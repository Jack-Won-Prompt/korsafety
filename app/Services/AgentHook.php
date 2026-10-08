<?php

namespace App\Services;

use App\Models\AgentTask;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 접수된 일감을 웹훅으로 곧바로 알린다.
 *
 * 주기 실행(cron)으로 빈 표를 들여다보지 않고, 오류·SR 이 생긴 그 자리에서 보낸다.
 * 보내는 쪽은 절대 기다리지 않는다 — 본래 업무(오류 기록·SR 등록)를 막으면 안 되므로
 * 시간 제한을 짧게 두고 실패해도 그냥 지나간다. 받은 쪽이 바로 응답하고 뒤에서 처리한다.
 *
 * 서명은 APP_KEY 로 만든다. 따로 비밀값을 더 두지 않아도 되고,
 * 서버 밖에서는 같은 서명을 만들 수 없다.
 */
class AgentHook
{
    private const TIMEOUT = 2;

    /** 웹훅 주소 — 설정이 없으면 사이트 주소에서 만든다 */
    public static function url(): string
    {
        $url = trim((string) config('services.agent.hook_url'));

        return $url !== '' ? $url : rtrim((string) config('app.url'), '/').'/agent/hook';
    }

    /** 본문 서명 */
    public static function sign(string $body): string
    {
        return hash_hmac('sha256', $body, (string) config('app.key'));
    }

    /** 서명이 맞는가 (길이·내용 모두 비교) */
    public static function verify(string $body, ?string $given): bool
    {
        return is_string($given) && hash_equals(self::sign($body), $given);
    }

    /** 일감이 생겼음을 알린다 */
    public static function notify(AgentTask $task): void
    {
        try {
            if (! AgentWorker::ready()) {
                return;
            }

            $body = json_encode([
                'task_id' => $task->id,
                'type' => $task->type,
                'ref_id' => $task->ref_id,
                'at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_UNICODE);

            Http::withHeaders([
                'content-type' => 'application/json',
                'x-agent-sign' => self::sign($body),
            ])->timeout(self::TIMEOUT)->connectTimeout(self::TIMEOUT)
                ->withBody($body, 'application/json')
                ->post(self::url());
        } catch (Throwable $e) {
            // 알리지 못해도 일감은 대기 상태로 남는다.
            // 다음 웹훅이 올 때 함께 처리되고, 관리자 화면에서 바로 돌릴 수도 있다.
            Log::info('Agent 웹훅 발송 실패 — 대기 상태로 남겨 둡니다', [
                'task' => $task->id, '사유' => mb_substr($e->getMessage(), 0, 150),
            ]);
        }
    }
}
