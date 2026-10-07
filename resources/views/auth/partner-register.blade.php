@extends('layouts.app')
@section('title', '협력사 회원가입 · KOR SAFETY')
@section('robots', 'noindex,nofollow')

@section('content')
@include('partials.address-finder')
@include('partials.email-verify')
@include('partials.file-pick')
<div class="wrap">
    <div class="acct" style="max-width:620px">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/></svg>
        </div>
        <h1>협력사 회원가입</h1>
        <p class="sub">사업자등록증 확인 후 본사가 승인하면 <b>협력사 할인가</b>로 구매할 수 있습니다.</p>

        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

        <form action="{{ route('partner.register.post') }}" method="post" enctype="multipart/form-data" data-verify-form>
            @csrf
            <input type="hidden" name="email_verified" value="{{ old('email_verified') }}">

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
                <div class="addr-row">
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="email">
                    <button type="button" class="btn btn-line addr-btn" data-verify-send>인증하기</button>
                </div>
                <div class="addr-row" data-verify-row hidden>
                    <input type="text" inputmode="numeric" maxlength="6" placeholder="인증번호 6자리" data-verify-code autocomplete="one-time-code">
                    <span class="verify-timer" data-verify-timer hidden></span>
                    <button type="button" class="btn btn-line addr-btn" data-verify-confirm>확인</button>
                </div>
                <div class="verify-msg" data-verify-msg hidden></div>
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
                <div class="addr-row">
                    <input type="text" id="biz_postcode" name="postcode" value="{{ old('postcode') }}" placeholder="우편번호" readonly>
                    <button type="button" class="btn btn-line addr-btn" data-addr-find="biz">주소 검색</button>
                </div>
                <input type="text" id="biz_address1" name="address1" value="{{ old('address1') }}" placeholder="주소" readonly style="margin-bottom:8px">
                <input type="text" id="biz_address2" name="address2" value="{{ old('address2') }}" placeholder="상세주소 (층·호수 등)">
            </div>
            <div class="field">
                <label>사업자등록증 <span class="req">*</span></label>
                <div class="file-pick" data-file-pick>
                    <input type="file" id="license" name="license" accept=".jpg,.jpeg,.png,.webp,.pdf">
                    <label for="license" class="file-pick-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5-5 5 5"/><path d="M12 5v12"/></svg>
                        파일 선택
                    </label>
                    <span class="file-pick-name" data-file-name data-empty="선택된 파일이 없습니다">선택된 파일이 없습니다</span>
                </div>
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

@endsection
