@extends('manage.layout')
@section('title', '주문 상세')
@section('page', '주문 ' . $order->order_no)
@section('crumb', '협력사 등록 주문 상세')

@section('content')
<div class="grid-2">
    <div class="panel">
        <div class="panel-h"><h2>주문 상품</h2></div>
        <table class="table">
            <thead><tr><th>상품명</th><th style="text-align:right">단가</th><th>수량</th><th style="text-align:right">합계</th></tr></thead>
            <tbody>
            @foreach($order->items as $it)
                <tr><td>{{ $it->product_name }}</td><td style="text-align:right">{{ number_format($it->price) }}원</td><td>{{ $it->qty }}</td><td style="text-align:right;font-weight:700">{{ number_format($it->line_total) }}원</td></tr>
            @endforeach
            <tr><td colspan="3" style="text-align:right;font-weight:800">판매금액 합계</td><td style="text-align:right;font-weight:900">{{ number_format($order->total) }}원</td></tr>
            <tr><td colspan="3" style="text-align:right;font-weight:800;color:var(--accent)">커미션 ({{ rtrim(rtrim($order->commission_rate,'0'),'.') }}%)</td><td style="text-align:right;font-weight:900;color:var(--accent)">+{{ number_format($order->commission_amount) }}원</td></tr>
            </tbody>
        </table>
    </div>

    <div>
        <div class="panel">
            <div class="panel-h"><h2>주문 정보</h2></div>
            <div class="panel-b">
                <dl style="display:grid;gap:12px;font-size:14px">
                    <div style="display:flex;justify-content:space-between"><dt class="muted">거래처</dt><dd style="margin:0;font-weight:700">{{ $order->client->name ?? '-' }} @if($order->client)<span class="badge {{ ['company'=>'hq','hospital'=>'ok','etc'=>'warn'][$order->client->type] }}" style="margin-left:6px">{{ $order->client->type_label }}</span>@endif</dd></div>
                    <div style="display:flex;justify-content:space-between"><dt class="muted">담당자</dt><dd style="margin:0">{{ $order->client->contact_name ?? '-' }}</dd></div>
                    <div style="display:flex;justify-content:space-between"><dt class="muted">등록일시</dt><dd style="margin:0">{{ $order->created_at->format('Y.m.d H:i') }}</dd></div>
                    <div style="display:flex;justify-content:space-between"><dt class="muted">커미션 상태</dt><dd style="margin:0">@php $cl=$order->commission_status_label; $cb=$cl==='지급완료'?'ok':($cl==='지급대기'?'warn':'off'); @endphp<span class="badge {{ $cb }}">{{ $cl }}</span></dd></div>
                </dl>
            </div>
        </div>
        <div class="panel">
            <div class="panel-h"><h2>주문 상태 변경</h2></div>
            <div class="panel-b">
                <form action="{{ route('agent.orders.status', $order) }}" method="post" style="display:flex;gap:8px">@csrf
                    <select class="select" name="status">
                        @foreach(['pending'=>'주문 접수','paid'=>'결제 완료','shipped'=>'배송중','done'=>'배송완료','cancelled'=>'취소'] as $k=>$v)
                            <option value="{{ $k }}" {{ $order->status===$k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-accent">변경</button>
                </form>
                <div class="hint" style="margin-top:10px">결제완료 이상 상태에서 커미션이 적립되며, 지급은 본사가 처리합니다.</div>
            </div>
        </div>
        <a href="{{ route('agent.orders.index') }}" class="btn" style="width:100%">← 주문 목록</a>
    </div>
</div>
@endsection
