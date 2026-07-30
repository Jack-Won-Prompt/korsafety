@extends('manage.layout')
@section('title', '세금계산서')
@section('page', '세금계산서')
@section('crumb', '발행 이력 · 상태 관리')

@section('content')
<div class="stat-row" style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:18px">
    <div class="panel" style="padding:16px 18px"><div class="t-sub">발행완료</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['issued']) }}건</div></div>
    <div class="panel" style="padding:16px 18px"><div class="t-sub">취소</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['cancelled']) }}건</div></div>
    <div class="panel" style="padding:16px 18px"><div class="t-sub">실패</div><div style="font-size:22px;font-weight:800">{{ number_format($stats['failed']) }}건</div></div>
    <div class="panel" style="padding:16px 18px"><div class="t-sub">발행 합계금액</div><div style="font-size:22px;font-weight:800;color:#d84315">{{ number_format($stats['amount']) }}원</div></div>
</div>

<div class="panel">
    <div class="panel-h">
        <div><h2>세금계산서 이력</h2>@if(config('popbill.simulate'))<div class="sub">⚠ 시뮬레이트 모드 — 실제 국세청 발행 아님</div>@endif</div>
        <form method="get" style="display:flex;gap:8px">
            <select class="input" style="height:38px" name="status" onchange="this.form.submit()">
                <option value="">전체 상태</option>
                @foreach(['issued'=>'발행완료','simulated'=>'시뮬레이트','cancelled'=>'취소','failed'=>'실패'] as $k=>$v)
                    <option value="{{ $k }}" @selected($status===$k)>{{ $v }}</option>
                @endforeach
            </select>
            <input class="input" style="height:38px;width:200px" name="q" value="{{ $q }}" placeholder="상호·사업자번호·주문번호">
            <button class="btn btn-sm">검색</button>
        </form>
    </div>
    <table class="table">
        <thead><tr><th>관리번호</th><th>주문</th><th>공급받는자</th><th>유형</th><th style="text-align:right">합계</th><th>상태</th><th>승인번호</th><th>일시</th></tr></thead>
        <tbody>
        @forelse($invoices as $inv)
            <tr>
                <td class="t-sub">{{ $inv->mgt_key }}</td>
                <td>@if($inv->order)<a href="{{ route('admin.orders.show', $inv->order) }}" class="t-name">{{ $inv->order->order_no }}</a>@else-@endif</td>
                <td>{{ $inv->receiver_corp_name }}<div class="t-sub">{{ $inv->receiver_corp_num }}</div></td>
                <td>{{ $inv->kind_label }}</td>
                <td style="text-align:right;font-weight:700">{{ number_format($inv->total_amount) }}원</td>
                <td><span class="badge {{ $inv->is_active?'ok':($inv->status=='failed'?'off':'warn') }}">{{ $inv->status_label }}</span></td>
                <td class="t-sub">{{ $inv->nts_confirm_num ?: '-' }}</td>
                <td class="t-sub">{{ optional($inv->issued_at ?: $inv->created_at)->format('Y.m.d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">발행된 세금계산서가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $invoices->links('manage.pagination') }}
@endsection
