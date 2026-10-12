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

    /** 바뀐 줄 수 상한 (추가 + 삭제) — 예고 없이 터진 오류는 좁게, 받아 둔 요청은 넓게 */
    private const MAX_LINES = 60;

    private const MAX_LINES_SR = 400;

    /** 한 요청에서 고칠 수 있는 파일 수 */
    private const MAX_FILES_SR = 4;

    /** 고친 전문을 받을 때 모델에게 허용하는 출력 길이 */
    private const REWRITE_MAX_TOKENS = 32000;

    /**
     * 손댈 수 있는 파일 크기 상한 (바이트).
     *
     * 예전에는 파일 전체를 다시 받아 덮어썼다. 그래서 답이 출력 한도에 걸리면
     * 뒤가 잘린 채 돌아왔고, 잘린 줄 모르고 덮어써 shop.css 가 반토막 난 적이 있다.
     * 그 탓에 45KB 넘는 파일은 아예 손대지 못했다 — 쇼핑몰 스타일 파일이 꼭 거기 걸렸다.
     *
     * 지금은 바꿀 조각만 받아 끼워 넣으므로, 파일 크기는 읽어서 보내 줄 수 있는가의
     * 문제일 뿐이다. 받는 양과 무관해져 상한을 크게 잡는다.
     */
    private const MAX_REWRITE_BYTES = 250_000;

    private const FIX_TIMEOUT = 300;

    private const API_URL = 'https://api.anthropic.com/v1/messages';

    /**
     * 분석 결과대로 고쳐 배포한다.
     *
     * @return array{tried:bool, fixed:bool, blocked:bool, message:string, commit:?string}
     */
    public function fix(AgentTask $task, array $analysis): array
    {
        $isSr = $task->type === 'sr';

        $files = [];
        foreach ((array) ($analysis['files'] ?? [$analysis['file'] ?? '']) as $one) {
            $path = $this->normalizePath((string) $one);
            if ($path !== '' && $this->allowed($path) && ! in_array($path, $files, true)) {
                $files[] = $path;
            }
        }
        $files = array_slice($files, 0, $isSr ? self::MAX_FILES_SR : 1);

        if (! $files) {
            return $this->out(false, true, '손대지 않는 자리입니다. 담당자 확인이 필요합니다.');
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

            // 2) 파일마다 고친 전문을 받는다 (조각 수정은 줄이 어긋나기 쉬워 전문으로 받는다)
            $touched = $notes = [];
            foreach ($files as $file) {
                $path = self::WORK_DIR.'/'.$file;
                if (! is_file($path)) {
                    $notes[] = "{$file}: 파일 없음";
                    continue;
                }
                $before = file_get_contents($path);
                if (strlen($before) > self::MAX_REWRITE_BYTES) {
                    $notes[] = "{$file}: 너무 커서 건너뜀 ("
                        .number_format(strlen($before) / 1024, 1).'KB, 상한 '
                        .number_format(self::MAX_REWRITE_BYTES / 1024).'KB)';
                    continue;
                }
                $after = $this->rewrite($file, $before, $analysis, $task);
                if ($after === '' || $after === $before) {
                    $notes[] = "{$file}: 고칠 내용 없음";
                    continue;
                }
                file_put_contents($path, $after);
                $touched[] = $file;
            }

            if (! $touched) {
                $this->revertWorkTree();

                return $this->out(true, false, '고칠 내용을 만들지 못해 그대로 두었습니다. '.implode(' / ', $notes));
            }

            // 3) 검증 — 문법, 화면 변환, 변경량.
            //    걸리면 무엇이 걸렸는지 알려 주고 한 번 더 받아 본다. 그래도 걸리면 되돌린다.
            if ($problem = $this->verify($touched, $isSr)) {
                $retried = [];
                foreach ($touched as $file) {
                    $path = self::WORK_DIR.'/'.$file;
                    $broken = (string) file_get_contents($path);
                    $this->git(['checkout', '--', $file]);
                    $before = (string) file_get_contents($path);
                    $after = $this->rewrite($file, $before, $analysis, $task, $problem, $broken);
                    if ($after !== '' && $after !== $before) {
                        file_put_contents($path, $after);
                        $retried[] = $file;
                    }
                }
                $again = $retried ? $this->verify($retried, $isSr) : '다시 고치지 못했습니다.';
                if ($again) {
                    $this->revertWorkTree();

                    return $this->out(true, false, '검증에서 걸러져 되돌렸습니다: '.$again);
                }
                $touched = $retried;
            }

            // 4) 저장소에 올린다
            $commit = $this->publish($touched, $analysis, $task);
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
                'message' => '수정해 운영에 반영했습니다 ('.implode(', ', $touched).').'
                    .($notes ? ' 못 한 것 — '.implode(' / ', $notes) : '')];
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

    /**
     * 고친 파일 전문을 받아 온다.
     *
     * $problem 이 있으면 앞서 내놓은 답이 검증에 걸렸다는 뜻이다.
     * 무엇이 걸렸는지와 걸린 내용을 함께 주어 같은 실수를 되풀이하지 않게 한다.
     */
    private function rewrite(
        string $file,
        string $before,
        array $analysis,
        AgentTask $task,
        ?string $problem = null,
        ?string $broken = null
    ): string {
        $key = AgentWorker::apiKey();
        $model = (string) \App\Models\Setting::get('agent_model_fix');
        if (! in_array($model, AgentWorker::MODELS, true)) {
            $model = 'claude-opus-5';
        }

        $system = <<<'TXT'
당신은 Laravel 12 쇼핑몰의 코드를 고치는 개발자입니다.
받은 파일에서 지적된 문제만 고칩니다. 파일 전체를 돌려주지 마십시오.
바꿀 자리마다 아래 형식의 덩이를 하나씩 보내십시오.

<<<<<<< SEARCH
(파일에 지금 있는 그대로의 줄)
=======
(그 자리를 대신할 줄)
>>>>>>> REPLACE

SEARCH 규칙 — 이것이 어긋나면 수정은 통째로 버려집니다.
 · 파일에 있는 글자를 그대로 옮기십시오. 들여쓰기·빈 줄·따옴표·주석까지 한 글자도 다르면 안 됩니다.
 · 파일 안에서 그 자리에만 있는 덩이여야 합니다. 짧아서 여러 군데 걸릴 것 같으면
   앞뒤 줄을 더 붙여 길게 잡으십시오.
 · 줄 번호나 … 같은 줄임 표시를 넣지 마십시오.
 · 지우기만 할 때는 REPLACE 쪽을 비워 두십시오.

지켜야 할 것
 · 문제와 상관없는 자리는 건드리지 마십시오.
 · 구조를 새로 짜거나 새 파일·새 설정을 만들지 마십시오.
 · 결제 금액, 주문 처리, 회원 개인정보, 로그인 인증, DB 구조에 닿는 수정은 하지 마십시오.
   그런 자리라면 덩이를 하나도 보내지 마십시오.
 · Blade 화면을 고칠 때 @endif@if 처럼 지시문을 붙여 쓰지 마십시오. 화면이 깨집니다.
 · 주석은 한국어로, 왜 그렇게 했는지를 적으십시오.
 · 덩이 말고 다른 말은 쓰지 마십시오.
TXT;

        $user = "고칠 파일: {$file}\n\n"
            .'원인: '.($analysis['cause'] ?? '')."\n"
            .'고치는 법: '.($analysis['how'] ?? '')."\n";

        if ($problem) {
            $user .= "\n[앞서 보낸 수정이 걸렸습니다]\n걸린 사유: {$problem}\n"
                ."같은 실수를 되풀이하지 마십시오. 바꾸는 자리를 더 좁게 잡으십시오.\n"
                ."SEARCH 를 찾지 못했다는 사유라면, 파일에서 글자를 다시 그대로 옮겨 오십시오.\n";
        }

        $user .= "\n— 파일 —\n".$before;

        $res = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(self::FIX_TIMEOUT)->post(self::API_URL, [
            'model' => $model,
            'max_tokens' => self::REWRITE_MAX_TOKENS,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('수정 호출 실패 ('.$res->status().')');
        }

        // 답이 길이 한도에 걸려 끊겼으면 뒷부분이 통째로 없는 파일이다. 쓰지 않는다.
        if ($res->json('stop_reason') === 'max_tokens') {
            Log::warning('Agent 수정 답이 잘려 버렸다', ['파일' => $file]);

            return '';
        }

        // 생각 블록이 먼저 올 수 있으므로 text 블록만 골라 잇는다
        $text = collect($res->json('content') ?? [])
            ->filter(fn ($c) => ($c['type'] ?? '') === 'text')
            ->pluck('text')->implode("\n");

        $task->tokens = (int) $task->tokens
            + (int) ($res->json('usage.input_tokens') ?? 0)
            + (int) ($res->json('usage.output_tokens') ?? 0);

        $after = $this->applyEdits($before, $text, $file);
        if ($after === null || $after === $before) {
            return '';
        }

        // 고치랬더니 절반이 사라졌다면 뭔가 잘못된 것이다. 끼워 넣기로 그럴 일은 드물지만
        // 큰 덩이를 통째로 지우는 수정이 올 수 있으므로 여기서도 한 번 본다.
        if (strlen($before) >= 2000 && strlen($after) < strlen($before) * 0.75) {
            Log::warning('Agent 수정 뒤 파일이 크게 짧아졌다', [
                '파일' => $file,
                '원본' => strlen($before),
                '고친 뒤' => strlen($after),
            ]);

            return '';
        }

        return $after;
    }

    /**
     * 라우트가 가리키는 컨트롤러 메서드가 모두 있는지 본다.
     *
     * 작업용 사본에서 따로 띄워 확인한다. 지금 돌고 있는 쪽은 아직 예전 코드라
     * 여기서 class_exists 로 봐 봐야 바뀐 파일을 보지 못한다.
     *
     * @return string|null 모두 멀쩡하면 null, 끊긴 자리가 있으면 사유
     */
    private function checkRoutes(): ?string
    {
        $script = <<<'PHP'
        $bad = [];
        foreach (app('router')->getRoutes() as $route) {
            $uses = $route->getAction('uses');
            if (! is_string($uses) || ! str_contains($uses, '@')) {
                continue;
            }
            [$class, $method] = explode('@', $uses, 2);
            if (! class_exists($class) || ! method_exists($class, $method)) {
                $bad[] = $route->uri().' → '.class_basename($class).'@'.$method;
            }
        }
        echo $bad ? implode(' / ', array_slice($bad, 0, 3)) : 'OK';
        PHP;

        $path = self::WORK_DIR.'/storage/app/agent-routecheck.php';
        if (@file_put_contents($path, "<?php\n".$script) === false) {
            return null;   // 검사를 못 했다고 해서 수정을 막지는 않는다
        }

        $run = Process::path(self::WORK_DIR)->timeout(120)
            ->run(['php', 'artisan', 'tinker', 'storage/app/agent-routecheck.php']);
        @unlink($path);

        $out = trim($run->output());
        if ($out === '' || ! $run->successful()) {
            return null;
        }
        if (! str_contains($out, 'OK')) {
            return '주소가 끊겼습니다 — '.mb_substr($out, 0, 200);
        }

        return null;
    }

    /**
     * 받은 덩이를 파일에 끼워 넣는다.
     *
     * SEARCH 가 파일에 없거나 여러 군데 걸리면 어디를 고치라는 것인지 알 수 없다.
     * 그럴 때는 한 덩이라도 어긋나면 전부 버린다 — 반만 고친 파일이 더 위험하다.
     *
     * @return string|null 고친 내용, 끼워 넣지 못하면 null
     */
    private function applyEdits(string $before, string $text, string $file): ?string
    {
        $pattern = '~^<{5,9} SEARCH\s*?\n(.*?)\n?^={5,9}\s*?\n(.*?)\n?^>{5,9} REPLACE\s*?$~ms';
        if (! preg_match_all($pattern, $text, $blocks, PREG_SET_ORDER)) {
            Log::warning('Agent 수정 덩이를 읽지 못했다', ['파일' => $file]);

            return null;
        }

        $after = $before;
        foreach ($blocks as $i => $block) {
            [, $search, $replace] = $block;

            if (trim($search) === '') {
                Log::warning('Agent 수정 덩이의 찾을 자리가 비어 있다', ['파일' => $file]);

                return null;
            }

            $hits = substr_count($after, $search);
            if ($hits !== 1) {
                Log::warning('Agent 수정 덩이를 그 자리에 놓지 못했다', [
                    '파일' => $file,
                    '덩이' => $i + 1,
                    '걸린 수' => $hits,
                    '찾던 것' => mb_substr($search, 0, 120),
                ]);

                return null;
            }

            $after = str_replace($search, $replace, $after);
        }

        return $after;
    }

    /**
     * 검증 — 통과하면 null, 걸리면 사유.
     *
     * @param  array<int, string>|string  $files  고친 파일들
     */
    public function verify(array|string $files, bool $wide = false): ?string
    {
        $files = (array) $files;

        // 1) PHP 문법
        foreach ($files as $file) {
            if (! str_ends_with($file, '.php')) {
                continue;
            }
            $lint = Process::path(self::WORK_DIR)->timeout(60)->run(['php', '-l', $file]);
            if (! $lint->successful()) {
                return "문법 오류 ({$file}) — ".mb_substr(trim($lint->output().$lint->errorOutput()), 0, 200);
            }
        }

        // 2) 화면은 변환한 뒤 PHP 문법까지 본다 — 변환만으로는 깨진 지시문을 잡지 못한다
        $hasBlade = (bool) array_filter($files, fn ($f) => str_ends_with($f, '.blade.php'));
        if ($hasBlade) {
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

        // 3) 스타일·스크립트는 문법 검사 도구가 없으니 괄호 짝만이라도 맞는지 본다
        foreach ($files as $file) {
            if (! preg_match('~\.(css|js)$~', $file)) {
                continue;
            }
            $body = (string) @file_get_contents(self::WORK_DIR.'/'.$file);
            if (substr_count($body, '{') !== substr_count($body, '}')) {
                return "괄호 짝이 맞지 않습니다 ({$file}) — 답이 잘렸을 수 있습니다";
            }
        }

        // 4) 길이 뚫린 자리가 없는지 — 라우트가 가리키는 메서드가 실제로 있어야 한다.
        //    메서드를 지우면서 라우트를 그대로 두면 그 주소만 죽는데, 문법 검사도
        //    화면 변환 검사도 이것은 잡지 못하고 첫 화면은 멀쩡해 배포 뒤 확인도 통과한다.
        //    실제로 Agent 가 editImage 를 지우고 라우트를 남겨 주소 하나가 죽은 적이 있다.
        if ($problem = $this->checkRoutes()) {
            return $problem;
        }

        // 5) 변경량 — 짚어 둔 파일만, 정해진 줄 수 안쪽
        $maxFiles = $wide ? self::MAX_FILES_SR : 1;
        $maxLines = $wide ? self::MAX_LINES_SR : self::MAX_LINES;

        $stat = Process::path(self::WORK_DIR)->timeout(60)->run(['git', 'diff', '--numstat']);
        $rows = array_values(array_filter(explode("\n", trim($stat->output()))));
        if (count($rows) > $maxFiles) {
            return '바뀐 파일이 '.count($rows)."개입니다 (상한 {$maxFiles}개)";
        }

        $changed = $added = $removed = 0;
        foreach ($rows as $row) {
            $cols = preg_split('/\s+/', trim($row));
            $added += (int) ($cols[0] ?? 0);
            $removed += (int) ($cols[1] ?? 0);
            $changed += (int) ($cols[0] ?? 0) + (int) ($cols[1] ?? 0);

            // 짚어 둔 자리 밖을 건드렸으면 그대로 걸러 낸다
            $path = trim((string) ($cols[2] ?? ''));
            if ($path !== '' && ! in_array($path, $files, true)) {
                return "짚지 않은 파일이 바뀌었습니다 ({$path})";
            }
        }
        if (! $wide && $changed > $maxLines) {
            return "바뀐 줄이 너무 많습니다 ({$changed}줄, 상한 {$maxLines}줄)";
        }

        // 요청 처리는 줄 수로 막지 않는다. 다만 더한 것 없이 지우기만 많이 했다면
        // 고친 것이 아니라 잘려 나간 것이다. 그것만은 거른다.
        if ($added + $removed > 0 && $removed >= 50 && $added < $removed * 0.2) {
            return "지우기만 하는 수정입니다 (+{$added} / -{$removed}) — 답이 잘렸을 수 있습니다";
        }

        return null;
    }

    /**
     * 커밋하고 올린다 — 성공하면 커밋 해시.
     *
     * @param  array<int, string>  $files  고친 파일들
     */
    private function publish(array $files, array $analysis, AgentTask $task): ?string
    {
        $title = mb_substr(trim((string) ($analysis['how'] ?? '자동 수정')), 0, 60);
        $message = $title." (Agent 자동 수정)\n\n"
            .'원인: '.($analysis['cause'] ?? '-')."\n"
            .'고친 자리: '.implode(', ', $files)."\n"
            ."작업 #{$task->id} · {$task->type_label} {$task->ref_id} · 확신 ".($analysis['confidence'] ?? '-')."\n";

        $this->git(array_merge(['add'], $files));

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
