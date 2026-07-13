@extends('manage.layout')
@section('title', '본사 대시보드')
@section('page', '대시보드')
@section('crumb', '본사 · 매출 및 운영 현황')

@section('content')
@php $max = max(1, collect($chart)->max('value')); @endphp

<div class="tiles">
    <div class="tile">
        <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg> 플랫폼 총매출</div>
        <div class="val">{{ number_format($stats['platform_sales']) }}<span class="won">원</span></div>
        <div class="sub">전체 판매점 합산</div>
    </div>
    <div class="tile">
        <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg> 본사 직영 매출</div>
        <div class="val">{{ number_format($stats['hq_sales']) }}<span class="won">원</span></div>
        <div class="sub">본사 직영 상품 {{ number_format($stats['hq_products']) }}개</div>
    </div>
    <div class="tile">
        <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2l1.5 3h9L18 2M3 6h18l-1.5 12a2 2 0 0 1-2 1.8H6.5a2 2 0 0 1-2-1.8z"/></svg> 총 주문</div>
        <div class="val">{{ number_format($stats['orders']) }}<span class="won">건</span></div>
        <div class="sub">평균 객단가 {{ number_format($stats['aov']) }}원</div>
    </div>
    <div class="tile">
        <div class="lab"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="7" r="4"/><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/></svg> 입점 판매점</div>
        <div class="val">{{ number_format($stats['sellers']) }}<span class="won">곳</span></div>
        <div class="sub">승인대기 {{ $stats['pending'] }}곳 · 전체상품 {{ number_format($stats['products']) }}</div>
    </div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-h"><div><h2>일별 매출</h2><div class="sub">최근 14일 플랫폼 매출 추이</div></div></div>
        <div class="panel-b">
            <div class="chart">
                @foreach($chart as $c)
                    <div class="bar" title="{{ $c['date'] }} · {{ number_format($c['value']) }}원">
                        <div class="fill" style="height:{{ max(2, round($c['value']/$max*100)) }}%"></div>
                        <div class="d">{{ $c['date'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-h"><div><h2>매출 TOP 판매점</h2></div></div>
        <div class="panel-b" style="padding:8px 10px">
            <table class="table">
                <tbody>
                @forelse($topSellers as $r)
                    <tr>
                        <td><span class="t-name">{{ $r->seller->name ?? '-' }}</span>
                            @if($r->seller && $r->seller->is_hq)<span class="badge hq" style="margin-left:6px">본사</span>@endif</td>
                        <td style="text-align:right;font-weight:800">{{ number_format($r->sales) }}원</td>
                    </tr>
                @empty
                    <tr><td class="empty">매출 데이터가 없습니다.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-h"><div><h2>최근 주문</h2></div><a href="{{ route('manage.orders') }}" class="btn btn-sm">전체 보기</a></div>
    <table class="table">
        <thead><tr><th>주문번호</th><th>주문자</th><th>상태</th><th style="text-align:right">금액</th><th>일시</th></tr></thead>
        <tbody>
        @forelse($recentOrders as $o)
            <tr>
                <td class="t-name">{{ $o->order_no }}</td>
                <td>{{ $o->customer_name }}</td>
                <td>@php $m=['paid'=>['ok','결제완료'],'shipped'=>['warn','배송중'],'done'=>['ok','배송완료'],'pending'=>['warn','대기'],'cancelled'=>['off','취소']][$o->status]??['ok',$o->status]; @endphp<span class="badge {{ $m[0] }}">{{ $m[1] }}</span></td>
                <td style="text-align:right;font-weight:800">{{ number_format($o->total) }}원</td>
                <td class="t-sub">{{ $o->created_at->format('Y.m.d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">주문이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
