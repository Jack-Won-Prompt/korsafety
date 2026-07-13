@extends('manage.layout')
@section('title', '주문 관리')
@section('page', '주문 관리')
@section('crumb', '협력사 등록 주문 및 커미션')
@section('actions')
    <a href="{{ route('agent.orders.create') }}" class="btn btn-accent btn-sm">+ 주문 등록</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-h"><div><h2>등록 주문</h2><div class="sub">총 {{ number_format($orders->total()) }}건</div></div></div>
    <table class="table">
        <thead><tr><th>주문번호</th><th>거래처</th><th style="text-align:right">판매금액</th><th style="text-align:right">커미션</th><th>주문상태</th><th>커미션상태</th><th>일시</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td><a href="{{ route('agent.orders.show',$o) }}" class="t-name">{{ $o->order_no }}</a></td>
                <td>{{ $o->client->name ?? $o->customer_name }}</td>
                <td style="text-align:right">{{ number_format($o->total) }}원</td>
                <td style="text-align:right;font-weight:800;color:var(--accent)">+{{ number_format($o->commission_amount) }}원</td>
                <td>@php $m=['pending'=>['warn','접수'],'paid'=>['ok','결제완료'],'shipped'=>['warn','배송중'],'done'=>['ok','완료'],'cancelled'=>['off','취소']][$o->status]??['ok',$o->status]; @endphp<span class="badge {{ $m[0] }}">{{ $m[1] }}</span></td>
                <td>@php $cl=$o->commission_status_label; $cb=$cl==='지급완료'?'ok':($cl==='지급대기'?'warn':'off'); @endphp<span class="badge {{ $cb }}">{{ $cl }}</span></td>
                <td class="t-sub">{{ $o->created_at->format('Y.m.d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">등록된 주문이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links('manage.pagination') }}
@endsection
