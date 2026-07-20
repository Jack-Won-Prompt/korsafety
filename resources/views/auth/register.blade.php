@extends('layouts.app')
@section('title', '회원가입 · KOR SAFETY')

@section('content')
<div class="wrap">
    <div class="acct">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/><path d="M19 8v6M22 11h-6" stroke-linecap="round"/></svg>
        </div>
        <h1>회원가입</h1>
        <p class="sub">가입하고 간편하게 주문·배송을 관리하세요.</p>

        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

        <form action="{{ route('register.post') }}" method="post">
            @csrf
            <div class="field">
                <label>이름</label>
                <input type="text" name="name" value="{{ old('name') }}" autofocus placeholder="홍길동">
            </div>
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
            </div>
            <div class="field">
                <label>비밀번호</label>
                <input type="password" name="password" placeholder="6자 이상">
            </div>
            <div class="field">
                <label>비밀번호 확인</label>
                <input type="password" name="password_confirmation" placeholder="비밀번호 재입력">
            </div>
            <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-top:8px">가입하기</button>
        </form>

        <div class="alt">이미 계정이 있으신가요? <a href="{{ route('login') }}">로그인</a></div>
    </div>
</div>
@endsection
