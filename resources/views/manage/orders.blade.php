@extends('manage.layout')
@section('title', '주문 내역')
@section('page', '주문 내역')
@section('crumb', '내 스토어 상품이 포함된 주문')

@section('content')
<div class="panel">
    <div class="panel-h"><div><h2>주문 상품 내역</h2><div class="sub">총 {{ number_format($items->total()) }}건</div></div></div>
    <table class="table">
        <thead><tr><th>주문번호</th><th>상품명</th><th>수량</th><th style="text-align:right">금액</th><th>주문상태</th><th>일시</th></tr></thead>
        <tbody>
        @forelse($items as $it)
            <tr>
                <td class="t-name">{{ $it->order->order_no ?? '-' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($it->product_name, 40) }}</td>
                <td>{{ $it->qty }}개</td>
                <td style="text-align:right;font-weight:800">{{ number_format($it->line_total) }}원</td>
                <td>@php $st=$it->order->status ?? 'paid'; $m=['paid'=>['ok','결제완료'],'shipped'=>['warn','배송중'],'done'=>['ok','배송완료'],'pending'=>['warn','대기'],'cancelled'=>['off','취소']][$st]??['ok',$st]; @endphp<span class="badge {{ $m[0] }}">{{ $m[1] }}</span></td>
                <td class="t-sub">{{ optional($it->created_at)->format('Y.m.d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">주문 내역이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $items->links('manage.pagination') }}
@endsection
