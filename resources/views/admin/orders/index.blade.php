@extends('manage.layout')
@section('title', '주문 관리')
@section('page', '주문 관리')
@section('crumb', '거래명세서 · 세금계산서 발행')

@section('content')
<div class="panel">
    <div class="panel-h">
        <div><h2>전체 주문</h2><div class="sub">총 {{ number_format($orders->total()) }}건</div></div>
        <form method="get" style="display:flex;gap:8px">
            <input class="input" style="height:38px;width:220px" name="q" value="{{ $q }}" placeholder="주문번호·고객명·연락처">
            <button class="btn btn-sm">검색</button>
        </form>
    </div>
    <table class="table">
        <thead><tr><th>주문번호</th><th>고객</th><th style="text-align:right">금액</th><th>상태</th><th>명세서/계산서</th><th>일시</th><th style="width:80px">관리</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td class="t-name">{{ $o->order_no }}</td>
                <td>{{ $o->customer_name ?: '-' }}<div class="t-sub">{{ $o->customer_phone }}</div></td>
                <td style="text-align:right;font-weight:800">{{ number_format($o->total) }}원</td>
                <td><span class="badge {{ in_array($o->status,['paid','done'])?'ok':($o->status=='cancelled'?'off':'warn') }}">{{ $o->status_label }}</span></td>
                <td>
                    @if($o->statements->count())<span class="badge ok" title="거래명세서 발행 이력">명세 {{ $o->statements->count() }}</span>@endif
                    @php $ti = $o->taxInvoices->firstWhere('is_active', true); @endphp
                    @if($ti)<span class="badge hq" title="세금계산서">계산서</span>@endif
                    @if(!$o->statements->count() && !$ti)<span class="t-sub">-</span>@endif
                </td>
                <td class="t-sub">{{ optional($o->created_at)->format('Y.m.d H:i') }}</td>
                <td><a href="{{ route('admin.orders.show', $o) }}" class="btn btn-sm btn-accent">상세</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">주문이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links('manage.pagination') }}
@endsection
