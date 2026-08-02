@php
    $u = auth()->user();
    $isHq = $u && $u->isHqAdmin();
    $isAgent = $u && $u->isAgent();
    $isPurchaser = $u && $u->isPurchaser();
    $store = $u?->seller;
    $consoleName = $isHq ? 'HQ CONSOLE' : ($isAgent ? 'AGENT CONSOLE' : ($isPurchaser ? 'PURCHASE CONSOLE' : 'SELLER CONSOLE'));
    $roleName = $isHq ? '본사 (Super Admin)' : ($isAgent ? ($u->agent->name ?? '협력사') : ($isPurchaser ? ($u->purchaser->name ?? '구매 대행자') : ($store->name ?? '판매점')));
    $roleDesc = $isHq ? '전체 관리 권한' : ($isAgent ? '영업대행 협력사' : ($isPurchaser ? '구매대행자' : '입점 판매점'));
    $inquiryUnread = 0;
    if ($isHq) {
        try { $inquiryUnread = (int) \App\Models\Inquiry::sum('unread_admin'); } catch (\Throwable $e) {}
    }
    // 상단 SR 배지 — 본사는 미처리 전체, 그 외 역할은 본인 미종료 건
    $srOpen = \App\Http\Controllers\Manage\ServiceRequestController::badgeCount($u);
@endphp
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '관리 콘솔') · KOR SAFETY</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon.png') }}?v={{ @filemtime(public_path('brand/favicon.png')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
    @stack('styles')
