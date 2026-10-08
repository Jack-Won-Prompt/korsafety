<?php

namespace App\Services;

use App\Models\AgentTask;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * 분석 결과대로 코드를 고쳐 운영에 올린다.
 *
 * 사람 확인 없이 운영 코드를 바꾸는 일이라 막는 장치를 겹겹이 둔다.
 *  1. 서빙 중인 폴더를 건드리지 않는다. 따로 받아 둔 작업용 사본에서 고치고 검증한다.
 *  2. 손댈 수 있는 자리를 경로로 제한한다. 모델이 "안전하다"고 답해도 경로로 다시 막는다.
 *     결제·주문·개인정보·인증·DB 구조·설정, 그리고 이 Agent 자신의 코드는 바꿀 수 없다.
 *  3. 파일 하나, 바뀐 줄 수 상한을 둔다. 크게 뜯어고치는 일은 사람 몫이다.
 *  4. 문법 검사와 화면 변환 검사를 통과해야 올린다.
 *  5. 올린 뒤 쇼핑몰이 정상 응답하지 않으면 즉시 되돌린다.
 */
class AgentFixer
{
    /** 작업용 사본 — 서빙 폴더와 분리한다 */
    private const WORK_DIR = '/home/ubuntu/www/agent-work';

    /** 운영 서빙 폴더 */
    private const LIVE_DIR = '/home/ubuntu/www/korsafety';

    /** 배포 후 확인할 주소 */
    private const HEALTH_URL = 'https://korsafety.co.kr/';

    /** 손댈 수 있는 자리 */
    private const ALLOW = ['app/', 'resources/views/', 'routes/', 'public/css/', 'public/js/', 'lang/'];

    /** 어떤 경우에도 손대지 않는 자리 (소문자 비교, 부분 일치) */
    private const DENY = [
        'database/migrations/', 'config/', '.env',
        'app/services/agentfixer.php', 'app/services/agentworker.php', 'app/services/agentintake.php',
        'app/models/setting.php', 'app/models/agenttask.php',
        'app/http/controllers/api/paymentcontroller.php',
        'app/http/controllers/cartcontroller.php',
        'app/models/order.php', 'app/models/user.php',
        'app/http/controllers/customerauthcontroller.php',
        'app/http/controllers/manage/authcontroller.php',
        'app/http/controllers/partnerregistercontroller.php',
        'app/http/controllers/inviteregistercontroller.php',
        'app/http/controllers/emailverificationcontroller.php',
    ];

    /** 바뀐 줄 수 상한 (추가 + 삭제) */
    private const MAX_LINES = 60;

    private const FIX_TIMEOUT = 300;

    private const API_URL = 'https://api.anthropic.com/v1/messages';

