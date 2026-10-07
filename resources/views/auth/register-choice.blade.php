@extends('layouts.app')
@section('title', '회원가입 · KOR SAFETY')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="wrap">
    <div class="acct" style="max-width:760px">
        <div class="acct-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/><path d="M19 8v6M22 11h-6" stroke-linecap="round"/></svg>
        </div>
        <h1>회원가입</h1>
        <p class="sub">가입 유형을 선택해 주세요.</p>

        <div class="join-pick">
            <a href="{{ route('register') }}" class="join-card">
                <div class="jc-ico">👤</div>
                <h2>일반 회원가입</h2>
                <p>개인 구매 고객용입니다. 바로 가입하고 주문할 수 있습니다.</p>
                <span class="jc-go">일반 회원으로 가입 →</span>
            </a>
            <a href="{{ route('partner.register') }}" class="join-card">
                <div class="jc-ico">🏢</div>
                <h2>협력사 회원가입</h2>
                <p>사업자 거래처용입니다. 사업자등록증 확인 후 승인되면 <b>협력사 할인가</b>로 구매할 수 있습니다.</p>
                <span class="jc-go">협력사로 가입 →</span>
            </a>
        </div>

        <div class="alt">이미 계정이 있으신가요? <a href="{{ route('login') }}">로그인</a></div>
    </div>
</div>

@push('styles')
<style>
    .join-pick{display:grid;grid-template-columns:repeat(2,1fr);gap:16px;margin:8px 0 20px}
    .join-card{display:block;padding:24px 20px;border:1px solid #e3e6ee;border-radius:14px;background:#fff;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
    .join-card:hover{border-color:var(--accent);box-shadow:0 6px 20px rgba(0,0,0,.06)}
    .join-card .jc-ico{font-size:30px;margin-bottom:10px}
    .join-card h2{margin:0 0 8px;font-size:17px}
    .join-card p{margin:0 0 14px;font-size:13.5px;color:#6b7280;line-height:1.6}
    .join-card .jc-go{font-weight:700;color:var(--accent);font-size:13.5px}
    @media (max-width:640px){ .join-pick{grid-template-columns:1fr} }
</style>
@endpush
@endsection
