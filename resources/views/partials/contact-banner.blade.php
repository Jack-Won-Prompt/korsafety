{{--
    안전제품 제작·문의 연락처 배너 (모바일 앱의 ContactBanner와 동일한 설정값 사용)
    관리 콘솔 › 사이트 설정에서 노출 여부·문구·번호를 바꿀 수 있다.
    $variant: 'wide'(기본) | 'inline' — inline은 상품 상세처럼 좁은 칼럼에서 사용
--}}
@php
    $cbEnabled = \App\Models\Setting::bool('contact_banner_enabled');
    $cbText    = trim((string) \App\Models\Setting::get('contact_banner_text'));
    $cbPhone   = trim((string) \App\Models\Setting::get('contact_banner_phone'));
    $cbTel     = preg_replace('/[^0-9+]/', '', $cbPhone);
    $variant   = $variant ?? 'wide';
@endphp

@if($cbEnabled && $cbTel !== '')
<a class="cbanner cbanner-{{ $variant }}" href="tel:{{ $cbTel }}" aria-label="{{ $cbText ?: '제품 문의' }} {{ $cbPhone }} 전화하기">
    <span class="cb-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 12a8 8 0 0 1 16 0"/>
            <path d="M4 12v3a2 2 0 0 0 2 2h1v-5H6a2 2 0 0 0-2 2zM20 12v3a2 2 0 0 1-2 2h-1v-5h1a2 2 0 0 1 2 2z"/>
            <path d="M20 17v1a3 3 0 0 1-3 3h-3"/>
        </svg>
    </span>
    <span class="cb-body">
        @if($cbText !== '')<span class="cb-text">{{ $cbText }}</span>@endif
        <span class="cb-phone">{{ $cbPhone }}</span>
    </span>
    <span class="cb-cta">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>
        </svg>
        전화하기
    </span>
</a>
@endif