    /**
     * 분석 결과대로 고쳐 배포한다.
     *
     * @return array{tried:bool, fixed:bool, blocked:bool, message:string, commit:?string}
     */
    public function fix(AgentTask $task, array $analysis): array
    {
        $file = $this->normalizePath((string) ($analysis['file'] ?? ''));

        if ($file === '' || ! $this->allowed($file)) {
            return $this->out(false, true, '손대지 않는 자리입니다'.($file ? " ({$file})" : '').'. 담당자 확인이 필요합니다.');
        }
        if (! is_dir(self::WORK_DIR.'/.git')) {
            return $this->out(false, true, '작업용 사본이 준비되지 않아 수정하지 않았습니다.');
        }

        $lock = $this->lock();
        if (! $lock) {
            return $this->out(false, false, '다른 수정이 진행 중이라 다음 차례로 미룹니다.');
        }

        try {
            // 1) 작업용 사본을 최신으로 맞춘다
            $this->git(['fetch', 'origin', 'main']);
            $this->git(['checkout', '-B', 'agent-work', 'origin/main']);
            $this->git(['reset', '--hard', 'origin/main']);

            $path = self::WORK_DIR.'/'.$file;
            if (! is_file($path)) {
                return $this->out(false, true, "파일을 찾지 못했습니다 ({$file}).");
            }
            $before = file_get_contents($path);
            if (strlen($before) > 200_000) {
                return $this->out(false, true, '파일이 너무 커서 자동 수정 대상이 아닙니다.');
            }

            // 2) 고친 파일 전문을 받는다 (조각 수정은 줄이 어긋나기 쉬워 전문으로 받는다)
            $after = $this->rewrite($file, $before, $analysis, $task);
            if ($after === '' || $after === $before) {
                return $this->out(true, false, '고칠 내용을 만들지 못해 그대로 두었습니다.');
            }
            file_put_contents($path, $after);

            // 3) 검증 — 문법, 화면 변환, 변경량
            if ($problem = $this->verify($file)) {
                $this->revertWorkTree();

                return $this->out(true, false, '검증에서 걸러져 되돌렸습니다: '.$problem);
            }

            // 4) 저장소에 올린다
            $commit = $this->publish($file, $analysis, $task);
            if (! $commit) {
                $this->revertWorkTree();

                return $this->out(true, false, '저장소에 올리지 못해 되돌렸습니다.');
            }

            // 5) 운영 반영 후 상태 확인, 이상하면 즉시 되돌린다
            $deployed = $this->deploy();
            if (! $deployed['ok']) {
                $rolled = $this->rollback($commit, $deployed['reason']);

                return $this->out(true, false, '배포 후 이상이 있어 '
                    .($rolled ? '되돌렸습니다' : '되돌리지 못했습니다 — 즉시 확인 필요').': '.$deployed['reason']);
            }

            return ['tried' => true, 'fixed' => true, 'blocked' => false, 'commit' => $commit,
                'message' => '수정해 운영에 반영했습니다.'];
        } catch (Throwable $e) {
            report($e);
            $this->revertWorkTree();

            return $this->out(true, false, '수정 중 문제가 생겨 되돌렸습니다: '.mb_substr($e->getMessage(), 0, 200));
        } finally {
            $this->unlock($lock);
        }
    }

    /** 저장소 기준 경로로 다듬는다 */
    public function normalizePath(string $raw): string
    {
        $raw = str_replace('\\', '/', trim($raw));
        if (preg_match('~((?:app|routes|resources|config|database|public|lang)/[\w./-]+)~', $raw, $m)) {
            return trim($m[1], '/');
        }

        return '';
    }

    /** 손대도 되는 자리인가 — 경로로 거른다 (모델 판단을 믿지 않는 두 번째 방어선) */
    public function allowed(string $file): bool
    {
        $lower = mb_strtolower($file);

        if (str_contains($lower, '..')) {
            return false;
        }
        foreach (self::DENY as $deny) {
            if (str_contains($lower, $deny)) {
                return false;
            }
        }
        foreach (self::ALLOW as $allow) {
            if (str_starts_with($lower, $allow)) {
                return true;
            }
        }

        return false;
    }

