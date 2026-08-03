@extends('manage.layout')
@section('title', '방문 이력')
@section('page', '웹 방문 이력')
@section('crumb', '제품 검색어 · 상품 진입 기록')

@section('content')
@php
    $maxDaily = max(1, $daily->max(fn ($r) => (int) $r->s + (int) $r->p) ?: 1);
@endphp

{{-- 요약 --}}
<div class="tiles">
    <div class="tile">
        <div class="lab">방문자 (기간)</div>
        <div class="val">{{ number_format($stats['visitors']) }}<span class="won"> 명</span></div>
        <div class="sub">오늘 {{ number_format($stats['today_visitors']) }}명</div>
    </div>
    <div class="tile">
        <div class="lab">제품 검색</div>
        <div class="val">{{ number_format($stats['searches']) }}<span class="won"> 회</span></div>
        <div class="sub">오늘 {{ number_format($stats['today_searches']) }}회</div>
    </div>
    <div class="tile">
        <div class="lab">상품 진입</div>
        <div class="val">{{ number_format($stats['products']) }}<span class="won"> 회</span></div>
        <div class="sub">오늘 {{ number_format($stats['today_products']) }}회</div>
    </div>
    <div class="tile">
        <div class="lab">접속 IP</div>
        <div class="val">{{ number_format($stats['ips']) }}<span class="won"> 개</span></div>
        <div class="sub">오늘 {{ number_format($stats['today_ips']) }}개 · 결과 0건 검색 {{ number_format($stats['no_result']) }}회</div>
    </div>
</div>

{{-- 기간 · 검색 --}}
<div class="panel">
    <div class="panel-b">
        <form method="get" style="display:flex;flex-wrap:nowrap;gap:8px;align-items:center;width:100%">
            <input class="input" style="height:38px;flex:1 1 0;min-width:110px" name="q" value="{{ $q }}" placeholder="검색어 · 상품명">
            <input class="input" style="height:38px;flex:0 0 140px" name="ip" value="{{ $ip }}" placeholder="IP 주소">
            <select class="input" style="height:38px;flex:0 0 118px" name="type">
                <option value="">전체 구분</option>
                @foreach(\App\Models\VisitLog::TYPES as $k => $v)
                    <option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <span class="t-sub" style="flex:0 0 auto">기간</span>
            <input class="input" style="height:38px;flex:0 0 138px" type="date" name="from" value="{{ $from }}">
            <span class="t-sub" style="flex:0 0 auto">~</span>
            <input class="input" style="height:38px;flex:0 0 138px" type="date" name="to" value="{{ $to }}">
            <button class="btn btn-sm btn-accent" style="flex:0 0 auto">조회</button>
            <a href="{{ route('admin.visits') }}" class="btn btn-sm" style="flex:0 0 auto">초기화</a>
        </form>
    </div>
</div>

