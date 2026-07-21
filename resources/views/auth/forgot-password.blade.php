@extends('layouts.app')
@section('title', '비밀번호 찾기 · KOR SAFETY')

@section('content')
<div class="wrap">
    <div class="acct">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        </div>
        <h1>비밀번호 찾기</h1>
        <p class="sub">가입하신 이메일로 재설정 링크를 보내드립니다.</p>

        @if(session('status'))<div class="err" style="background:var(--okbg,#e9f8ef);border-color:#bce8cf;color:#12703a">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

        <form action="{{ route('password.email') }}" method="post">
            @csrf
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" value="{{ old('email') }}" autofocus placeholder="you@example.com">
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">재설정 링크 받기</button>
        </form>

        <div class="alt"><a href="{{ route('login') }}">← 로그인으로 돌아가기</a></div>
    </div>
</div>
@endsection
