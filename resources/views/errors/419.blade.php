@extends('errors._base')
@section('title', '페이지 유효시간이 지났습니다')
@section('heading', '페이지 유효시간이 지났습니다')
@section('message')
    화면을 오래 열어 두면 보안을 위해 입력이 만료됩니다.<br>
    이전 화면으로 돌아가 다시 시도해 주세요.
@endsection

@section('figure')
    <div class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>
        </svg>
    </div>
    <div class="code">ERROR 419</div>
@endsection

@section('actions')
    <div class="btns">
        <button class="btn primary" onclick="history.back()">이전 화면으로</button>
        <a class="btn ghost" href="{{ url('/') }}">홈으로 가기</a>
    </div>
@endsection
