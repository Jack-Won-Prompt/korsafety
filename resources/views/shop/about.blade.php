@extends('layouts.app')
@section('title', '회사소개 · KOR SAFETY')
@section('meta_desc', '주식회사 한국안전 — 1969년 설립, 유한킴벌리 프로페셔널 공식 파트너. 산업안전용품·위생용품·소방안전기구 전문 유통기업.')

@section('content')
<div class="wrap">
    {{-- HERO --}}
    <section class="about-hero">
        <div class="about-hero-in">
            <span class="eyebrow">◆ COMPANY PROFILE 2026</span>
            <h1>현장의 안전과 위생을<br>한 번에 책임지는 <em>파트너</em></h1>
            <p>주식회사 한국안전은 1969년 설립 이후 유한킴벌리 프로페셔널 제품을 중심으로 산업 현장의 개인보호구부터 시설 위생용품, 소방안전기구까지 원스톱으로 공급해 온 전문 유통기업입니다.</p>
            <span class="tag">◆ 유한킴벌리 프로페셔널 공식 유통 파트너</span>
        </div>
    </section>

    {{-- 회사 개요 --}}
    <section class="about-sec">
        <div class="about-head">
            <div class="k">ABOUT US</div>
            <h2>회사 개요</h2>
            <p>제조·건설 현장, 병원, 식품·외식업, 오피스에 이르기까지 고객 환경에 맞는 최적의 안전·위생 솔루션을 제안합니다.</p>
        </div>
        <div class="about-overview">
            <p class="intro">
                주식회사 한국안전은 <b>1969년</b>부터 반세기 넘게 이어온 신뢰를 바탕으로,
                산업안전용품과 시설 위생용품, 소방안전기구를 아우르는 통합 유통 체계를 갖추고 있습니다.
                유한킴벌리 프로페셔널을 비롯한 검증된 글로벌 브랜드의 정품만을 공급하며,
                사용 환경 진단부터 등급·규격·디스펜서 선정까지 현장 맞춤 컨설팅으로 비용 절감을 함께 실현합니다.
            </p>
            <dl class="info-table">
                <div class="row"><dt>법인명</dt><dd>주식회사 한국안전</dd></div>
                <div class="row"><dt>대표자</dt><dd>임현규</dd></div>
                <div class="row"><dt>설립일</dt><dd>1969년 10월 1일</dd></div>
                <div class="row"><dt>사업자등록번호</dt><dd>101-86-83744</dd></div>
                <div class="row"><dt>소재지</dt><dd>서울특별시 종로구 돈화문로 94, 1층 (와룡동, 동원빌딩)</dd></div>
                <div class="row"><dt>업종</dt><dd>산업안전용품·청소용품·소방안전기구 도매 및 소매, 통신판매</dd></div>
            </dl>
        </div>
        <div class="about-stats">
            <div class="s"><div class="n">50+</div><div class="l">년 업력 (1969년 설립)</div></div>
            <div class="s"><div class="n">4대</div><div class="l">사업 영역 원스톱 공급</div></div>
            <div class="s"><div class="n">4+</div><div class="l">글로벌 브랜드 정품 유통</div></div>
        </div>
    </section>

    {{-- 사업 영역 --}}
    <section class="about-sec alt" style="border-radius:24px">
        <div class="wrap" style="padding:0 40px">
            <div class="about-head">
                <div class="k">BUSINESS AREAS</div>
                <h2>사업 영역</h2>
                <p>사업자등록 기준 4대 업종을 기반으로, 안전과 위생을 아우르는 통합 공급 체계를 갖추고 있습니다.</p>
            </div>
            <div class="biz-grid">
                <div class="biz-card"><div class="no">01</div><h3>산업안전용품</h3><p>크린가드 보호복·마스크·글러브·위생화 등 개인보호구(PPE) 전문 공급. 현장 위험 등급에 맞는 제품 매칭.</p></div>
                <div class="biz-card"><div class="no">02</div><h3>청소 · 위생용품</h3><p>크리넥스 화장지·핸드타올·스킨케어, 와이프올 와이퍼 등 시설 위생 토탈 솔루션.</p></div>
                <div class="biz-card"><div class="no">03</div><h3>소방안전기구</h3><p>소화기·소방설비 등 소방안전기구 유통. 사업장 법정 소방용품 구비 지원.</p></div>
                <div class="biz-card"><div class="no">04</div><h3>통신판매</h3><p>온라인 채널 기반 전국 단위 판매·배송. 소량 주문부터 정기 납품까지 유연하게 대응.</p></div>
            </div>
        </div>
    </section>

    {{-- 취급 브랜드 --}}
    <section class="about-sec">
        <div class="about-head">
            <div class="k">BRANDS</div>
            <h2>취급 브랜드</h2>
            <p>글로벌 킴벌리클라크·유한킴벌리 프로페셔널의 B2B 브랜드를 정품으로 공급합니다.</p>
        </div>
        <div class="brand-grid">
            <div class="brand-card"><div class="bi">KG</div><div><div class="en">KleenGuard®</div><h3>크린가드</h3><p>보호복·마스크·글러브·보안경·위생화 등 개인보호구(PPE) 대표 브랜드.</p></div></div>
            <div class="brand-card"><div class="bi">KT</div><div><div class="en">Kimtech®</div><h3>킴테크</h3><p>클린룸·실험실용 정밀 와이퍼·클린룸 와이퍼·장갑.</p></div></div>
            <div class="brand-card"><div class="bi">WA</div><div><div class="en">WypAll®</div><h3>와이프올</h3><p>제조·푸드서비스용 산업용 와이퍼. 하이드로니트 공법 원단으로 뛰어난 흡수·내구성.</p></div></div>
            <div class="brand-card"><div class="bi">KS</div><div><div class="en">Kleenex® · Scott®</div><h3>크리넥스 · 스카트</h3><p>화장지·핸드타올·미용티슈·스킨케어 등 워시룸 위생용품.</p></div></div>
        </div>
    </section>

    {{-- 경쟁력 --}}
    <section class="about-sec alt" style="border-radius:24px">
        <div class="wrap" style="padding:0 40px">
            <div class="about-head">
                <div class="k">WHY US</div>
                <h2>한국안전의 경쟁력</h2>
            </div>
            <div class="why-grid">
                <div class="why-card"><span class="wi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3l8 3v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span><div><h3>정품 · 검증된 품질</h3><p>유한킴벌리 프로페셔널 공식 유통 파트너. 인증 기반의 신뢰할 수 있는 정품만 공급합니다.</p></div></div>
                <div class="why-card"><span class="wi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 7l-8-4-8 4 8 4 8-4z"/><path d="M4 7v10l8 4 8-4V7"/></svg></span><div><h3>원스톱 통합 공급</h3><p>개인보호구부터 위생용품, 소방안전기구까지 — 거래처 하나로 사업장 전체를 해결합니다.</p></div></div>
                <div class="why-card"><span class="wi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7h13v10H3z"/><path d="M16 10h4l1 3v4h-5z"/><circle cx="7" cy="18" r="1.6"/><circle cx="17" cy="18" r="1.6"/></svg></span><div><h3>신속한 납품 대응</h3><p>서울 도심(종로) 거점의 기동력. 소량·긴급 주문과 전국 통신판매까지 유연하게 대응합니다.</p></div></div>
                <div class="why-card"><span class="wi"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3 8-8"/><path d="M21 12a9 9 0 1 1-6.2-8.5"/></svg></span><div><h3>현장 맞춤 컨설팅</h3><p>사용 환경 진단 후 등급·규격·디스펜서까지 최적 조합을 제안, 비용 절감 데이터를 함께 제시합니다.</p></div></div>
            </div>
        </div>
    </section>

    {{-- 주요 고객 --}}
    <section class="about-sec">
        <div class="about-head">
            <div class="k">CUSTOMERS</div>
            <h2>주요 고객 · 적용 현장</h2>
            <p>산업 현장부터 다중이용시설까지 — 환경별로 검증된 안전·위생 솔루션을 공급합니다.</p>
        </div>
        <div class="cust-grid">
            <div class="cust-card"><h3>제조 · 산업 현장</h3><p>보호복 · 마스크 · 글러브 · 산업용 와이퍼</p></div>
            <div class="cust-card"><h3>건설 현장</h3><p>개인보호구 · 소방안전기구 · 현장 위생용품</p></div>
            <div class="cust-card"><h3>병원 · 의료기관</h3><p>센터풀 화장지 · 핸드타올 · 감염 관리 위생용품</p></div>
            <div class="cust-card"><h3>식품 · 외식업</h3><p>푸드서비스 타올 · 위생화 · 주방 위생용품</p></div>
            <div class="cust-card"><h3>오피스 · 다중이용시설</h3><p>워시룸 토탈 케어 · 미용티슈 · 방향제</p></div>
            <div class="cust-card"><h3>호텔 · 리조트 · 교육기관</h3><p>프리미엄 위생용품 · 디스펜서 시스템</p></div>
        </div>
    </section>

    {{-- CONTACT --}}
    <section class="about-sec" style="padding-top:0">
        <div class="contact-box">
            <div>
                <div class="k" style="color:var(--hivis)">CONTACT US</div>
                <h2 style="margin-top:10px">현장의 안전과 위생,<br>한국안전이 함께합니다.</h2>
                <div class="biz">주식회사 한국안전 &nbsp;|&nbsp; 사업자등록번호 101-86-83744 &nbsp;|&nbsp; 대표 임현규</div>
            </div>
            <dl class="cc">
                <div class="row"><dt>TEL</dt><dd>02-2273-9533</dd></div>
                <div class="row"><dt>FAX</dt><dd>02-2279-1354</dd></div>
                <div class="row"><dt>E-MAIL</dt><dd>hks2273@naver.com</dd></div>
                <div class="row"><dt>WEBSITE</dt><dd>www.korsafety.co.kr</dd></div>
                <div class="row"><dt>ADDRESS</dt><dd>서울특별시 종로구 돈화문로 94, 1층 (와룡동, 동원빌딩)</dd></div>
            </dl>
        </div>
    </section>
</div>
@endsection
