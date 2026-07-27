@extends('manage.layout')
@section('title', '실시간 문의')
@section('page', '실시간 문의')
@section('crumb', '고객 채팅 상담 관리')

@section('content')
@php
    $pusherKey = config('broadcasting.connections.pusher.key');
    $pusherReady = config('broadcasting.default') === 'pusher' && $pusherKey;
@endphp
<div class="panel">
    <div class="panel-h">
        <div><h2>문의 목록</h2><div class="sub">진행중 {{ $openCount }}건 · 미확인 <b id="unread-total">{{ $unreadTotal }}</b>건</div></div>
        <div class="sub" id="rt-state">{{ $pusherReady ? '실시간 연결됨' : '자동 새로고침(3초)' }}</div>
    </div>
    <table class="table" id="inq-table">
        <thead><tr><th style="width:60px">상태</th><th>문의자</th><th>전화번호</th><th>최근 메시지</th><th style="width:70px">미확인</th><th style="width:110px">관리</th></tr></thead>
        <tbody>
        @forelse($inquiries as $inq)
            <tr data-id="{{ $inq->id }}">
                <td><span class="badge {{ $inq->status === 'open' ? 'ok' : 'off' }}">{{ $inq->status === 'open' ? '진행중' : '종료' }}</span></td>
                <td><span class="t-name">{{ $inq->name }}</span><div class="t-sub">메시지 {{ $inq->messages_count }}건</div></td>
                <td class="t-sub">{{ $inq->phone }}</td>
                <td class="t-sub">{{ optional($inq->last_message_at)->format('Y-m-d H:i') ?? '-' }}</td>
                <td>@if($inq->unread_admin)<span class="badge alert">{{ $inq->unread_admin }}</span>@else<span class="t-sub">0</span>@endif</td>
                <td><a href="{{ route('admin.inquiries.show', $inq) }}" class="btn btn-sm btn-accent">대화하기</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">아직 접수된 문의가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $inquiries->links('manage.pagination') }}

@push('scripts')
@if($pusherReady)
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<script>
(function(){
    var pusher = new Pusher(@json($pusherKey), { cluster: @json(config('broadcasting.connections.pusher.options.cluster')), forceTLS: true });
    var ch = pusher.subscribe('inquiries.admin');
    var timer = null;
    ch.bind('inquiry.activity', function(){
        // 핑 수신 → 목록 갱신(디바운스 후 새로고침)
        clearTimeout(timer);
        timer = setTimeout(function(){ location.reload(); }, 600);
    });
})();
</script>
@else
<script>
// Pusher 미설정 시 자동 새로고침 폴백(15초)
setInterval(function(){ if(!document.hidden) location.reload(); }, 15000);
</script>
@endif
@endpush
@endsection
