@php
    $pusherKey = config('broadcasting.connections.pusher.key');
    $pusherCluster = config('broadcasting.connections.pusher.options.cluster');
    $pusherReady = config('broadcasting.default') === 'pusher' && $pusherKey;
@endphp
<div id="ks-chat" class="ks-chat"
     data-start="{{ route('inquiry.start') }}"
     data-pusher-key="{{ $pusherReady ? $pusherKey : '' }}"
     data-pusher-cluster="{{ $pusherCluster }}">
    <button type="button" class="ks-chat-fab" aria-label="문의하기">
        <svg class="ic-chat" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 11.5a8.38 8.38 0 0 1-8.5 8.5 8.5 8.5 0 0 1-3.8-.9L3 21l1.9-5.7a8.5 8.5 0 0 1-.9-3.8A8.38 8.38 0 0 1 12.5 3 8.38 8.38 0 0 1 21 11.5z"/>
        </svg>
        <svg class="ic-close" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        <span class="ks-chat-label">문의하기</span>
    </button>

    <div class="ks-chat-panel" hidden>
        <div class="ks-chat-head">
            <div>
                <div class="ks-chat-title">실시간 문의</div>
                <div class="ks-chat-sub">한국안전 상담원과 채팅</div>
            </div>
            <button type="button" class="ks-chat-min" aria-label="닫기">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>

        {{-- 1단계: 이름·전화번호 등록 --}}
        <form class="ks-chat-reg" autocomplete="on">
            <p class="ks-chat-intro">상담을 위해 이름과 연락처를 남겨 주세요.<br>상담원이 실시간으로 답변드립니다.</p>
            <label>이름 <span>*</span>
                <input type="text" name="name" maxlength="40" required placeholder="홍길동">
            </label>
            <label>전화번호 <span>*</span>
                <input type="tel" name="phone" maxlength="30" required placeholder="010-1234-5678" inputmode="tel">
            </label>
            <label>문의 내용 <span class="opt">(선택)</span>
                <textarea name="message" maxlength="2000" rows="2" placeholder="궁금하신 내용을 입력해 주세요."></textarea>
            </label>
            <div class="ks-chat-err" hidden></div>
            <button type="submit" class="ks-chat-submit">상담 시작하기</button>
            <p class="ks-chat-priv">· 입력하신 정보는 상담 목적으로만 사용됩니다.</p>
        </form>

        {{-- 2단계: 대화 --}}
        <div class="ks-chat-thread" hidden>
            <div class="ks-chat-msgs"></div>
            <form class="ks-chat-send">
                <textarea name="body" maxlength="2000" rows="1" placeholder="메시지를 입력하세요" required></textarea>
                <button type="submit" aria-label="전송">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>

@if($pusherReady)
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
@endif
<script src="{{ asset('js/chat.js') }}?v={{ @filemtime(public_path('js/chat.js')) }}"></script>
