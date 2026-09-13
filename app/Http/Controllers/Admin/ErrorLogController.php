<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** 서버 에러 관리 — 유형 · 상세 조회와 해결/미해결 상태 관리 (본사 전용) */
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
        $logs = $list->orderByDesc('last_seen_at')->orderByDesc('id')->paginate(30)->withQueryString();

        $byStatus = ErrorLog::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $stats = [
            'unresolved' => (int) ($byStatus['unresolved'] ?? 0),
            'resolved' => (int) ($byStatus['resolved'] ?? 0),
            'today' => ErrorLog::whereDate('last_seen_at', today())->count(),
            'today_new' => ErrorLog::whereDate('first_seen_at', today())->count(),
            'week_new' => ErrorLog::where('first_seen_at', '>=', today()->subDays(6))->count(),
        ];
        // 미해결 유형별 건수
        $typeCounts = ErrorLog::where('status', 'unresolved')
            ->selectRaw('type, COUNT(*) c')->groupBy('type')->pluck('c', 'type');

        return view('admin.errors.index', compact('logs', 'stats', 'typeCounts', 'status', 'type', 'source', 'q', 'from', 'to'));
    }

    public function show(ErrorLog $errorLog)
    {
        $errorLog->load(['user', 'resolver']);

        // 같은 원인으로 이전에 해결 처리했던 이력 (재발 확인용)
        $related = ErrorLog::where('fingerprint', $errorLog->fingerprint)
            ->where('id', '!=', $errorLog->id)
            ->with('resolver')->latest('id')->limit(10)->get();

        return view('admin.errors.show', ['log' => $errorLog, 'related' => $related]);
    }

    public function resolve(Request $request, ErrorLog $errorLog)
    {
        $data = $request->validate(['note' => 'nullable|string|max:2000'], [], ['note' => '처리 내용']);
        $errorLog->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
            'resolution_note' => $data['note'] ?? null,
        ]);

        return back()->with('status', '에러를 해결 처리했습니다.');
    }

    public function reopen(ErrorLog $errorLog)
    {
        // 같은 원인의 미해결 건이 이미 있으면 그쪽으로 모이므로 되돌리지 않는다
        $dup = ErrorLog::where('fingerprint', $errorLog->fingerprint)
            ->where('status', 'unresolved')->where('id', '!=', $errorLog->id)->first();
        if ($dup) {
            return redirect()->route('admin.errors.show', $dup)
                ->with('error', '같은 원인의 미해결 에러(#'.$dup->id.')가 이미 있어 그 건으로 이동했습니다.');
        }

        $errorLog->update(['status' => 'unresolved', 'resolved_at' => null, 'resolved_by' => null]);

        return back()->with('status', '에러를 미해결로 되돌렸습니다.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|max:500',
            'ids.*' => 'integer',
            'action' => 'required|in:resolve,delete',
        ], ['ids.required' => '처리할 에러를 선택하세요.']);

        $query = ErrorLog::whereIn('id', $data['ids']);
        if ($data['action'] === 'delete') {
            $n = $query->delete();

            return back()->with('status', '선택한 에러 '.number_format($n).'건을 삭제했습니다.');
        }

        $n = $query->where('status', 'unresolved')->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $request->user()->id,
        ]);

        return back()->with('status', '선택한 에러 '.number_format($n).'건을 해결 처리했습니다.');
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
        $n = ErrorLog::where('status', 'resolved')->where('resolved_at', '<', $before)->delete();

        return back()->with('status', $before->format('Y-m-d').' 이전에 해결된 에러 '.number_format($n).'건을 삭제했습니다.');
    }
}
