@extends('layouts.app')
@section('title', '로그인 · KOR SAFETY')

@section('content')
<div class="wrap">
    <div class="acct">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>
        </div>
        <h1>로그인</h1>
        <p class="sub">KOR SAFETY 계정으로 로그인하세요.</p>

        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

        <form action="{{ route('login.post') }}" method="post">
            @csrf
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" value="{{ old('email') }}" autofocus placeholder="you@example.com">
            </div>
            <div class="field">
                <label>비밀번호</label>
                <input type="password" name="password" placeholder="비밀번호">
            </div>
            <div class="row-between">
                <label><input type="checkbox" name="remember" value="1"> 로그인 상태 유지</label>
            </div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">로그인</button>
        </form>

        <div class="alt">아직 회원이 아니신가요? <a href="{{ route('register') }}">회원가입</a></div>
    </div>
</div>
@endsection