{{-- 일자별 추이 --}}
@if($daily->count())
<div class="panel">
    <div class="panel-h"><div><h2>일자별 추이</h2><div class="sub">검색 + 상품 진입</div></div></div>
    <div class="panel-b">
        <div class="chart">
            @foreach($daily as $d)
                @php $sum = (int) $d->s + (int) $d->p; @endphp
                <div class="bar" title="{{ $d->d }} · 검색 {{ $d->s }} · 진입 {{ $d->p }}">
                    <span class="t-sub" style="font-size:10.5px">{{ number_format($sum) }}</span>
                    <div class="fill" style="height:{{ max(3, round($sum / $maxDaily * 100)) }}%"></div>
                    <div class="d">{{ \Illuminate\Support\Str::substr($d->d, 5) }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="grid-2">
    {{-- 인기 검색어 --}}
    <div class="panel">
        <div class="panel-h"><div><h2>인기 검색어</h2><div class="sub">기간 내 상위 12건</div></div></div>
        <table class="table">
            <thead><tr><th style="width:44px">순위</th><th>검색어</th><th style="width:80px">검색 수</th><th style="width:90px">결과</th><th style="width:70px">상품</th></tr></thead>
            <tbody>
            @forelse($topKeywords as $i => $k)
                <tr>
                    <td class="t-sub">{{ $i + 1 }}</td>
                    <td class="t-name">{{ $k->keyword }}</td>
                    <td>{{ number_format($k->c) }}회</td>
                    <td>@if((int) $k->min_result === 0)<span class="badge off">0건 있음</span>@else<span class="t-sub">있음</span>@endif</td>
                    <td><a class="btn btn-sm" href="{{ route('search', ['q' => $k->keyword]) }}" target="_blank">보기</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">기간 내 검색 기록이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- 많이 본 상품 --}}
    <div class="panel">
        <div class="panel-h"><div><h2>많이 본 상품</h2><div class="sub">기간 내 상위 12건</div></div></div>
        <table class="table">
            <thead><tr><th style="width:44px">순위</th><th>상품</th><th style="width:70px">조회</th><th style="width:70px">방문자</th></tr></thead>
            <tbody>
            @forelse($topProducts as $i => $p)
                <tr>
                    <td class="t-sub">{{ $i + 1 }}</td>
                    <td>
                        <a class="t-name" href="{{ route('product.show', $p->product_id) }}" target="_blank">{{ \Illuminate\Support\Str::limit($p->product_name, 32) }}</a>
                    </td>
                    <td>{{ number_format($p->c) }}회</td>
                    <td class="t-sub">{{ number_format($p->uniq) }}명</td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty">기간 내 상품 진입 기록이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- IP별 방문 --}}
<div class="panel">
    <div class="panel-h"><div><h2>IP별 방문</h2><div class="sub">기간 내 상위 12개 · IP를 누르면 해당 IP의 이력만 봅니다</div></div></div>
    <table class="table">
        <thead><tr>
            <th style="width:44px">순위</th><th style="width:180px">IP 주소</th>
            <th style="width:90px">총 요청</th><th style="width:90px">검색</th><th style="width:90px">상품 진입</th>
            <th style="width:90px">세션</th><th>마지막 방문</th><th style="width:80px">보기</th>
        </tr></thead>
        <tbody>
        @forelse($topIps as $i => $row)
            <tr>
                <td class="t-sub">{{ $i + 1 }}</td>
                <td class="t-name">{{ $row->ip_address }}</td>
                <td>{{ number_format($row->c) }}회</td>
                <td class="t-sub">{{ number_format($row->s) }}회</td>
                <td class="t-sub">{{ number_format($row->p) }}회</td>
                <td class="t-sub">{{ number_format($row->sessions) }}개</td>
                <td class="t-sub">{{ \Illuminate\Support\Carbon::parse($row->last_at)->format('Y.m.d H:i') }}</td>
                <td>
                    <a class="btn btn-sm" href="{{ route('admin.visits', array_merge(request()->query(), ['ip' => $row->ip_address, 'page' => null])) }}">이력</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">기간 내 방문 기록이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- 원본 로그 --}}
<div class="panel">
    <div class="panel-h">
        <div><h2>방문 로그</h2>
            <div class="sub">총 {{ number_format($logs->total()) }}건
                @if($ip !== '') · IP <b>{{ $ip }}</b> 필터 중 <a href="{{ route('admin.visits', array_merge(request()->query(), ['ip' => null, 'page' => null])) }}">해제</a>@endif
            </div>
        </div>
        <form action="{{ route('admin.visits.purge') }}" method="post" style="display:flex;gap:8px;align-items:center"
              onsubmit="return confirm('설정한 기간보다 오래된 방문 이력을 삭제합니다. 진행할까요?')">@csrf
            <span class="t-sub">보관 기간</span>
            <select class="input" name="days" style="height:34px;width:96px;font-size:12.5px">
                <option value="30">30일</option>
                <option value="90" selected>90일</option>
                <option value="180">180일</option>
                <option value="365">365일</option>
            </select>
            <button class="btn btn-sm btn-danger">오래된 이력 삭제</button>
        </form>
    </div>
    <table class="table">
        <thead><tr>
            <th style="width:130px">일시</th>
            <th style="width:86px">구분</th>
            <th>내용</th>
            <th style="width:80px">결과</th>
            <th style="width:110px">회원</th>
            <th style="width:110px">IP</th>
            <th style="width:150px">유입 경로</th>
        </tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td class="t-sub">{{ optional($log->created_at)->format('Y.m.d H:i:s') }}</td>
                <td><span class="badge {{ $log->type === 'search' ? 'hq' : 'ok' }}">{{ $log->type_label }}</span></td>
                <td>
                    @if($log->type === 'search')
                        <a class="t-name" href="{{ route('search', ['q' => $log->keyword]) }}" target="_blank">{{ $log->keyword }}</a>
                    @else
                        <a class="t-name" href="{{ route('product.show', $log->product_id) }}" target="_blank">{{ \Illuminate\Support\Str::limit($log->product_name, 40) }}</a>
                    @endif
                </td>
                <td class="t-sub">
                    @if($log->type === 'search')
                        @if((int) $log->result_count === 0)<span class="badge off">0건</span>@else{{ number_format($log->result_count) }}건@endif
                    @else
                        -
                    @endif
                </td>
                <td class="t-sub">{{ $log->user->name ?? '비회원' }}</td>
                <td class="t-sub">
                    <a href="{{ route('admin.visits', array_merge(request()->query(), ['ip' => $log->ip_address, 'page' => null])) }}"
                       title="이 IP의 이력만 보기">{{ $log->ip_address }}</a>
                </td>
                <td class="t-sub" title="{{ $log->referer }}">{{ $log->referer ? \Illuminate\Support\Str::limit(preg_replace('#^https?://#', '', $log->referer), 24) : '직접 유입' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">조건에 맞는 방문 이력이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $logs->links('manage.pagination') }}
@endsection
