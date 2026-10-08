<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentTask;
use App\Models\Setting;
use App\Services\AgentFixer;
use App\Services\AgentWorker;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;

/** 자동 처리 Agent 작업 내역 — 무엇을 어떻게 고쳤는지 사람이 따라 읽을 수 있게 (본사 전용) */
class AgentTaskController extends Controller implements HasMiddleware
{
    /** 담당자만 볼 수 있다 — 화면을 숨기는 것만으로는 주소를 직접 치면 열리므로 여기서도 막는다 */
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                abort_unless(optional($request->user())->isAgentOperator(), 403, '자동 처리 Agent 담당자만 볼 수 있습니다.');

                return $next($request);
            },
        ];
    }

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $type = $request->query('type');

        $query = AgentTask::query();
        if ($status !== 'all' && isset(AgentTask::STATUSES[$status])) {
            $query->where('status', $status);
        }
        if ($type && isset(AgentTask::TYPES[$type])) {
            $query->where('type', $type);
        }
        $tasks = $query->latest('id')->paginate(30)->withQueryString();

        $byStatus = AgentTask::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $stats = [
            'all' => (int) $byStatus->sum(),
            'pending' => (int) ($byStatus['pending'] ?? 0),
            'done' => (int) ($byStatus['done'] ?? 0),
            'failed' => (int) ($byStatus['failed'] ?? 0),
            'skipped' => (int) ($byStatus['skipped'] ?? 0),
            'today' => AgentTask::whereDate('created_at', today())->count(),
            'tokens_today' => (int) AgentTask::whereDate('created_at', today())->sum('tokens'),
        ];

        $ready = [
            'enabled' => Setting::bool('agent_enabled'),
            'error' => Setting::bool('agent_error_enabled'),
            'sr' => Setting::bool('agent_sr_enabled'),
            'auto_fix' => Setting::bool('agent_auto_fix'),
            'key' => AgentWorker::apiKey() !== '',
            'limit' => (int) Setting::get('agent_daily_limit'),
        ];

        return view('admin.agent.index', compact('tasks', 'stats', 'status', 'type', 'ready'));
    }

    /** 지금 바로 대기 일감 처리 */
    public function run(Request $request)
    {
        if (! AgentWorker::ready()) {
            return back()->with('error', 'Agent 가 꺼져 있거나 서버에 API 키가 없습니다.');
        }

        $result = (new AgentWorker())->run(3);

        return back()->with('status', "처리 {$result['처리']}건 · 보류 {$result['건너뜀']}건 — ".implode(' / ', array_slice($result['글'], 0, 3)));
    }

    /** 실패·보류 건을 다시 대기로 */
    public function retry(AgentTask $task)
    {
        if ($task->status === 'done') {
            return back()->with('error', '이미 처리를 마친 작업입니다.');
        }

        $task->update(['status' => 'pending', 'attempts' => 0, 'result' => null]);

        return back()->with('status', "작업 #{$task->id} 을(를) 다시 대기로 돌렸습니다.");
    }

    /** Agent 가 올린 수정을 되돌린다 */
    public function revert(AgentTask $task)
    {
        if (! $task->commit_hash) {
            return back()->with('error', '되돌릴 수정 내역이 없습니다.');
        }

        $ok = (new AgentFixer())->rollback($task->commit_hash, '관리자 요청');

        return $ok
            ? back()->with('status', "작업 #{$task->id} 의 수정을 되돌리고 운영에 반영했습니다.")
            : back()->with('error', '되돌리지 못했습니다. 서버 상태를 확인해 주세요.');
    }
}
