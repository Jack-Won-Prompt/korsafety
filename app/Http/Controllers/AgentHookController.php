<?php

namespace App\Http\Controllers;

use App\Services\AgentHook;
use App\Services\AgentWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 웹훅 수신 — 오류·SR 접수 알림을 받아 일꾼을 깨운다.
 *
 * 처리를 여기서 직접 하지 않는 까닭이 있다.
 * 웹은 www-data 로 돌지만 작업용 사본과 배포 열쇠는 ubuntu 것이라,
 * 웹 프로세스는 코드를 고칠 수도 올릴 수도 없다.
 * 열쇠를 웹 쪽에 내주는 대신, 신호만 남기고 실제 일은 ubuntu 로 도는 쪽에 맡긴다.
 *
 * 신호는 약속한 자리에 빈 파일 하나를 두는 것으로 한다.
 * systemd 가 그 파일이 생기는 것을 보고 일꾼을 깨운다 — 주기적으로 들여다보지 않는다.
 */
class AgentHookController extends Controller
{
    /** 일꾼을 깨우는 신호를 두는 자리 */
    public const SIGNAL_DIR = '/var/tmp/korsafety-agent';

    public function handle(Request $request)
    {
        $body = $request->getContent();

        if (! AgentHook::verify($body, $request->header('x-agent-sign'))) {
            // 기록이 실패해도(로그 파일 권한 등) 거절 응답은 그대로 나가야 한다
            try {
                Log::warning('Agent 웹훅 — 서명이 맞지 않아 거절', ['ip' => $request->ip()]);
            } catch (Throwable $ignored) {
            }

            return response()->json(['message' => '서명이 올바르지 않습니다.'], 401);
        }

        if (! AgentWorker::ready()) {
            return response()->json(['message' => 'Agent 가 꺼져 있습니다.'], 202);
        }

        $taskId = (int) ($request->json('task_id') ?? 0);

        return response()->json([
            'received' => $taskId,
            'woken' => $this->signal($taskId),
        ], 202);
    }

    /**
     * 일꾼을 깨운다 — 약속한 자리에 빈 파일을 둔다.
     *
     * 깨우지 못해도 일감은 이미 대기 줄에 들어가 있으므로 다음 신호 때 함께 처리된다.
     * 그래서 여기서 실패해도 접수 자체를 되돌리지는 않는다.
     */
    private function signal(int $taskId): bool
    {
        try {
            if (! is_dir(self::SIGNAL_DIR)) {
                return false;
            }
            $file = self::SIGNAL_DIR.'/trigger-'.($taskId ?: 'catchup').'-'.time();

            return @file_put_contents($file, (string) $taskId) !== false;
        } catch (Throwable $e) {
            try {
                Log::warning('Agent 일꾼을 깨우지 못했다', ['task' => $taskId]);
            } catch (Throwable $ignored) {
            }

            return false;
        }
    }
}
