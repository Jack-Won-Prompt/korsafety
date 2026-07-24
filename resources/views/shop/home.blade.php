@extends('layouts.app')
@section('title', 'KOR SAFETY · 산업안전용품 전문 쇼핑몰')

@php
    // slug -> inline SVG path set for category icons
    $icons = [
        'safety-shoes' => '<path d="M2 17h13l4-2 3-4c-2 0-3-1-5-1l-3 2H4z"/><path d="M2 17v3h20v-2"/>',
        'workwear'     => '<path d="M8 3l4 3 4-3 5 4-3 4-2-1v10H8V10L6 11 3 7z"/>',
        'safety-gear'  => '<path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/>',
        'harness'      => '<path d="M8 3v18M16 3v18M8 8h8M8 14h8"/>',
        'facilities'   => '<path d="M3 21V9l9-6 9 6v12"/><path d="M9 21v-6h6v6"/>',
        'road-safety'  => '<path d="M12 2l9 18H3z"/><path d="M12 9v5M12 17h.01"/>',
        'fire-rescue'  => '<path d="M12 2c3 4 5 6 5 10a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-5 1-9z"/>',
        'clean-safe'   => '<path d="M4 10l8-6 8 6v10H4z"/><path d="M9 20v-6h6v6"/><path d="M12 8v3"/>',
        'seasonal'     => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
    ];
@endphp

@section('content')
@if($maintenance ?? false)
<section class="maint">
    <div class="wrap">
        <div class="maint-box">
            <span class="maint-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a4 4 0 0 1-5 5L4 17v3h3l5.7-5.7a4 4 0 0 1 5-5l1.6-1.6-2.9-2.9z"/>
                </svg>
            </span>
            <div class="maint-k">SERVICE PREPARING</div>
            <h2>{{ $maintenanceMessage ?: '더 좋은 서비스를 위해서 준비중에 있습니다.' }}</h2>
            <p>불편을 드려 죄송합니다. 빠른 시일 내에 더 나은 모습으로 찾아뵙겠습니다.</p>
            <div class="maint-contact">
                <a href="tel:02-2273-9533" class="btn btn-accent">☎ 02-2273-9533</a>
                <a href="{{ route('about') }}" class="btn btn-ghost">회사소개 보기</a>
            </div>
        </div>
    </div>
</section>
@else
@php
    $heroSlides = [
        ['img'=>'/shop/img/4568/photo.jpg','eyebrow'=>'PROFESSIONAL WORKWEAR','title'=>'현장을 지키는<br><em>프로의 선택</em>','sub'=>'고시인성 워크웨어로 안전과 스타일을 동시에. 검증된 브랜드만 담았습니다.','cta'=>'워크웨어 보기','link'=>route('category.show','workwear')],
        ['img'=>'/shop/img/4039/photo.jpg','eyebrow'=>'PREMIUM SAFETY SHOES','title'=>'가볍게, 그러나<br><em>강력하게</em>','sub'=>'초경량 다이얼 안전화 신상품 입고. 하루 종일 편안한 착화감.','cta'=>'안전화 보기','link'=>route('category.show','safety-shoes')],
        ['img'=>'/shop/img/2441/photo.jpg','eyebrow'=>'EXTREME PROTECTION','title'=>'극한의 현장까지<br><em>완벽한 보호</em>','sub'=>'방한·내열 특수 장갑부터 보호구까지, 현장이 요구하는 모든 안전용품.','cta'=>'안전용품 보기','link'=>route('category.show','safety-gear')],
        ['img'=>'/shop/img/2404/photo.jpg','eyebrow'=>'ALL-SEASON SAFETY','title'=>'사계절<br><em>세이프티 웨어</em>','sub'=>'혹한기 방한부터 혹서기 쿨링까지. 시즌 상품을 특별한 가격에.','cta'=>'시즌상품 보기','link'=>route('category.show','seasonal')],
    ];
