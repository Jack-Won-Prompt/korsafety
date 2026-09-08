@extends('errors._base')
@section('title', '접근 권한이 없습니다')
@section('heading', '접근 권한이 없습니다')
@section('message')
    {{ $exception?->getMessage() ?: '이 페이지를 볼 수 있는 권한이 없습니다.' }}<br>
    권한이 필요하다면 담당자에게 문의해 주세요.
@endsection

@section('figure')
    <div class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>
        </svg>
    </div>
    <div class="code">ERROR 403</div>
@endsection

@section('actions')
    <div class="btns">
        <a class="btn primary" href="{{ url('/') }}">홈으로 가기</a>
        <a class="btn ghost" href="{{ url('/admin/login') }}">관리자 로그인</a>
    </div>
@endsection
