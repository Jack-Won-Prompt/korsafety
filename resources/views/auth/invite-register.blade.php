@extends('layouts.app')
@section('title', '회원가입 · KOR SAFETY')
@section('robots', 'noindex,nofollow')

@section('content')
@include('partials.address-finder')
@include('partials.file-pick')
@php $isPartner = $invitation->role === 'partner'; @endphp

<div class="wrap">
    <div class="acct" style="max-width:620px">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16v12H4z"/><path d="M4 7l8 6 8-6"/></svg>
        </div>
        <h1>{{ $isPartner ? '협력사 회원가입' : '회원가입' }}</h1>
        <p class="sub">
            @if($invitation->company_name){{ $invitation->company_name }} @endif초대받으신 정보를 입력해 주세요.
            가입 후 담당자 승인이 끝나면 이용하실 수 있습니다.
        </p>

        @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

        <div class="invite-box">
            <span class="invite-label">초대받은 이메일</span>
            <b>{{ $invitation->email }}</b>
            <span class="badge {{ $isPartner ? 'hq' : 'ok' }}">{{ $invitation->role_label }}</span>
        </div>

        <form action="{{ route('invite.register', $invitation->token) }}" method="post" enctype="multipart/form-data">
            @csrf

            <div class="field-group-title">가입자 정보</div>
            <div class="field">
                <label>이름 <span class="req">*</span></label>
                <input type="text" name="name" value="{{ old('name', $invitation->name) }}" autofocus placeholder="홍길동">
            </div>
            <div class="field">
                <label>휴대전화 <span class="req">*</span></label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="010-1234-5678">
            </div>
            <div class="field">
                <label>비밀번호 <span class="req">*</span></label>
                <input type="password" name="password" placeholder="6자 이상">
            </div>
            <div class="field">
                <label>비밀번호 확인 <span class="req">*</span></label>
                <input type="password" name="password_confirmation" placeholder="비밀번호 재입력">
            </div>

            @if($isPartner)
                <div class="field-group-title">회사 정보</div>
                <div class="field">
                    <label>회사명 <span class="req">*</span></label>
                    <input type="text" name="company_name" value="{{ old('company_name', $invitation->company_name) }}" placeholder="(주)한국안전">
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
            @endif

            <div class="field">
                <label>{{ $isPartner ? '사업장 주소' : '주소' }} @if($isPartner)<span class="req">*</span>@else<span class="opt">선택</span>@endif</label>
                <div class="addr-row">
                    <input type="text" id="inv_postcode" name="postcode" value="{{ old('postcode') }}" placeholder="우편번호" readonly>
                    <button type="button" class="btn btn-line addr-btn" data-addr-find="inv">주소 검색</button>
                </div>
                <input type="text" id="inv_address1" name="address1" value="{{ old('address1') }}" placeholder="주소" readonly style="margin-bottom:8px">
                <input type="text" id="inv_address2" name="address2" value="{{ old('address2') }}" placeholder="상세주소">
            </div>

            @if($isPartner)
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
            @endif

            <button type="submit" class="btn btn-accent btn-lg btn-block" style="margin-top:12px">가입하기</button>
        </form>
    </div>
</div>
@endsection
