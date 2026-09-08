@extends('errors._base')
@section('title', '요청이 너무 많습니다')
@section('heading', '요청이 너무 많습니다')
@section('message')
    짧은 시간에 요청이 몰렸습니다.<br>
    잠시 기다렸다가 다시 시도해 주세요.
@endsection

@section('figure')
    <div class="ico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3a9 9 0 1 0 9 9"/><path d="M12 7v5l3 2"/><path d="M16 3h5v5"/>
        </svg>
    </div>
    <div class="code">ERROR 429</div>
@endsection

@section('actions')
    <div class="btns">
        <button class="btn primary" onclick="location.reload()">다시 시도</button>
        <a class="btn ghost" href="{{ url('/') }}">홈으로 가기</a>
    </div>
@endsection
