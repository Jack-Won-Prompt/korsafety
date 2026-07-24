<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>관리 로그인 · KOR SAFETY</title>
    <link rel="icon" type="image/png" href="{{ asset('brand/favicon.png') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
</head>
<body>
<div class="auth-wrap">
    <form class="auth-card" action="{{ route('manage.login.post') }}" method="post">
        @csrf
        <img src="{{ asset('brand/icon.png') }}" class="m-logo" alt="KS">
        <h1>관리 콘솔 로그인</h1>
        <p class="sub">본사 관리자 · 입점 판매점 공용 로그인</p>

        @if(session('status'))<div class="auth-ok">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="auth-err">{{ $errors->first() }}</div>@endif

        <div class="form-row">
            <label>이메일</label>
            <input class="input" type="email" name="email" value="{{ old('email') }}" autofocus placeholder="이메일 주소">
        </div>
        <div class="form-row">
            <label>비밀번호</label>
            <input class="input" type="password" name="password" placeholder="비밀번호">
        </div>
        <button class="btn btn-accent" type="submit">로그인</button>

        <div style="text-align:center;margin-top:16px">
            <a href="{{ route('password.request') }}" style="color:#8b93a1;font-size:13px">비밀번호를 잊으셨나요?</a>
        </div>

        <div class="auth-links" style="flex-wrap:wrap;gap:8px;margin-top:14px">
            <a href="{{ route('partner.apply') }}">입점 신청</a>
            <a href="{{ route('agent.apply') }}">협력사 신청</a>
            <a href="{{ route('purchaser.apply') }}">구매 대행자 신청 →</a>
        </div>
    </form>
</div>
</body>
</html>
