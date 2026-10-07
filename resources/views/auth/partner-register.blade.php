@extends('layouts.app')
@section('title', '협력사 회원가입 · KOR SAFETY')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="wrap">
    <div class="acct" style="max-width:620px">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/></svg>
        </div>
        <h1>협력사 회원가입</h1>
        <p class="sub">사업자등록증 확인 후 본사가 승인하면 <b>협력사 할인가</b>로 구매할 수 있습니다.</p>

        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

        <form action="{{ route('partner.register.post') }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="field-group-title">가입자 정보</div>
            <div class="field">
                <label>가입자 이름 <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" autofocus placeholder="홍길동">
            </div>
            <div class="field">
                <label>가입자 휴대전화 <span class="req">*</span></label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="010-1234-5678">
            </div>
            <div class="field">
                <label>이메일 (로그인 아이디) <span class="req">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com">
            </div>
            <div class="field">
                <label>비밀번호 <span class="req">*</span></label>
                <input type="password" name="password" placeholder="6자 이상">
            </div>
            <div class="field">
                <label>비밀번호 확인 <span class="req">*</span></label>
                <input type="password" name="password_confirmation" placeholder="비밀번호 재입력">
            </div>

            <div class="field-group-title">회사 정보</div>
            <div class="field">
                <label>회사명 <span class="req">*</span></label>
                <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="(주)한국안전">
            </div>
            <div class="field">
                <label>회사 전화번호 <span class="req">*</span></label>
                <input type="text" name="company_phone" value="{{ old('company_phone') }}" placeholder="02-1234-5678">
            </div>
            <div class="field">
                <label>회사 팩스 <span class="opt">선택</span></label>
                <input type="text" name="company_fax" value="{{ old('company_fax') }}" placeholder="02-1234-5679">
            </div>
            <div class="field">
                <label>대표자 이름 <span class="req">*</span></label>
                <input type="text" name="owner_name" value="{{ old('owner_name') }}" placeholder="김대표">
            </div>
            <div class="field">
                <label>사업장 주소 <span class="req">*</span></label>
                <input type="text" name="business_address" value="{{ old('business_address') }}" placeholder="서울시 중구 ○○로 00, 0층">
            </div>
            <div class="field">
                <label>사업자등록증 <span class="req">*</span></label>
                <input type="file" name="license" accept=".jpg,.jpeg,.png,.webp,.pdf">
                <div class="hint">JPG · PNG · WEBP · PDF, 8MB 이하. 본사 담당자만 확인하며 외부에 공개되지 않습니다.</div>
            </div>

            <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-top:12px">가입 신청하기</button>
        </form>

        <div class="alt">
            개인 고객이신가요? <a href="{{ route('register') }}">일반 회원가입</a> ·
            이미 계정이 있으신가요? <a href="{{ route('login') }}">로그인</a>
        </div>
    </div>
</div>

@push('styles')
<style>
    .field-group-title{margin:18px 0 10px;padding-top:14px;border-top:1px solid #eef0f4;font-weight:800;font-size:14px;color:#1b2130}
    .field-group-title:first-of-type{border-top:0;padding-top:0;margin-top:4px}
    .req{color:#c11c0f;font-weight:700}
    .opt{color:#9aa0a6;font-weight:600;font-size:12px}
    .hint{margin-top:6px;font-size:12px;color:#8a90a0;line-height:1.5}
</style>
@endpush
@endsection
