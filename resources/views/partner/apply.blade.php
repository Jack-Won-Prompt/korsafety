<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>입점 신청 · KOR SAFETY</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/variable/pretendardvariable.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ @filemtime(public_path('css/admin.css')) }}">
</head>
<body>
<div class="auth-wrap" style="padding:40px 0">
    <form class="auth-card wide" action="{{ route('partner.apply.post') }}" method="post">
        @csrf
        <div class="m">KS</div>
        <h1>입점 판매점 신청</h1>
        <p class="sub">신청 후 본사 승인이 완료되면 상품을 등록·판매할 수 있습니다.</p>

        @if($errors->any())<div class="auth-err">{{ $errors->first() }}</div>@endif

        <div class="form-2">
            <div class="form-row"><label>상호명 <span class="req">*</span></label>
                <input class="input" name="store_name" value="{{ old('store_name') }}" placeholder="예) 델타 세이프티"></div>
            <div class="form-row"><label>대표자명 <span class="req">*</span></label>
                <input class="input" name="owner_name" value="{{ old('owner_name') }}" placeholder="홍길동"></div>
        </div>
        <div class="form-2">
            <div class="form-row"><label>사업자등록번호 <span class="req">*</span></label>
                <input class="input" name="business_no" value="{{ old('business_no') }}" placeholder="000-00-00000"></div>
            <div class="form-row"><label>연락처 <span class="req">*</span></label>
                <input class="input" name="phone" value="{{ old('phone') }}" placeholder="02-000-0000"></div>
        </div>
        <div class="form-row"><label>이메일 (로그인 ID) <span class="req">*</span></label>
            <input class="input" type="email" name="email" value="{{ old('email') }}" placeholder="store@example.com"></div>
        <div class="form-2">
            <div class="form-row"><label>비밀번호 <span class="req">*</span></label>
                <input class="input" type="password" name="password" placeholder="6자 이상"></div>
            <div class="form-row"><label>비밀번호 확인 <span class="req">*</span></label>
                <input class="input" type="password" name="password_confirmation" placeholder="비밀번호 재입력"></div>
        </div>

        <button class="btn btn-accent" type="submit" style="width:100%;height:50px;margin-top:6px">입점 신청하기</button>
        <div class="auth-links"><a href="{{ route('manage.login') }}">← 로그인으로</a><a href="{{ route('home') }}">쇼핑몰로</a></div>
    </form>
</div>
</body>
</html>
