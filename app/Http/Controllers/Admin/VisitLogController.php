<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** 웹 방문 이력 — 검색어 · 상품 진입 조회와 집계 (본사 전용) */
class VisitLogController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->query('type');
        $q = trim((string) $request->query('q', ''));
        $ip = trim((string) $request->query('ip', ''));
        $from = $request->query('from') ?: now()->subDays(6)->toDateString();
        $to = $request->query('to') ?: now()->toDateString();

        $ranged = fn () => VisitLog::query()
            ->whereDate('created_at', '>=', $from)
            ->whereDate('created_at', '<=', $to);

        // 목록
        $list = $ranged()->with(['user', 'product'])->latest('id');
        if ($type && isset(VisitLog::TYPES[$type])) {
            $list->where('type', $type);
        }
        if ($ip !== '') {
            $list->where('ip_address', $ip);
        }
        if ($q !== '') {
            $list->where(fn ($w) => $w->where('keyword', 'like', "%$q%")
                ->orWhere('product_name', 'like', "%$q%")
                ->orWhere('ip_address', 'like', "%$q%"));
        }
        $logs = $list->paginate(30)->withQueryString();

        // 요약 (기간 기준 + 오늘)
        $today = fn () => VisitLog::whereDate('created_at', today());
        $stats = [
            'searches' => (clone $ranged())->where('type', 'search')->count(),
            'products' => (clone $ranged())->where('type', 'product')->count(),
            'visitors' => (clone $ranged())->distinct('session_id')->count('session_id'),
            'today_searches' => $today()->where('type', 'search')->count(),
            'today_products' => $today()->where('type', 'product')->count(),
            'today_visitors' => $today()->distinct('session_id')->count('session_id'),
            'no_result' => (clone $ranged())->where('type', 'search')->where('result_count', 0)->count(),
            'ips' => (clone $ranged())->distinct('ip_address')->count('ip_address'),
            'today_ips' => $today()->distinct('ip_address')->count('ip_address'),
        ];

        // IP별 방문 집계
        $topIps = (clone $ranged())
            ->selectRaw('ip_address, COUNT(*) c, SUM(type = "search") s, SUM(type = "product") p,
                         COUNT(DISTINCT session_id) sessions, MAX(created_at) last_at')
            ->whereNotNull('ip_address')->groupBy('ip_address')
            ->orderByDesc('c')->limit(12)->get();

        // 인기 검색어 / 많이 본 상품
        $topKeywords = (clone $ranged())->where('type', 'search')
            ->selectRaw('keyword, COUNT(*) c, MIN(result_count) min_result')
            ->whereNotNull('keyword')->groupBy('keyword')
            ->orderByDesc('c')->limit(12)->get();

        $topProducts = (clone $ranged())->where('type', 'product')
            ->selectRaw('product_id, MAX(product_name) product_name, COUNT(*) c, COUNT(DISTINCT session_id) uniq')
            ->whereNotNull('product_id')->groupBy('product_id')
            ->orderByDesc('c')->limit(12)->get();

        // 일자별 추이 (기간이 길면 최근 14일만)
        $daily = (clone $ranged())
            ->selectRaw('DATE(created_at) d, SUM(type = "search") s, SUM(type = "product") p')
            ->groupBy('d')->orderBy('d')->get()->take(-14);

        return view('admin.visits.index', compact('logs', 'stats', 'topKeywords', 'topProducts', 'topIps', 'daily', 'type', 'q', 'ip', 'from', 'to'));
    }

    /** 오래된 이력 삭제 (기본 90일 이전) */
    public function purge(Request $request)
    {
        $data = $request->validate(['days' => 'required|integer|min:7|max:3650'], [], ['days' => '보관 기간']);
        $before = Carbon::today()->subDays((int) $data['days']);
        $n = VisitLog::where('created_at', '<', $before)->delete();

        return back()->with('status', $before->format('Y-m-d').' 이전 방문 이력 '.number_format($n).'건을 삭제했습니다.');
    }
}
