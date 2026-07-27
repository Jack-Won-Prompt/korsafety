@extends('manage.layout')
@section('title', '문의 대화 · '.$inquiry->name)
@section('page', '문의 대화')
@section('crumb', $inquiry->name.' 님과의 상담')
@section('actions')
    <a href="{{ route('admin.inquiries') }}" class="btn btn-sm">← 목록</a>
    <form action="{{ route('admin.inquiries.toggle', $inquiry) }}" method="post" style="display:inline">@csrf
        <button class="btn btn-sm">{{ $inquiry->status === 'open' ? '상담 종료' : '상담 재개' }}</button>
    </form>
@endsection

@section('content')
@php
    $pusherKey = config('broadcasting.connections.pusher.key');
    $pusherReady = config('broadcasting.default') === 'pusher' && $pusherKey;
@endphp
<div class="panel ac-panel"
     data-token="{{ $inquiry->token }}"
     data-poll="{{ route('admin.inquiries.poll', $inquiry) }}"
     data-reply="{{ route('admin.inquiries.reply', $inquiry) }}"
     data-pusher-key="{{ $pusherReady ? $pusherKey : '' }}"
     data-pusher-cluster="{{ config('broadcasting.connections.pusher.options.cluster') }}">
    <div class="ac-head">
        <div class="ac-who">
            <div class="ac-avatar">{{ mb_substr($inquiry->name, 0, 1) }}</div>
            <div>
                <div class="ac-name">{{ $inquiry->name }} <span class="badge {{ $inquiry->status === 'open' ? 'ok' : 'off' }}">{{ $inquiry->status === 'open' ? '진행중' : '종료' }}</span></div>
                <div class="ac-phone"><a href="tel:{{ $inquiry->phone }}">☎ {{ $inquiry->phone }}</a> · 접수 {{ $inquiry->created_at->format('Y-m-d H:i') }}</div>
            </div>
        </div>
        <div class="sub">{{ $pusherReady ? '● 실시간' : '○ 자동 새로고침' }}</div>
    </div>

    <div class="ac-msgs" id="ac-msgs">
        @foreach($inquiry->messages as $m)
            <div class="ac-msg {{ $m->sender === 'admin' ? 'out' : 'in' }}" data-mid="{{ $m->id }}">
                <div class="ac-bubble">{!! nl2br(e($m->body)) !!}</div>
                <div class="ac-time">{{ $m->created_at->format('m/d H:i') }}</div>
            </div>
        @endforeach
    </div>

    <form class="ac-send" id="ac-send">
        <textarea name="body" rows="1" maxlength="2000" placeholder="답변을 입력하세요 (Enter 전송 / Shift+Enter 줄바꿈)" required></textarea>
        <button type="submit" class="btn btn-accent">전송</button>
    </form>
</div>

@push('scripts')
@if($pusherReady)<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>@endif
<script>
(function(){
    var panel = document.querySelector('.ac-panel');
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var box = document.getElementById('ac-msgs');
    var form = document.getElementById('ac-send');
    var input = form.querySelector('textarea');
    var token = panel.dataset.token, pollUrl = panel.dataset.poll, replyUrl = panel.dataset.reply;
    var pKey = panel.dataset.pusherKey, pCluster = panel.dataset.pusherCluster;
    var seen = {}, lastId = 0;

    // 기존 메시지 id 등록
    Array.prototype.forEach.call(box.querySelectorAll('[data-mid]'), function(el){
        var id = +el.dataset.mid; seen[id] = true; if (id > lastId) lastId = id;
    });
    box.scrollTop = box.scrollHeight;

    function esc(s){ var d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
    function add(m){
        if(!m || seen[m.id]) return; seen[m.id]=true; if(m.id>lastId) lastId=m.id;
        var w=document.createElement('div');
        w.className='ac-msg '+(m.sender==='admin'?'out':'in'); w.dataset.mid=m.id;
        var t=new Date(m.created_at); var pad=function(n){return n<10?'0'+n:n;};
        w.innerHTML='<div class="ac-bubble">'+esc(m.body).replace(/\n/g,'<br>')+'</div>'+
            '<div class="ac-time">'+pad(t.getMonth()+1)+'/'+pad(t.getDate())+' '+pad(t.getHours())+':'+pad(t.getMinutes())+'</div>';
        box.appendChild(w); box.scrollTop=box.scrollHeight;
    }

    // 실시간 or 폴링
    if(pKey && window.Pusher){
        try{
            var pusher=new Pusher(pKey,{cluster:pCluster,forceTLS:true});
            pusher.subscribe('inquiry.'+token).bind('message.sent', add);
        }catch(e){ poll(); setInterval(poll,3000); }
    } else {
        setInterval(poll,3000);
    }
    function poll(){
        if(document.hidden) return;
        fetch(pollUrl+'?after='+lastId,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}})
            .then(function(r){return r.json();}).then(function(d){(d.messages||[]).forEach(add);}).catch(function(){});
    }

    // 전송
    form.addEventListener('submit', function(e){
        e.preventDefault();
        var body=input.value.trim(); if(!body) return;
        input.value=''; grow();
        var fd=new FormData(); fd.append('body',body);
        fetch(replyUrl,{method:'POST',headers:{'X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:fd})
            .then(function(r){return r.json();}).then(add).catch(function(){});
    });
    input.addEventListener('keydown', function(e){
        if(e.key==='Enter' && !e.shiftKey){ e.preventDefault(); form.requestSubmit(); }
    });
    function grow(){ input.style.height='auto'; input.style.height=Math.min(input.scrollHeight,120)+'px'; }
    input.addEventListener('input', grow);
    input.focus();
})();
</script>
@endpush
@endsection
