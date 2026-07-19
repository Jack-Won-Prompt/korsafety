@extends('manage.layout')
@section('title', '캐쉬백 정산')
@section('page', '캐쉬백 정산')
@section('crumb', '구매 대행자 캐쉬백 적립 및 지급 처리')

@section('content')
<div class="tiles c3">
    <div class="tile"><div class="lab">누적 적립 캐쉬백</div><div class="val">{{ number_format($summary['accrued']) }}<span class="won">원</span></div><div class="sub">결제완료 주문 기준</div></div>
    <div class="tile"><div class="lab">지급 대기</div><div class="val" style="color:var(--accent)">{{ number_format($summary['pending']) }}<span class="won">원</span></div></div>
    <div class="tile"><div class="lab">지급 완료</div><div class="val">{{ number_format($summary['paid']) }}<span class="won">원</span></div></div>
</div>

<div class="panel">
    <div class="panel-h">
        <div><h2>캐쉬백 내역</h2></div>
        <div style="display:flex;gap:6px">
            @foreach(['pending'=>'지급대기','paid'=>'지급완료','all'=>'전체'] as $k=>$v)
                <a href="{{ route('admin.cashbacks', ['f'=>$k]) }}" class="btn btn-sm {{ $filter===$k ? 'btn-ink' : '' }}">{{ $v }}</a>
            @endforeach
        </div>
    </div>
    <table class="table">
        <thead><tr><th>주문번호</th><th>구매 대행자</th><th>구매자(소매처)</th><th style="text-align:right">주문금액</th><th>캐쉬백율</th><th style="text-align:right">캐쉬백</th><th>상태</th><th style="width:110px">정산</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td class="t-name">{{ $o->order_no }}</td>
                <td>{{ $o->purchaser->name ?? '-' }}</td>
                <td class="t-sub">{{ $o->buyer->shop_name ?? $o->customer_name }}</td>
                <td style="text-align:right">{{ number_format($o->total) }}원</td>
                <td>{{ rtrim(rtrim($o->cashback_rate,'0'),'.') }}%</td>
                <td style="text-align:right;font-weight:800;color:var(--accent)">{{ number_format($o->cashback_amount) }}원</td>
                <td>@if($o->cashback_paid_at)<span class="badge ok">지급완료</span><div class="t-sub">{{ $o->cashback_paid_at->format('m/d') }}</div>@else<span class="badge warn">지급대기</span>@endif</td>
                <td>
                    @if(!$o->cashback_paid_at)
                        <form action="{{ route('admin.cashbacks.pay', $o) }}" method="post" onsubmit="return confirm('{{ number_format($o->cashback_amount) }}원을 지급 처리하시겠습니까?')">@csrf<button class="btn btn-sm btn-accent">지급처리</button></form>
                    @else
                        <span class="t-sub">완료</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">해당 내역이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links('manage.pagination') }}
@endsection
