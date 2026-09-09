{{--
    검색 유입용 공통 메타 — 각 페이지는 아래 섹션만 정의하면 된다.
      @section('title')      제목 (필수)
      @section('meta_desc')  설명
      @section('og_image')   대표 이미지 경로 (없으면 로고)
      @section('robots')     색인 정책 (기본 index,follow)
      @section('canonical')  정규 주소 (없으면 현재 주소)
--}}
@php
    $seoTitle = trim($__env->yieldContent('title', 'KOR SAFETY · 산업안전용품 전문 쇼핑몰'));
    $seoDesc = trim($__env->yieldContent('meta_desc', '안전화, 워크웨어, 안전용품, 안전시설물까지 — 현장을 지키는 모든 안전장비를 한 곳에서.'));
    $seoImage = trim($__env->yieldContent('og_image', '')) ?: asset('brand/logo-wordmark.png');
    $seoCanonical = trim($__env->yieldContent('canonical', '')) ?: url()->current();
    $seoRobots = trim($__env->yieldContent('robots', 'index,follow'));
    $company = config('company');
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDesc }}">
<meta name="robots" content="{{ $seoRobots }}">
<link rel="canonical" href="{{ $seoCanonical }}">

{{-- 카카오톡·페이스북·네이버 공유 --}}
<meta property="og:type" content="@yield('og_type', 'website')">
<meta property="og:site_name" content="{{ $company['name'] }}">
<meta property="og:locale" content="ko_KR">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDesc }}">
<meta property="og:url" content="{{ $seoCanonical }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDesc }}">
<meta name="twitter:image" content="{{ $seoImage }}">

{{-- 검색엔진 사이트 소유확인 (관리 콘솔 › 사이트 설정에서 입력) --}}
@if($code = \App\Models\Setting::get('seo_naver_verify'))
    <meta name="naver-site-verification" content="{{ $code }}">
@endif
@if($code = \App\Models\Setting::get('seo_google_verify'))
    <meta name="google-site-verification" content="{{ $code }}">
@endif

{{-- 회사 정보 · 사이트 검색 (구글 검색결과의 검색창) --}}
@php
    $seoGraph = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => url('/').'#organization',
                'name' => $company['name'],
                'url' => url('/'),
                'logo' => asset('brand/logo-wordmark.png'),
                'telephone' => $company['tel'],
                'email' => $company['email'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'streetAddress' => $company['address'],
                    'addressCountry' => 'KR',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => url('/').'#website',
                'url' => url('/'),
                'name' => $company['name'],
                'inLanguage' => 'ko-KR',
                'potentialAction' => [
                    '@type' => 'SearchAction',
                    'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url('/search').'?q={search_term_string}'],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($seoGraph, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@stack('jsonld')