</head>
<body>
<div class="console">
    <aside class="sidebar">
        <div class="side-brand">
            <img src="{{ asset('brand/icon.png') }}" alt="KS" class="side-logo">
            <span><b>KOR SAFETY</b><small>{{ $consoleName }}</small></span>
        </div>
        <div class="side-role">
            <span class="dot"></span>
            <span>
                <span class="rn">{{ $roleName }}</span><br>
                <span class="rs">{{ $roleDesc }}</span>
            </span>
        </div>

        <nav class="side-nav">
            @php
                $ic = fn($p) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">'.$p.'</svg>';
            @endphp
            <div class="grp">메뉴</div>
            @php
                $navDash = '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>';
                $navUsers = '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>';
                $navBox = '<path d="M20 7l-8-4-8 4 8 4 8-4z"/><path d="M4 7v10l8 4 8-4V7"/><path d="M12 11v10"/>';
                $navBag = '<path d="M6 2l1.5 3h9L18 2"/><path d="M3 6h18l-1.5 12a2 2 0 0 1-2 1.8H6.5a2 2 0 0 1-2-1.8z"/>';
                $navBiz = '<path d="M3 21V8l6-4v17M9 21V4l6 3v14M15 21V7l6 3v11"/><path d="M2 21h20"/>';
                $navCoin = '<circle cx="12" cy="12" r="9"/><path d="M12 7v10M15 9.5a3 3 0 0 0-3-1.5c-1.7 0-3 .9-3 2s1.3 2 3 2 3 1 3 2-1.3 2-3 2a3 3 0 0 1-3-1.5"/>';
            @endphp
            @if($isHq)
                <a href="{{ route('admin.index') }}" class="{{ request()->routeIs('admin.index') ? 'active' : '' }}">{!! $ic($navDash) !!} 대시보드</a>
                <a href="{{ route('admin.sellers') }}" class="{{ request()->routeIs('admin.sellers') ? 'active' : '' }}">{!! $ic($navUsers) !!} 판매점 관리</a>
                <a href="{{ route('admin.agents') }}" class="{{ request()->routeIs('admin.agents') ? 'active' : '' }}">{!! $ic($navBiz) !!} 협력사 관리</a>
                <a href="{{ route('admin.commissions') }}" class="{{ request()->routeIs('admin.commissions') ? 'active' : '' }}">{!! $ic($navCoin) !!} 커미션 정산</a>
                <a href="{{ route('admin.purchasers') }}" class="{{ request()->routeIs('admin.purchasers') ? 'active' : '' }}">{!! $ic($navUsers) !!} 구매 대행자 관리</a>
                <a href="{{ route('admin.cashbacks') }}" class="{{ request()->routeIs('admin.cashbacks') ? 'active' : '' }}">{!! $ic($navCoin) !!} 캐쉬백 정산</a>
                <a href="{{ route('manage.products.index') }}" class="{{ request()->routeIs('manage.products.*') ? 'active' : '' }}">{!! $ic($navBox) !!} 상품 관리</a>
                <a href="{{ route('manage.categories.index') }}" class="{{ request()->routeIs('manage.categories.*') ? 'active' : '' }}">{!! $ic('<path d="M3 5h8v6H3zM13 5h8v4h-8zM13 13h8v6h-8zM3 15h8v4H3z"/>') !!} 상품 카테고리</a>
                <a href="{{ route('manage.orders') }}" class="{{ request()->routeIs('manage.orders') ? 'active' : '' }}">{!! $ic($navBag) !!} 주문 내역</a>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">{!! $ic('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/>') !!} 주문·명세서 관리</a>
                <a href="{{ route('admin.taxinvoice.index') }}" class="{{ request()->routeIs('admin.taxinvoice.*') ? 'active' : '' }}">{!! $ic('<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 7h8M8 11h8M8 15h5"/>') !!} 세금계산서</a>
                <a href="{{ route('admin.inquiries') }}" class="{{ request()->routeIs('admin.inquiries*') ? 'active' : '' }}">{!! $ic('<path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7a8.5 8.5 0 0 1-.9-3.8A8.38 8.38 0 0 1 12.5 3 8.38 8.38 0 0 1 21 11.5z"/>') !!} 실시간 문의 @if($inquiryUnread)<span class="nav-badge">{{ $inquiryUnread }}</span>@endif</a>
                <div class="grp">시스템</div>
                <a href="{{ route('admin.login-logs') }}" class="{{ request()->routeIs('admin.login-logs') ? 'active' : '' }}">{!! $ic('<path d="M12 8v4l3 2"/><circle cx="12" cy="12" r="9"/>') !!} 로그인 이력</a>
                <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">{!! $ic('<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-2.82 1.17V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15H4a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 6 9.4l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 11 4.6V4a2 2 0 1 1 4 0v.09c.66.26 1.4.05 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9H21a2 2 0 1 1 0 4h-.09c-.32.66-.28 1.4.49 2z"/>') !!} 사이트 설정</a>
            @elseif($isAgent)
                <a href="{{ route('agent.index') }}" class="{{ request()->routeIs('agent.index') ? 'active' : '' }}">{!! $ic($navDash) !!} 대시보드</a>
                <a href="{{ route('agent.orders.index') }}" class="{{ request()->routeIs('agent.orders.*') ? 'active' : '' }}">{!! $ic($navBag) !!} 주문 관리</a>
                <a href="{{ route('agent.clients.index') }}" class="{{ request()->routeIs('agent.clients.*') ? 'active' : '' }}">{!! $ic($navBiz) !!} 거래처 관리</a>
            @elseif($isPurchaser)
                <a href="{{ route('purchaser.index') }}" class="{{ request()->routeIs('purchaser.index') ? 'active' : '' }}">{!! $ic($navDash) !!} 대시보드</a>
                <a href="{{ route('purchaser.orders.index') }}" class="{{ request()->routeIs('purchaser.orders.*') ? 'active' : '' }}">{!! $ic($navBag) !!} 주문 관리</a>
                <a href="{{ route('purchaser.buyers.index') }}" class="{{ request()->routeIs('purchaser.buyers.*') ? 'active' : '' }}">{!! $ic($navUsers) !!} 구매자 관리</a>
            @else
                <a href="{{ route('seller.index') }}" class="{{ request()->routeIs('seller.index') ? 'active' : '' }}">{!! $ic($navDash) !!} 대시보드</a>
                <a href="{{ route('manage.products.index') }}" class="{{ request()->routeIs('manage.products.*') ? 'active' : '' }}">{!! $ic($navBox) !!} 상품 관리</a>
                <a href="{{ route('manage.orders') }}" class="{{ request()->routeIs('manage.orders') ? 'active' : '' }}">{!! $ic($navBag) !!} 주문 내역</a>
            @endif

            <div class="grp">지원</div>
            <a href="{{ route('manage.sr.index') }}" class="{{ request()->routeIs('manage.sr.*') ? 'active' : '' }}">
                {!! $ic('<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h4"/>') !!}
                SR 서비스 요청 @if($srOpen)<span class="nav-badge">{{ $srOpen }}</span>@endif
            </a>
        </nav>

        <div class="side-foot">
            <div class="u">{{ $u->name }}</div>
            <div class="e">{{ $u->email }}</div>
            <form action="{{ route('manage.logout') }}" method="post">@csrf
                <button type="submit">로그아웃</button>
            </form>
        </div>
    </aside>

    <div class="main">
        <div class="topbar">
            <div>
                <h1>@yield('page', '대시보드')</h1>
                <div class="crumb">@yield('crumb', 'KOR SAFETY 관리 콘솔')</div>
            </div>
            <div class="t-actions">
                <a href="{{ route('manage.sr.index') }}" class="sr-btn {{ request()->routeIs('manage.sr.*') ? 'on' : '' }}"
                   title="SR · 서비스 요청 {{ $isHq ? '(미처리 건수)' : '(내 미종료 건수)' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>
                        <path d="M9 13h6M9 17h4"/>
                    </svg>
                    <b>SR</b>
                    @if($srOpen)<span class="sr-badge">{{ $srOpen > 99 ? '99+' : $srOpen }}</span>@endif
                </a>
                <a href="{{ route('home') }}" target="_blank" class="btn btn-sm">쇼핑몰 보기 ↗</a>
                @yield('actions')
            </div>
        </div>
        <div class="content">
            @if(session('status'))<div class="flash ok">✓ {{ session('status') }}</div>@endif
            @if(session('error'))<div class="flash err">! {{ session('error') }}</div>@endif
            @if($errors->any())<div class="flash err">! {{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </div>
</div>
@stack('scripts')
</body>
</html>