@endphp
<div class="wrap">
    <section class="hero-slider" id="hero" data-interval="5500">
        <div class="hs-track">
            @foreach($heroSlides as $i => $s)
                <div class="hs-slide {{ $i === 0 ? 'active' : '' }}">
                    <div class="hs-bg" style="background-image:url('{{ asset($s['img']) }}')"></div>
                    <div class="hs-overlay"></div>
                    <div class="wrap hs-content">
                        <span class="hs-eyebrow">◆ {{ $s['eyebrow'] }}</span>
                        <h2 class="hs-title">{!! $s['title'] !!}</h2>
                        <p class="hs-sub">{{ $s['sub'] }}</p>
                        <a href="{{ $s['link'] }}" class="btn btn-accent btn-lg hs-cta">{{ $s['cta'] }} →</a>
                    </div>
                </div>
            @endforeach
        </div>

        <button class="hs-arrow prev" aria-label="이전 슬라이드">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M15 6l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>
        <button class="hs-arrow next" aria-label="다음 슬라이드">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 6l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        <div class="hs-dots">
            @foreach($heroSlides as $i => $s)
                <button class="hs-dot {{ $i === 0 ? 'active' : '' }}" data-i="{{ $i }}" aria-label="슬라이드 {{ $i+1 }}"><span></span></button>
            @endforeach
        </div>
    </section>
</div>

@if($showCategories)
<section class="section tight">
    <div class="wrap">
        <div class="cat-tiles">
            @foreach($categories as $cat)
                <a href="{{ route('category.show', $cat) }}" class="cat-tile">
                    <span class="ct-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$cat->slug] ?? '<rect x="4" y="4" width="16" height="16" rx="3"/>' !!}</svg>
                    </span>
                    <span class="ct-name">{{ $cat->name }}</span>
                    <span class="ct-count">{{ number_format($cat->products_count) }}개 상품</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<section id="best" class="section" style="padding-top:20px">
    <div class="wrap">
        <div class="sec-head">
            <div class="st">
                <h2>베스트 셀러</h2>
                <div class="sub">현장에서 가장 많이 찾는 인기 안전용품</div>
            </div>
        </div>
        <div class="p-grid">
            @foreach($best as $product)
                @include('partials.product-card', ['product' => $product, 'badge' => 'best'])
            @endforeach
        </div>
    </div>
</section>

<section class="section tight">
    <div class="wrap">
        <div class="promo">
            <div class="cell">
                <span class="pi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
                <div><h4>KCs 안전인증 정품</h4><p>전 상품 정식 수입·인증 완료</p></div>
            </div>
            <div class="cell">
                <span class="pi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 7h13v10H3z"/><path d="M16 10h4l1 3v4h-5z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg></span>
                <div><h4>당일 출고</h4><p>오후 2시 이전 주문 시 당일 발송</p></div>
            </div>
            <div class="cell">
                <span class="pi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v12H4z"/><path d="M8 20h8M12 16v4"/></svg></span>
                <div><h4>대량구매 할인</h4><p>기업·현장 단위 견적 문의 환영</p></div>
            </div>
        </div>
    </div>
</section>

@foreach($showcase as $block)
    @if($block['products']->count())
    <section class="section showcase" style="{{ $loop->even ? 'background:#fff' : '' }}">
        <div class="wrap">
            <div class="showcase-row" style="border:0;padding:0">
                <div class="showcase-aside">
                    <span class="badge badge-new" style="margin-bottom:12px;display:inline-block">CATEGORY</span>
                    <h3>{{ $block['category']->name }}</h3>
                    <p>{{ $block['category']->name }} 카테고리의 추천 상품을 만나보세요.</p>
                    <a href="{{ route('category.show', $block['category']) }}" class="btn btn-ghost">전체보기 →</a>
                </div>
                <div class="p-grid">
                    @foreach($block['products'] as $product)
                        @include('partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </div>
    </section>
    @endif
@endforeach

<section class="section">
    <div class="wrap">
        <div class="sec-head">
            <div class="st">
                <h2>신상품</h2>
                <div class="sub">새롭게 입고된 최신 안전용품</div>
            </div>
        </div>
        <div class="p-grid g5">
            @foreach($newIn as $product)
                @include('partials.product-card', ['product' => $product, 'badge' => 'new'])
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection
