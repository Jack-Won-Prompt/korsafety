@extends('errors._base')
@section('title', '점검 중입니다')
@section('heading', '더 좋은 서비스를 위해 점검 중입니다')
@section('message')
    잠시 후 다시 찾아와 주세요.<br>
    급한 주문·문의는 고객센터로 연락 주시면 바로 도와드리겠습니다.
@endsection

@section('figure')
    <div class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M14.7 6.3a4 4 0 0 1-5 5L4 17v3h3l5.7-5.7a4 4 0 0 1 5-5l1.6-1.6-2.9-2.9z"/>
        </svg>
    </div>
    <div class="code">SERVICE UNAVAILABLE</div>
@endsection

@section('actions')
    <div class="btns">
        <a class="btn primary" href="tel:{{ preg_replace('/[^0-9+]/', '', config('company.tel')) }}">☎ {{ config('company.tel') }}</a>
        <button class="btn ghost" onclick="location.reload()">새로고침</button>
    </div>
@endsection
