<?php

namespace App\Http\Controllers;

use App\Models\AgentTask;
use App\Services\AgentHook;
use App\Services\AgentWorker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * 웹훅 수신 — 오류·SR 접수 알림을 받아 그 일감을 바로 처리한다.
 *
 * 받자마자 응답을 돌려주고, 실제 처리는 응답을 보낸 뒤에 한다.
 * 분석과 수정에 1~5분이 걸릴 수 있어, 보내는 쪽을 붙잡아 두면 안 되기 때문이다.
 */
class AgentHookController extends Controller
{
    /** 한 번 받을 때 함께 처리할 밀린 일감 수 (웹훅을 놓친 건을 따라잡는다) */
    private const CATCH_UP = 3;

    public function handle(Request $request)
    {
        $body = $request->getContent();

        if (! AgentHook::verify($body, $request->header('x-agent-sign'))) {
            Log::warning('Agent 웹훅 — 서명이 맞지 않아 거절', ['ip' => $request->ip()]);

            return response()->json(['message' => '서명이 올바르지 않습니다.'], 401);
        }

        if (! AgentWorker::ready()) {
            return response()->json(['message' => 'Agent 가 꺼져 있습니다.'], 202);
        }

        $taskId = (int) ($request->json('task_id') ?? 0);

        // 응답을 먼저 돌려주고, 처리는 그 뒤에 한다
        app()->terminating(function () use ($taskId) {
            $this->process($taskId);
        });

        return response()->json(['received' => $taskId], 202);
    }

    /** 알림이 온 일감을 처리하고, 밀린 것도 함께 따라잡는다 */
    private function process(int $taskId): void
    {
        try {
            $worker = new AgentWorker();

            $task = AgentTask::where('id', $taskId)->where('status', 'pending')->first();
            if ($task) {
                $worker->handle($task);
            }

            // 웹훅을 놓쳐 남아 있는 일감이 있으면 함께 처리한다 (cron 없이 스스로 따라잡기)
            $worker->run(self::CATCH_UP);
        } catch (Throwable $e) {
            Log::error('Agent 웹훅 처리 실패', ['task' => $taskId, '사유' => mb_substr($e->getMessage(), 0, 300)]);
        }
    }
}
