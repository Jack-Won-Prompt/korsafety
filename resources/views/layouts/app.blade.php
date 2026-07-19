<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'KOR SAFETY · 산업안전용품 전문 쇼핑몰')</title>
    <meta name="description" content="@yield('meta_desc', '안전화, 워크웨어, 안전용품, 안전시설물까지 — 현장을 지키는 모든 안전장비를 한 곳에서.')">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='7' fill='%2312151b'/%3E%3Ctext x='16' y='22' font-family='Arial' font-size='15' font-weight='900' fill='%23ff5722' text-anchor='middle'%3EKS%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <link rel="stylesheet" href="{{ asset('css/shop.css') }}?v={{ @filemtime(public_path('css/shop.css')) }}">
</head>
<body>
<div class="topbar">
    <div class="wrap">
        <div class="tb-left"><span class="dot"></span> 전 상품 안전인증(KCs) 정품 · 당일출고</div>
        <div class="tb-right">
            <a href="{{ route('home') }}">고객센터 1588-0000</a>
            <a href="#">비회원 주문조회</a>
            <a href="#">고객센터</a>
        </div>
    </div>
</div>

<header class="site-header">
    <div class="wrap">
        <div class="header-main">
            <button class="icon-btn menu-btn" aria-label="메뉴">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
            </button>
            <a href="{{ route('home') }}" class="brand">
                <span class="brand-mark"><span>KS</span></span>
                <span>
                    <span class="brand-name">KOR<b>SAFETY</b></span>
                    <span class="brand-sub">SAFETY LIFE PARTNER</span>
                </span>
            </a>

            <form class="search-form" action="{{ route('search') }}" method="get">
                <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="안전화, 브랜드, 상품명을 검색해 보세요" aria-label="검색">
                <button type="submit" aria-label="검색">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4" stroke-linecap="round"/></svg>
                </button>
            </form>

            <div class="header-actions">
                <a href="#" class="icon-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 21s-7-4.5-9.5-9A5 5 0 0 1 12 6a5 5 0 0 1 9.5 6c-2.5 4.5-9.5 9-9.5 9z"/></svg>
                    <span class="lbl">찜</span>
                </a>
                <a href="{{ route('cart.index') }}" class="icon-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M6 6L5 3H2"/></svg>
                    <span class="lbl">장바구니</span>
                    <span class="cart-badge {{ $cartCount ? '' : 'hide' }}" id="cart-badge">{{ $cartCount }}</span>
                </a>
                <a href="#" class="icon-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
                    <span class="lbl">로그인</span>
                </a>
            </div>
        </div>
    </div>
    <nav class="catnav">
        <div class="wrap">
            <a href="{{ route('home') }}" class="all">전체 카테고리</a>
            @foreach($navCategories as $cat)
                <a href="{{ route('category.show', $cat) }}" class="{{ (isset($category) && $category->id === $cat->id) ? 'active' : '' }}">{{ $cat->name }}</a>
            @endforeach
        </div>
    </nav>
</header>

<main>
    @yield('content')
</main>

<footer class="site-footer">
    <div class="wrap">
        <div class="footer-top">
            <div class="footer-brand">
                <span class="brand-name">KOR<b style="color:var(--accent)">SAFETY</b></span>
                <p>산업 현장의 안전을 책임지는 대한민국 대표 안전용품 전문몰. 안전화부터 워크웨어, 안전시설물까지 검증된 정품만을 합리적인 가격으로 제공합니다.</p>
            </div>
            <div class="fcol">
                <h4>쇼핑</h4>
                @foreach($navCategories->take(6) as $cat)
                    <a href="{{ route('category.show', $cat) }}">{{ $cat->name }}</a>
                @endforeach
            </div>
            <div class="fcol">
                <h4>고객지원</h4>
                <a href="#">공지사항</a><a href="#">자주 묻는 질문</a>
                <a href="#">대량구매 문의</a><a href="#">교환/반품 안내</a>
            </div>
            <div class="fcol">
                <h4>회사 · 파트너</h4>
                <a href="#">회사소개</a><a href="#">이용약관</a>
                <a href="{{ route('partner.apply') }}">입점 신청</a>
                <a href="{{ route('agent.apply') }}">협력사(영업대행) 신청</a>
                <a href="{{ route('manage.login') }}">판매자·관리자 로그인</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="biz">
                주식회사 한국안전 · 대표 임현규 · 사업자등록번호 101-86-83744<br>
                서울특별시 종로구 돈화문로 94, 1층(와룡동, 동원빌딩) · 법인등록번호 110111-5230026 · 고객센터 1588-0000
            </div>
            <div>© {{ date('Y') }} KOR SAFETY. All rights reserved.</div>
        </div>
    </div>
</footer>

<div class="drawer" id="drawer">
    <div class="scrim"></div>
    <div class="panel">
        <a href="{{ route('home') }}" style="font-weight:900;font-size:18px;border:0">전체 카테고리</a>
        @foreach($navCategories as $cat)
            <a href="{{ route('category.show', $cat) }}">{{ $cat->name }}</a>
        @endforeach
        <a href="{{ route('cart.index') }}">장바구니</a>
    </div>
</div>

<div class="toast" id="toast">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <span class="msg"></span>
</div>

<script src="{{ asset('js/shop.js') }}?v={{ @filemtime(public_path('js/shop.js')) }}"></script>
@stack('scripts')
</body>
</html>
