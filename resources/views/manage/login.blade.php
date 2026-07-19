<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>관리 로그인 · KOR SAFETY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<div class="auth-wrap">
    <form class="auth-card" action="{{ route('manage.login.post') }}" method="post">
        @csrf
        <div class="m">KS</div>
        <h1>관리 콘솔 로그인</h1>
        <p class="sub">본사 관리자 · 입점 판매점 공용 로그인</p>

        @if(session('status'))<div class="auth-ok">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="auth-err">{{ $errors->first() }}</div>@endif

        <div class="form-row">
            <label>이메일</label>
            <input class="input" type="email" name="email" value="{{ old('email') }}" autofocus placeholder="admin@korsafety.kr">
        </div>
        <div class="form-row">
            <label>비밀번호</label>
            <input class="input" type="password" name="password" placeholder="비밀번호">
        </div>
        <button class="btn btn-accent" type="submit">로그인</button>

        <div class="auth-links" style="flex-wrap:wrap;gap:8px">
            <a href="{{ route('partner.apply') }}">입점 신청</a>
            <a href="{{ route('agent.apply') }}">협력사 신청</a>
            <a href="{{ route('purchaser.apply') }}">구매 대행자 신청 →</a>
        </div>

        <div class="demo">
            <b>데모 계정</b><br>
            본사 관리자 — admin@korsafety.kr / korsafety2013<br>
            판매점 — delta@partner.kr / seller123<br>
            협력사 — agent@partner.kr / agent123<br>
            구매 대행자 — buyer@partner.kr / buyer123
        </div>
    </form>
</div>
</body>
</html>
