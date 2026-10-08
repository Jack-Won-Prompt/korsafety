<?php

namespace App\Services;

use App\Models\AgentTask;
use App\Models\ErrorLog;
use App\Services\AgentHook;
use App\Models\ServiceRequest;
use App\Models\Setting;
use Throwable;

/**
 * Agent 가 처리할 일감을 접수한다.
 *
 * 접수는 본래 업무(오류 기록·SR 등록)를 절대 막으면 안 된다.
 * 그래서 모든 예외를 삼키고, 조건이 안 맞으면 조용히 지나간다.
 */
class AgentIntake
{
    /** 운영 오류 접수 — 같은 원인은 하루 한 번만 */
    public static function error(ErrorLog $log): void
    {
        try {
            if (! Setting::bool('agent_enabled') || ! Setting::bool('agent_error_enabled')) {
                return;
            }

            // Agent 자신이 낸 오류는 접수하지 않는다 (자기 실패를 자기가 분석하는 되돌이 방지)
            // trace 는 모델에 같은 이름의 메서드가 있어 속성 접근이 막히므로 원본 값에서 직접 꺼낸다
            $trace = (string) ($log->getAttributes()['trace'] ?? '');
            $where = mb_strtolower((string) $log->file.' '.$trace);
            foreach (['agentworker', 'agentfixer', 'agentintake', 'agenttask', 'agenthook', 'agentworkcommand'] as $mine) {
                if (str_contains($where, $mine)) {
                    return;
                }
            }

            $key = 'err:'.$log->fingerprint;
            $already = AgentTask::where('dedupe_key', $key)
                ->where('created_at', '>=', now()->subDay())->exists();
            if ($already) {
                return;
            }

            $task = AgentTask::create([
                'type' => 'error',
                'ref_id' => $log->id,
                'dedupe_key' => $key,
                'payload' => [
                    'class' => $log->exception_class,
                    'message' => mb_substr((string) $log->message, 0, 500),
                    'file' => $log->file,
                    'line' => $log->line,
                    'url' => $log->url,
                ],
                'status' => 'pending',
            ]);

            // 접수한 그 자리에서 웹훅으로 알린다 (주기 실행 없이 바로 처리되도록)
            AgentHook::notify($task);
        } catch (Throwable $ignored) {
            // 접수에 실패해도 오류 기록 자체는 남아 있어야 한다
        }
    }

    /** SR 접수 */
    public static function sr(ServiceRequest $sr): void
    {
        try {
            if (! Setting::bool('agent_enabled') || ! Setting::bool('agent_sr_enabled')) {
                return;
            }

            $key = 'sr:'.$sr->id;
            if (AgentTask::where('dedupe_key', $key)->exists()) {
                return;
            }

            $task = AgentTask::create([
                'type' => 'sr',
                'ref_id' => $sr->id,
                'dedupe_key' => $key,
                'payload' => [
                    'title' => $sr->title,
                    'category' => $sr->category,
                    'priority' => $sr->priority,
                ],
                'status' => 'pending',
            ]);

            // 접수한 그 자리에서 웹훅으로 알린다 (주기 실행 없이 바로 처리되도록)
            AgentHook::notify($task);
        } catch (Throwable $ignored) {
        }
    }
}
