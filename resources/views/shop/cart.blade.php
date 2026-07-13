@extends('layouts.app')
@section('title', '장바구니 · KOR SAFETY')

@section('content')
<div class="wrap">
    <div class="crumb"><a href="{{ route('home') }}">홈</a><span class="sep">/</span><span>장바구니</span></div>
    <div class="list-head" style="border:0;margin-bottom:8px"><div><h1>장바구니</h1></div></div>

    @if(count($items))
    <div class="cart-grid">
        <div class="cart-items">
            @foreach($items as $item)
                @php $p = $item['product']; @endphp
                <div class="cart-line">
                    <a href="{{ route('product.show', $p) }}" class="thumb"><img src="{{ asset($p->main_image) }}" alt="{{ $p->name }}" onerror="this.style.visibility='hidden'"></a>
                    <div>
                        @if($p->brand)<div class="cl-brand">{{ $p->brand }}</div>@endif
                        <a href="{{ route('product.show', $p) }}" class="cl-name">{{ $p->name }}</a>
                        <form action="{{ route('cart.update', $p) }}" method="post" style="display:inline-flex">
                            @csrf @method('PATCH')
                            <div class="qty">
                                <button type="button" data-step="down">−</button>
                                <input type="text" name="qty" value="{{ $item['qty'] }}" inputmode="numeric" data-autosubmit="1">
                                <button type="button" data-step="up">+</button>
                            </div>
                        </form>
                    </div>
                    <div class="cl-right">
                        <div class="cl-price">{{ number_format($item['line']) }}원</div>
                        <form action="{{ route('cart.remove', $p) }}" method="post">
                            @csrf @method('DELETE')
                            <button type="submit" class="link-remove">삭제</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <aside class="summary">
            <h3>주문 요약</h3>
            <div class="row"><span>상품금액</span><span>{{ number_format($subtotal) }}원</span></div>
            <div class="row"><span>배송비</span><span>{{ $subtotal >= 50000 ? '무료' : '3,000원' }}</span></div>
            <div class="row total"><span>결제예정금액</span><span>{{ number_format($subtotal + ($subtotal >= 50000 || $subtotal == 0 ? 0 : 3000)) }}원</span></div>
            <button class="btn btn-accent btn-lg btn-block" onclick="alert('데모 쇼핑몰입니다. 결제 기능은 준비 중입니다.')">주문하기</button>
            <a href="{{ route('home') }}" class="btn btn-ghost btn-block" style="margin-top:10px">계속 쇼핑하기</a>
            <p class="muted" style="font-size:12.5px;margin-top:16px;text-align:center">5만원 이상 구매 시 무료배송</p>
        </aside>
    </div>
    @else
    <div class="empty-state">
        <div class="ei"><svg viewBox="0 0 24 24" width="34" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M6 6L5 3H2"/></svg></div>
        <h2>장바구니가 비어 있습니다</h2>
        <p>현장에 필요한 안전용품을 담아보세요.</p>
        <a href="{{ route('home') }}" class="btn btn-accent">쇼핑하러 가기</a>
    </div>
    @endif
</div>
@endsection
