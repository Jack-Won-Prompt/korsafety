<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** 오류 관리 — 서버·웹스크립트 오류의 유형 · 상세 조회와 처리 상태 관리 (본사 전용) */
class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'unresolved');
        if ($status !== 'all' && ! isset(ErrorLog::STATUSES[$status])) {
            $status = 'unresolved';
        }
        $type = $request->query('type');
        $source = $request->query('source');
        $q = trim((string) $request->query('q', ''));
        $from = $request->query('from');
        $to = $request->query('to');

        $list = ErrorLog::query()->with(['resolver', 'user']);
        if ($status !== 'all') {
            $list->where('status', $status);
        }
        if ($type && isset(ErrorLog::TYPES[$type])) {
            $list->where('type', $type);
        }
        if ($source && isset(ErrorLog::SOURCES[$source])) {
            $list->where('source', $source);
        }
        if ($q !== '') {
            $list->where(fn ($w) => $w->where('message', 'like', "%$q%")
                ->orWhere('exception_class', 'like', "%$q%")
                ->orWhere('file', 'like', "%$q%")
                ->orWhere('url', 'like', "%$q%"));
        }
        if ($from) {
            $list->whereDate('last_seen_at', '>=', $from);
        }
        if ($to) {
            $list->whereDate('last_seen_at', '<=', $to);
        }
        $logs = $list->with('assignee')->orderByDesc('last_seen_at')->orderByDesc('id')->paginate(30)->withQueryString();

        $byStatus = ErrorLog::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $stats = [
            'unresolved' => (int) ($byStatus['unresolved'] ?? 0),
            'in_progress' => (int) ($byStatus['in_progress'] ?? 0),
            'resolved' => (int) ($byStatus['resolved'] ?? 0),
            'ignored' => (int) ($byStatus['ignored'] ?? 0),
            'all' => (int) $byStatus->sum(),
            'today' => ErrorLog::whereDate('last_seen_at', today())->count(),
            'today_new' => ErrorLog::whereDate('first_seen_at', today())->count(),
            'week_new' => ErrorLog::where('first_seen_at', '>=', today()->subDays(6))->count(),
            'javascript' => ErrorLog::whereIn('status', ErrorLog::OPEN_STATUSES)->where('type', 'javascript')->count(),
        ];
        // 처리가 끝나지 않은 건의 유형별 수
        $typeCounts = ErrorLog::whereIn('status', ErrorLog::OPEN_STATUSES)
            ->selectRaw('type, COUNT(*) c')->groupBy('type')->pluck('c', 'type');

        // 담당자로 지정할 수 있는 계정 (본사)
        $staff = User::where('role', 'hq_admin')->orderBy('name')->get(['id', 'name']);

        return view('admin.errors.index', compact('logs', 'stats', 'typeCounts', 'staff', 'status', 'type', 'source', 'q', 'from', 'to'));
    }

    public function show(ErrorLog $errorLog)
    {
        $errorLog->load(['user', 'resolver', 'assignee']);
        $staff = User::where('role', 'hq_admin')->orderBy('name')->get(['id', 'name']);

        // 같은 원인으로 이전에 해결 처리했던 이력 (재발 확인용)
        $related = ErrorLog::where('fingerprint', $errorLog->fingerprint)
            ->where('id', '!=', $errorLog->id)
            ->with('resolver')->latest('id')->limit(10)->get();

        return view('admin.errors.show', ['log' => $errorLog, 'related' => $related, 'staff' => $staff]);
    }

    /** 처리 상태 변경 — 미처리 · 처리중 · 처리완료 · 확인함(무시) */
    public function status(Request $request, ErrorLog $errorLog)
    {
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(ErrorLog::STATUSES)),
            'note' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ], [], ['status' => '처리 상태', 'note' => '처리 메모', 'assigned_to' => '담당자']);

        // 처리가 끝나지 않은 상태로 되돌릴 때, 같은 원인의 열린 건이 이미 있으면 그쪽으로 보낸다
        if (in_array($data['status'], ErrorLog::OPEN_STATUSES, true) && ! $errorLog->isOpen()) {
            $dup = ErrorLog::where('fingerprint', $errorLog->fingerprint)
                ->whereIn('status', ErrorLog::OPEN_STATUSES)->where('id', '!=', $errorLog->id)->first();
            if ($dup) {
                return redirect()->route('admin.errors.show', $dup)
                    ->with('error', '같은 원인의 처리 전 에러(#'.$dup->id.')가 이미 있어 그 건으로 이동했습니다.');
            }
        }

        $done = in_array($data['status'], ['resolved', 'ignored'], true);
        $errorLog->update([
            'status' => $data['status'],
            'assigned_to' => $data['assigned_to'] ?? null,
            'resolution_note' => $data['note'] ?? $errorLog->resolution_note,
            'resolved_at' => $done ? ($errorLog->resolved_at ?? now()) : null,
            'resolved_by' => $done ? ($errorLog->resolved_by ?? auth()->id()) : null,
            'status_changed_at' => now(),
        ]);

        return back()->with('status', '처리 상태를 "'.ErrorLog::STATUSES[$data['status']].'"(으)로 바꿨습니다.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|max:500',
            'ids.*' => 'integer',
            'action' => 'required|in:unresolved,in_progress,resolved,ignored,delete',
        ], ['ids.required' => '처리할 에러를 선택하세요.']);

        $query = ErrorLog::whereIn('id', $data['ids']);
        if ($data['action'] === 'delete') {
            $n = $query->delete();

            return back()->with('status', '선택한 에러 '.number_format($n).'건을 삭제했습니다.');
        }

        $done = in_array($data['action'], ['resolved', 'ignored'], true);
        $n = $query->where('status', '!=', $data['action'])->update([
            'status' => $data['action'],
            'resolved_at' => $done ? now() : null,
            'resolved_by' => $done ? auth()->id() : null,
            'status_changed_at' => now(),
        ]);

        return back()->with('status', '선택한 에러 '.number_format($n).'건을 "'.ErrorLog::STATUSES[$data['action']].'"(으)로 바꿨습니다.');
    }

    public function destroy(ErrorLog $errorLog)
    {
        $errorLog->delete();

        return redirect()->route('admin.errors')->with('status', '에러 #'.$errorLog->id.'를 삭제했습니다.');
    }

    /** 해결된 지 오래된 에러 삭제 */
    public function purge(Request $request)
    {
        $data = $request->validate(['days' => 'required|integer|min:7|max:3650'], [], ['days' => '보관 기간']);
        $before = Carbon::today()->subDays((int) $data['days']);
        $n = ErrorLog::whereIn('status', ['resolved', 'ignored'])->where('resolved_at', '<', $before)->delete();

        return back()->with('status', $before->format('Y-m-d').' 이전에 처리된 에러 '.number_format($n).'건을 삭제했습니다.');
    }
}
