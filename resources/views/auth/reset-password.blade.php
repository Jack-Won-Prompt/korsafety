@extends('layouts.app')
@section('title', '비밀번호 재설정 · KOR SAFETY')

@section('content')
<div class="wrap">
    <div class="acct">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="11" width="18" height="10" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/><circle cx="12" cy="16" r="1"/></svg>
        </div>
        <h1>비밀번호 재설정</h1>
        <p class="sub">새 비밀번호를 입력해 주세요.</p>

        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

        <form action="{{ route('password.update') }}" method="post">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" readonly style="background:var(--bg-soft)">
            </div>
            <div class="field">
                <label>새 비밀번호</label>
                <input type="password" name="password" autofocus placeholder="4자 이상">
            </div>
            <div class="field">
                <label>새 비밀번호 확인</label>
                <input type="password" name="password_confirmation" placeholder="비밀번호 재입력">
            </div>
            <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-top:8px">비밀번호 변경</button>
        </form>

        <div class="alt"><a href="{{ route('login') }}">← 로그인으로</a></div>
    </div>
</div>
@endsection