    /** 고친 파일 전문을 받아 온다 */
    private function rewrite(string $file, string $before, array $analysis, AgentTask $task): string
    {
        $key = AgentWorker::apiKey();
        $model = (string) \App\Models\Setting::get('agent_model_fix');
        if (! in_array($model, AgentWorker::MODELS, true)) {
            $model = 'claude-opus-5';
        }

        $system = <<<'TXT'
당신은 Laravel 12 쇼핑몰의 코드를 고치는 개발자입니다.
받은 파일에서 지적된 문제만 고쳐, 고친 파일 전체를 그대로 돌려주십시오.

지켜야 할 것
 · 문제와 상관없는 줄은 한 글자도 바꾸지 마십시오. 들여쓰기·빈 줄·주석도 그대로 두십시오.
 · 구조를 새로 짜거나 새 파일·새 설정을 만들지 마십시오.
 · 결제 금액, 주문 처리, 회원 개인정보, 로그인 인증, DB 구조에 닿는 수정은 하지 마십시오.
   그런 자리라면 한 글자도 고치지 말고 원본 그대로 돌려주십시오.
 · Blade 화면을 고칠 때 @endif@if 처럼 지시문을 붙여 쓰지 마십시오. 화면이 깨집니다.
 · 주석은 한국어로, 왜 그렇게 했는지를 적으십시오.
 · 답은 파일 내용만 보내십시오. 설명도, ``` 울타리도 붙이지 마십시오.
TXT;

        $user = "고칠 파일: {$file}\n\n"
            .'원인: '.($analysis['cause'] ?? '')."\n"
            .'고치는 법: '.($analysis['how'] ?? '')."\n\n"
            ."— 파일 —\n".$before;

        $res = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(self::FIX_TIMEOUT)->post(self::API_URL, [
            'model' => $model,
            'max_tokens' => 16000,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('수정 호출 실패 ('.$res->status().')');
        }

        // 생각 블록이 먼저 올 수 있으므로 text 블록만 골라 잇는다
        $text = collect($res->json('content') ?? [])
            ->filter(fn ($c) => ($c['type'] ?? '') === 'text')
            ->pluck('text')->implode("\n");

        $task->tokens = (int) $task->tokens
            + (int) ($res->json('usage.input_tokens') ?? 0)
            + (int) ($res->json('usage.output_tokens') ?? 0);

        if (preg_match('~^```[a-z]*\n(.*)\n```\s*$~s', trim($text), $m)) {
            $text = $m[1];
        }

        return trim($text) === '' ? '' : rtrim($text)."\n";
    }

    /** 검증 — 통과하면 null, 걸리면 사유 */
    public function verify(string $file): ?string
    {
        // 1) PHP 문법
        if (str_ends_with($file, '.php')) {
            $lint = Process::path(self::WORK_DIR)->timeout(60)->run(['php', '-l', $file]);
            if (! $lint->successful()) {
                return '문법 오류 — '.mb_substr(trim($lint->output().$lint->errorOutput()), 0, 200);
            }
        }

        // 2) 화면은 변환한 뒤 PHP 문법까지 본다 — 변환만으로는 깨진 지시문을 잡지 못한다
        if (str_ends_with($file, '.blade.php')) {
            Process::path(self::WORK_DIR)->timeout(120)->run('php artisan view:clear');
            $compile = Process::path(self::WORK_DIR)->timeout(240)->run('php artisan view:cache 2>&1');
            if (! $compile->successful()) {
                return '화면 변환 실패';
            }
            $check = Process::path(self::WORK_DIR)->timeout(240)->run(
                'bad=0; for f in storage/framework/views/*.php; do php -l "$f" >/dev/null 2>&1 || bad=$((bad+1)); done; echo $bad'
            );
            Process::path(self::WORK_DIR)->timeout(60)->run('php artisan view:clear');
            if ((int) trim($check->output()) > 0) {
                return '화면 문법 오류 '.trim($check->output()).'건';
            }
        }

        // 3) 변경량 — 파일 하나, 정해진 줄 수 안쪽
        $stat = Process::path(self::WORK_DIR)->timeout(60)->run(['git', 'diff', '--numstat']);
        $lines = array_values(array_filter(explode("\n", trim($stat->output()))));
        if (count($lines) !== 1) {
            return '바뀐 파일이 '.count($lines).'개입니다 (1개만 허용)';
        }
        $cols = preg_split('/\s+/', trim($lines[0]));
        $changed = (int) ($cols[0] ?? 0) + (int) ($cols[1] ?? 0);
        if ($changed > self::MAX_LINES) {
            return "바뀐 줄이 너무 많습니다 ({$changed}줄, 상한 ".self::MAX_LINES.'줄)';
        }

        return null;
    }

    /** 커밋하고 올린다 — 성공하면 커밋 해시 */
    private function publish(string $file, array $analysis, AgentTask $task): ?string
    {
        $title = mb_substr(trim((string) ($analysis['how'] ?? '자동 수정')), 0, 60);
        $message = $title." (Agent 자동 수정)\n\n"
            .'원인: '.($analysis['cause'] ?? '-')."\n"
            ."고친 자리: {$file}\n"
            ."작업 #{$task->id} · {$task->type_label} {$task->ref_id} · 확신 ".($analysis['confidence'] ?? '-')."\n";

        $this->git(['add', $file]);

        $commit = Process::path(self::WORK_DIR)->timeout(120)->input($message)->run([
            'git', '-c', 'user.name=KOR SAFETY Agent', '-c', 'user.email=agent@korsafety.co.kr',
            'commit', '-F', '-',
        ]);
        if (! $commit->successful()) {
            return null;
        }

        // 올리기 직전에 다시 맞춘다 — 그사이 사람이 올렸을 수 있다
        $this->git(['fetch', 'origin', 'main']);
        $rebase = Process::path(self::WORK_DIR)->timeout(120)->run(['git', 'rebase', 'origin/main']);
        if (! $rebase->successful()) {
            Process::path(self::WORK_DIR)->timeout(60)->run(['git', 'rebase', '--abort']);

            return null;
        }

        $push = Process::path(self::WORK_DIR)->timeout(180)->run(['git', 'push', 'origin', 'HEAD:main']);
        if (! $push->successful()) {
            Log::warning('Agent 푸시 실패', ['오류' => mb_substr($push->errorOutput(), 0, 300)]);

            return null;
        }

        $hash = Process::path(self::WORK_DIR)->timeout(60)->run(['git', 'rev-parse', 'HEAD']);

        return trim($hash->output()) ?: null;
    }

    /** 운영 폴더에 반영하고 사이트가 정상인지 확인한다 */
    private function deploy(): array
    {
        $pull = Process::path(self::LIVE_DIR)->timeout(300)->run('git pull --ff-only origin main 2>&1');
        if (! $pull->successful()) {
            return ['ok' => false, 'reason' => '운영 폴더 반영 실패'];
        }

        Process::path(self::LIVE_DIR)->timeout(300)->run('php artisan config:clear && php artisan view:clear');
        $cache = Process::path(self::LIVE_DIR)->timeout(300)->run('php artisan route:cache && php artisan view:cache 2>&1');
        if (! $cache->successful()) {
            return ['ok' => false, 'reason' => '캐시 재생성 실패'];
        }

        $check = Process::timeout(60)->run('curl -s -o /dev/null -w "%{http_code}" '.escapeshellarg(self::HEALTH_URL));
        $code = (int) trim($check->output());
        if ($code !== 200) {
            return ['ok' => false, 'reason' => "쇼핑몰 응답 {$code}"];
        }

        return ['ok' => true, 'reason' => ''];
    }

    /** 올린 커밋을 되돌리고 운영까지 복구한다 */
    public function rollback(string $hash, string $why): bool
    {
        try {
            $this->git(['fetch', 'origin', 'main']);
            $this->git(['checkout', '-B', 'agent-work', 'origin/main']);

            $revert = Process::path(self::WORK_DIR)->timeout(120)->run(['git', 'revert', '--no-edit', $hash]);
            if (! $revert->successful()) {
                return false;
            }
            $push = Process::path(self::WORK_DIR)->timeout(180)->run(['git', 'push', 'origin', 'HEAD:main']);
            if (! $push->successful()) {
                return false;
            }

            Process::path(self::LIVE_DIR)->timeout(300)->run('git pull --ff-only origin main');
            Process::path(self::LIVE_DIR)->timeout(300)->run('php artisan config:clear && php artisan view:clear');
            Process::path(self::LIVE_DIR)->timeout(300)->run('php artisan route:cache && php artisan view:cache');

            Log::warning('Agent 수정 되돌림', ['커밋' => $hash, '사유' => $why]);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }

    private function revertWorkTree(): void
    {
        Process::path(self::WORK_DIR)->timeout(60)->run(['git', 'checkout', '--', '.']);
    }

    private function git(array $args): void
    {
        $res = Process::path(self::WORK_DIR)->timeout(180)->run(array_merge(['git'], $args));
        if (! $res->successful()) {
            throw new \RuntimeException('git '.implode(' ', $args).' 실패: '.mb_substr($res->errorOutput(), 0, 200));
        }
    }

    /** @return resource|false */
    private function lock()
    {
        $path = storage_path('app/agent-fixer.lock');
        $fp = @fopen($path, 'c');
        if (! $fp) {
            return false;
        }
        if (! flock($fp, LOCK_EX | LOCK_NB)) {
            fclose($fp);

            return false;
        }

        return $fp;
    }

    private function unlock($fp): void
    {
        if ($fp) {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private function out(bool $tried, bool $blocked, string $message): array
    {
        return ['tried' => $tried, 'fixed' => false, 'blocked' => $blocked, 'message' => $message, 'commit' => null];
    }
}
