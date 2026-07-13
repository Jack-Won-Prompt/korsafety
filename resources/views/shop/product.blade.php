@extends('layouts.app')
@section('title', $product->name . ' · KOR SAFETY')
@section('meta_desc', $product->name)

@php
    $gallery = $product->galleryImages;
    $detailImgs = $product->detailImages;
    $hero = $product->main_image ?? ($gallery->first()->path ?? null);
    $final = $product->final_price;
@endphp

@section('content')
<div class="wrap">
    <div class="crumb">
        <a href="{{ route('home') }}">홈</a><span class="sep">/</span>
        @if($product->category)
            <a href="{{ route('category.show', $product->category) }}">{{ $product->category->name }}</a><span class="sep">/</span>
        @endif
        <span>{{ \Illuminate\Support\Str::limit($product->name, 30) }}</span>
    </div>

    <div class="pd">
        <div class="pd-gallery">
            <div class="pd-main">
                <img id="pd-main-img" src="{{ asset($hero) }}" alt="{{ $product->name }}" onerror="this.style.visibility='hidden'">
            </div>
            @if($gallery->count() > 1 || $product->main_image)
            <div class="pd-thumbs">
                @if($product->main_image)
                    <button class="active"><img src="{{ asset($product->main_image) }}" alt=""></button>
                @endif
                @foreach($gallery as $img)
                    @continue($img->path === $product->main_image)
                    <button><img src="{{ asset($img->path) }}" alt="" onerror="this.closest('button').style.display='none'"></button>
                @endforeach
            </div>
            @endif
        </div>

        <div class="pd-buy">
            @if($product->brand)<div class="pd-brand">{{ $product->brand }}</div>@endif
            <h1 class="pd-title">{{ $product->name }}</h1>

            <div class="pd-price-box">
                <div class="pd-price">
                    @if($final)
                        @if($product->has_discount)<span class="off">{{ $product->discount_percent }}%</span>@endif
                        <span class="now">{{ number_format($final) }}<span class="won">원</span></span>
                        @if($product->has_discount)<span class="was">{{ number_format($product->price) }}원</span>@endif
                    @else
                        <span class="ask">가격문의</span>
                    @endif
                </div>
            </div>

            <dl class="pd-meta">
                <div class="row"><dt>상품코드</dt><dd>YW-{{ $product->external_no }}</dd></div>
                <div class="row"><dt>카테고리</dt><dd>{{ $product->category->name ?? '-' }}</dd></div>
                <div class="row"><dt>배송</dt><dd>택배 · 오후 2시 이전 주문 당일출고</dd></div>
                <div class="row"><dt>인증</dt><dd>KCs 안전인증 정품</dd></div>
            </dl>

            <form id="pd-add-form" action="{{ route('cart.add', $product) }}" method="post" data-ajax="1">
                @csrf
                <div style="display:flex;align-items:center;gap:14px;margin:22px 0 4px">
                    <span style="font-weight:700;font-size:14px">수량</span>
                    <div class="qty">
                        <button type="button" data-step="down" aria-label="감소">−</button>
                        <input type="text" name="qty" value="1" inputmode="numeric" aria-label="수량">
                        <button type="button" data-step="up" aria-label="증가">+</button>
                    </div>
                </div>
                <div class="pd-actions">
                    <button type="submit" class="btn btn-accent btn-lg btn-block">장바구니 담기</button>
                    <a href="{{ route('cart.index') }}" class="btn btn-primary btn-lg btn-block">바로 구매</a>
                </div>
            </form>

            <div class="pd-trust">
                <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/></svg> 안전인증 정품</span>
                <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h13v10H3z"/><path d="M16 10h4l1 3v4h-5z"/></svg> 당일출고</span>
                <span class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16v12H4z"/></svg> 대량구매 견적</span>
            </div>
        </div>
    </div>

    <div class="pd-detail">
        <div class="tabs">
            <span class="t active">상세정보</span>
        </div>

        @if($detailImgs->count())
            <div class="pd-detail-imgs">
                @foreach($detailImgs as $img)
                    <img src="{{ asset($img->path) }}" alt="{{ $product->name }} 상세 이미지" loading="lazy" onerror="this.style.display='none'">
                @endforeach
            </div>
        @endif

        <div class="pd-spec">
            <h3>상품 정보</h3>
            <dl>
                <div><dt>상품명</dt><dd>{{ $product->name }}</dd></div>
                <div><dt>브랜드</dt><dd>{{ $product->brand ?: '자체/기타' }}</dd></div>
                <div><dt>상품코드</dt><dd>YW-{{ $product->external_no }}</dd></div>
                <div><dt>카테고리</dt><dd>{{ $product->category->name ?? '-' }}</dd></div>
                <div><dt>안전인증</dt><dd>KCs 안전인증 정품</dd></div>
                <div><dt>판매가</dt><dd>{{ $final ? number_format($final).'원' : '가격문의' }}</dd></div>
            </dl>
        </div>

        <div class="pd-notice">
            <div class="col">
                <h3>배송 안내</h3>
                <ul>
                    <li><b>배송 방법</b> · 택배</li>
                    <li><b>배송 지역</b> · 전국</li>
                    <li><b>배송 비용</b> · 3,000원 (5만원 이상 구매 시 무료)</li>
                    <li><b>배송 기간</b> · 오후 2시 이전 결제 시 당일출고 (영업일 기준 1~3일)</li>
                    <li>도서·산간 지역은 추가 배송비가 발생할 수 있습니다.</li>
                </ul>
            </div>
            <div class="col">
                <h3>교환 및 반품 안내</h3>
                <ul>
                    <li>상품 수령 후 7일 이내 교환·반품 신청이 가능합니다.</li>
                    <li>단순 변심에 의한 교환·반품 시 왕복 배송비는 고객 부담입니다.</li>
                    <li>착용·사용했거나 포장이 훼손된 경우 교환·반품이 제한됩니다.</li>
                    <li>주문 제작·개별 인쇄 상품은 교환·반품이 불가합니다.</li>
                    <li>상품 하자·오배송의 경우 전액 판매자 부담으로 처리됩니다.</li>
                </ul>
            </div>
        </div>
    </div>

    @if($related->count())
    <section class="section" style="border-top:1px solid var(--line);padding-bottom:20px">
        <div class="sec-head"><div class="st"><h2>함께 보면 좋은 상품</h2></div></div>
        <div class="p-grid">
            @foreach($related as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection
