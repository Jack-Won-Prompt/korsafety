@extends('layouts.app')
@section('title', '회원가입 · KOR SAFETY')
@section('robots', 'noindex,nofollow')

@section('content')
<div class="wrap">
    <div class="join-wrap">
        <div class="join-head">
            <div class="join-ico">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/><path d="M19 8v6M22 11h-6" stroke-linecap="round"/></svg>
            </div>
            <h1>회원가입</h1>
            <p>가입 유형을 선택해 주세요. 가입 후에도 문의로 변경하실 수 있습니다.</p>
        </div>

        <div class="join-pick">
            <a href="{{ route('register') }}" class="join-card">
                <div class="jc-top">
                    <div class="jc-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="8" r="3.6"/><path d="M5 20c0-3.3 3.1-5.4 7-5.4s7 2.1 7 5.4"/></svg>
                    </div>
                    <div>
                        <h2>일반 회원가입</h2>
                    </div>
                </div>
                <p class="jc-desc">개인 구매 고객을 위한 가입입니다. 바로 가입하고 주문하실 수 있습니다.</p>
                <ul class="jc-list">
                    <li>가입 즉시 주문 가능</li>
                    <li>주문·배송 조회</li>
                    <li>배송지 저장으로 간편 주문</li>
                </ul>
                <span class="jc-go">
                    일반 회원으로 가입
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </a>

            <a href="{{ route('partner.register') }}" class="join-card is-partner">
                <div class="jc-top">
                    <div class="jc-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><path d="M3 21h18M5 21V8l7-4 7 4v13"/><path d="M10 21v-5h4v5"/></svg>
                    </div>
                    <div>
                        <h2>협력사 회원가입</h2>
                        <span class="jc-tag">협력사 할인가 적용</span>
                    </div>
                </div>
                <p class="jc-desc">사업자 거래처를 위한 가입입니다. 사업자등록증 확인 후 승인해 드립니다.</p>
                <ul class="jc-list">
                    <li>승인 후 <b>협력사 전용 할인가</b>로 구매</li>
                    <li>사업자등록증 확인 후 승인 (영업일 기준 확인)</li>
                    <li>대량·정기 구매 거래처에 적합</li>
                </ul>
                <span class="jc-go">
                    협력사로 가입 신청
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </span>
            </a>
        </div>

        <div class="join-foot">
            이미 계정이 있으신가요? <a href="{{ route('login') }}">로그인</a>
        </div>
    </div>
</div>
@endsection
