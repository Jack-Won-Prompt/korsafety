@extends('manage.layout')
@section('title', '서버 에러')
@section('page', '서버 에러 관리')
@section('crumb', '서버에서 발생한 에러의 유형 · 상세 · 처리 상태')

@push('styles')
<style>
    .err-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
    .err-tabs a{padding:7px 14px;border-radius:999px;border:1px solid #e3e6ee;background:#fff;font-size:13px;font-weight:700;color:#555;text-decoration:none}
    .err-tabs a.on{background:#1b2130;border-color:#1b2130;color:#fff}
    .err-tabs a b{margin-left:4px;font-weight:800}
    .err-types{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
    .err-types a{font-size:12px;padding:4px 10px;border-radius:999px;background:#f3f4f8;color:#444;text-decoration:none}
    .err-types a.on{background:#eef1ff;color:#3646c7;font-weight:800}
    .err-msg{display:block;font-size:13px;color:#1b2130;word-break:break-all;line-height:1.45}
    .err-loc{font-family:ui-monospace,Consolas,monospace;font-size:11.5px;color:#8a90a0;word-break:break-all}
    .err-hits{font-weight:800}
    .err-hits.many{color:#c11c0f}
</style>
@endpush

@section('content')
@php
    $tabUrl = fn ($s) => route('admin.errors', array_merge(request()->except(['page', 'status']), ['status' => $s]));
    $typeUrl = fn ($t) => route('admin.errors', array_merge(request()->except(['page', 'type']), ['type' => $t]));
    $typeBadge = ['database' => 'off', 'php' => 'alert', 'http' => 'warn', 'external' => 'hq', 'application' => 'warn'];
@endphp

{{-- 요약 --}}
<div class="tiles">
    <div class="tile">
        <div class="lab">미해결</div>
        <div class="val" style="{{ $stats['unresolved'] ? 'color:#c11c0f' : '' }}">{{ number_format($stats['unresolved']) }}<span class="won"> 건</span></div>
        <div class="sub">처리가 필요한 에러</div>
    </div>
    <div class="tile">
        <div class="lab">오늘 발생</div>
        <div class="val">{{ number_format($stats['today']) }}<span class="won"> 건</span></div>
        <div class="sub">그중 새로 생긴 에러 {{ number_format($stats['today_new']) }}건</div>
    </div>
    <div class="tile">
        <div class="lab">최근 7일 신규</div>
        <div class="val">{{ number_format($stats['week_new']) }}<span class="won"> 건</span></div>
        <div class="sub">처음 발생 기준</div>
    </div>
    <div class="tile">
        <div class="lab">해결 완료</div>
        <div class="val">{{ number_format($stats['resolved']) }}<span class="won"> 건</span></div>
        <div class="sub">누적</div>
    </div>
</div>

<div class="err-tabs">
    <a href="{{ $tabUrl('unresolved') }}" class="{{ $status === 'unresolved' ? 'on' : '' }}">미해결<b>{{ number_format($stats['unresolved']) }}</b></a>
    <a href="{{ $tabUrl('resolved') }}" class="{{ $status === 'resolved' ? 'on' : '' }}">해결<b>{{ number_format($stats['resolved']) }}</b></a>
    <a href="{{ $tabUrl('all') }}" class="{{ $status === 'all' ? 'on' : '' }}">전체<b>{{ number_format($stats['unresolved'] + $stats['resolved']) }}</b></a>
</div>

{{-- 검색 --}}
<div class="panel">
    <div class="panel-b">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;width:100%">
            <input type="hidden" name="status" value="{{ $status }}">
            <input class="input" style="height:38px;flex:1 1 220px;min-width:160px" name="q" value="{{ $q }}" placeholder="에러 메시지 · 예외 클래스 · 파일 · URL">
            <select class="input" style="height:38px;flex:0 0 130px" name="type">
                <option value="">전체 유형</option>
                @foreach(\App\Models\ErrorLog::TYPES as $k => $v)
                    <option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <select class="input" style="height:38px;flex:0 0 110px" name="source">
                <option value="">전체 출처</option>
                @foreach(\App\Models\ErrorLog::SOURCES as $k => $v)
                    <option value="{{ $k }}" @selected($source === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <span class="t-sub">최근 발생</span>
            <input class="input" style="height:38px;flex:0 0 138px" type="date" name="from" value="{{ $from }}">
            <span class="t-sub">~</span>
            <input class="input" style="height:38px;flex:0 0 138px" type="date" name="to" value="{{ $to }}">
            <button class="btn btn-sm btn-accent">조회</button>
            <a href="{{ route('admin.errors') }}" class="btn btn-sm">초기화</a>
        </form>
        @if($typeCounts->count())
            <div class="err-types">
                <span class="t-sub" style="align-self:center">미해결 유형별</span>
                @foreach(\App\Models\ErrorLog::TYPES as $k => $v)
                    @if(!empty($typeCounts[$k]))
                        <a href="{{ $typeUrl($type === $k ? null : $k) }}" class="{{ $type === $k ? 'on' : '' }}">{{ $v }} {{ number_format($typeCounts[$k]) }}</a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- 목록 --}}
<div class="panel">
    <div class="panel-h">
        <div><h2>에러 목록</h2><div class="sub">총 {{ number_format($logs->total()) }}건 · 같은 원인의 에러는 한 건으로 묶어 발생 횟수를 셉니다</div></div>
        <form action="{{ route('admin.errors.purge') }}" method="post" style="display:flex;gap:8px;align-items:center"
              onsubmit="return confirm('설정한 기간보다 오래전에 해결된 에러를 삭제합니다. 진행할까요?')">@csrf
            <span class="t-sub">해결 후 보관</span>
            <select class="input" name="days" style="height:34px;width:96px;font-size:12.5px">
                <option value="30">30일</option>
                <option value="90" selected>90일</option>
                <option value="180">180일</option>
                <option value="365">365일</option>
            </select>
            <button class="btn btn-sm btn-danger">오래된 해결 건 삭제</button>
        </form>
    </div>

    <form id="bulk" action="{{ route('admin.errors.bulk') }}" method="post">@csrf
        <div style="display:flex;gap:8px;align-items:center;padding:10px 18px;border-bottom:1px solid #eef0f4">
            <span class="t-sub" id="bulk-count">선택 0건</span>
            <button class="btn btn-sm" name="action" value="resolve" onclick="return confirm('선택한 에러를 해결 처리할까요?')">선택 해결 처리</button>
            <button class="btn btn-sm btn-danger" name="action" value="delete" onclick="return confirm('선택한 에러를 삭제할까요? 되돌릴 수 없습니다.')">선택 삭제</button>
        </div>
    </form>

    <table class="table">
        <thead><tr>
            <th style="width:34px"><input type="checkbox" id="check-all" title="전체 선택"></th>
            <th style="width:70px">상태</th>
            <th style="width:100px">유형</th>
            <th>에러 내용</th>
            <th style="width:76px">발생</th>
            <th style="width:130px">최근 발생</th>
            <th style="width:200px">요청</th>
            <th style="width:64px"></th>
        </tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td><input type="checkbox" name="ids[]" value="{{ $log->id }}" form="bulk" class="row-check"></td>
                <td><span class="badge {{ $log->isResolved() ? 'ok' : 'off' }}">{{ $log->status_label }}</span></td>
                <td><span class="badge {{ $typeBadge[$log->type] ?? 'warn' }}">{{ $log->type_label }}</span></td>
                <td>
                    <a class="err-msg" href="{{ route('admin.errors.show', $log) }}">
                        <b>{{ $log->short_class }}</b> · {{ \Illuminate\Support\Str::limit($log->message, 150) }}
                    </a>
                    <div class="err-loc">{{ $log->file }}{{ $log->line ? ':'.$log->line : '' }}</div>
                </td>
                <td><span class="err-hits {{ $log->occurrences >= 10 ? 'many' : '' }}">{{ number_format($log->occurrences) }}회</span></td>
                <td class="t-sub">
                    {{ optional($log->last_seen_at)->format('Y.m.d H:i') }}
                    @if($log->occurrences > 1)<br><span style="font-size:11px">최초 {{ optional($log->first_seen_at)->format('m.d H:i') }}</span>@endif
                </td>
                <td class="t-sub" title="{{ $log->url }}">
                    <span class="badge hq" style="padding:2px 7px;font-size:10.5px">{{ $log->source_label }}</span>
                    @if($log->method && $log->method !== 'CLI'){{ $log->method }}@endif
                    <div class="err-loc">{{ \Illuminate\Support\Str::limit(preg_replace('#^https?://[^/]+#', '', (string) $log->url), 40) ?: '-' }}</div>
                </td>
                <td><a class="btn btn-sm" href="{{ route('admin.errors.show', $log) }}">상세</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">
                @if($status === 'unresolved' && $q === '' && !$type && !$source && !$from && !$to)
                    미해결 에러가 없습니다.
                @else
                    조건에 맞는 에러가 없습니다.
                @endif
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $logs->links('manage.pagination') }}

@push('scripts')
<script>
(function(){
    var all = document.getElementById('check-all');
    var rows = document.querySelectorAll('.row-check');
    var label = document.getElementById('bulk-count');
    function sync(){
        var n = document.querySelectorAll('.row-check:checked').length;
        label.textContent = '선택 ' + n + '건';
        all.checked = n > 0 && n === rows.length;
    }
    all.addEventListener('change', function(){ rows.forEach(function(r){ r.checked = all.checked; }); sync(); });
    rows.forEach(function(r){ r.addEventListener('change', sync); });
})();
</script>
@endpush
@endsection
