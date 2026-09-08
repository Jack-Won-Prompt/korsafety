@extends('errors._base')
@section('title', '일시적인 오류가 발생했습니다')
@section('heading', '일시적인 오류가 발생했습니다')
@section('message')
    서비스 처리 중 문제가 발생했습니다. 잠시 후 다시 시도해 주세요.<br>
    같은 화면이 계속 나오면 고객센터로 알려주시면 빠르게 확인하겠습니다.
@endsection

@section('figure')
    <div class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <path d="M12 9v4M12 17h.01"/>
        </svg>
    </div>
    <div class="code">ERROR 500</div>
@endsection

@section('actions')
    <div class="btns">
        <button class="btn primary" onclick="location.reload()">다시 시도</button>
        <a class="btn ghost" href="{{ url('/') }}">홈으로 가기</a>
    </div>
@endsection
