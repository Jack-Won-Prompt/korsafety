@extends('manage.layout')
@section('title', 'SR 관리')
@section('page', 'SR · 서비스 요청')
@section('crumb', $isStaff ? '접수된 요청 처리 · 담당 · 상태 관리' : '내가 등록한 서비스 요청')
@section('actions')
    <a href="{{ route('manage.sr.create') }}" class="btn btn-accent btn-sm">+ SR 등록</a>
@endsection

@section('content')
@php
    $tiles = [
        ['all', '전체', $total],
        ['open', 'SR 접수', $counts['open'] ?? 0],
        ['in_progress', '처리중', $counts['in_progress'] ?? 0],
        ['resolved', '처리완료', $counts['resolved'] ?? 0],
        ['closed', '종료', $counts['closed'] ?? 0],
    ];
@endphp
<div class="tiles" style="grid-template-columns:repeat(5,1fr)">
    @foreach($tiles as [$key, $label, $val])
        @php $url = $key === 'all' ? route('manage.sr.index') : route('manage.sr.index', ['status' => $key]); @endphp
        <a href="{{ $url }}" class="tile" style="{{ ($status ?: 'all') === $key ? 'border-color:var(--accent)' : '' }}">
            <div class="lab">{{ $label }}</div>
            <div class="val">{{ number_format($val) }}<span class="won"> 건</span></div>
        </a>
    @endforeach
</div>

<div class="panel">
    <div class="panel-b">
        <form method="get" style="display:flex;flex-wrap:nowrap;gap:8px;align-items:center;width:100%">
            <input class="input" style="height:38px;flex:1 1 0;min-width:120px" name="q" value="{{ $q }}" placeholder="SR번호·제목·내용 검색">
            <select class="input" style="height:38px;flex:0 0 110px" name="status">
                <option value="">전체 상태</option>
                @foreach(\App\Models\ServiceRequest::STATUSES as $k => $v)
                    <option value="{{ $k }}" @selected($status === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <select class="input" style="height:38px;flex:0 0 120px" name="category">
                <option value="">전체 유형</option>
                @foreach(\App\Models\ServiceRequest::CATEGORIES as $k => $v)
                    <option value="{{ $k }}" @selected($category === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <select class="input" style="height:38px;flex:0 0 100px" name="priority">
                <option value="">전체 중요도</option>
                @foreach(\App\Models\ServiceRequest::PRIORITIES as $k => $v)
                    <option value="{{ $k }}" @selected($priority === $k)>{{ $v }}</option>
                @endforeach
            </select>
            @if($isStaff)
                <label class="t-sub" style="flex:0 0 auto;display:flex;align-items:center;gap:5px;white-space:nowrap">
                    <input type="checkbox" name="mine" value="1" @checked($mine)> 내 담당
                </label>
            @endif
            <button class="btn btn-sm btn-accent" style="flex:0 0 auto">검색</button>
            <a href="{{ route('manage.sr.index') }}" class="btn btn-sm" style="flex:0 0 auto">초기화</a>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-h">
        <div><h2>{{ $isStaff ? '전체 SR' : '내 SR' }}</h2><div class="sub">총 {{ number_format($requests->total()) }}건</div></div>
    </div>
    <table class="table">
        <thead><tr>
            <th style="width:118px">SR 번호</th>
            <th style="width:88px">유형</th>
            <th>제목</th>
            <th style="width:70px">중요도</th>
            @if($isStaff)<th style="width:110px">요청자</th>@endif
            <th style="width:100px">담당자</th>
            <th style="width:60px">답글</th>
            <th style="width:80px">상태</th>
            <th style="width:110px">등록일</th>
        </tr></thead>
        <tbody>
        @forelse($requests as $sr)
            <tr>
                <td class="t-name"><a href="{{ route('manage.sr.show', $sr) }}">{{ $sr->sr_no }}</a></td>
                <td class="t-sub">{{ $sr->category_label }}</td>
                <td><a href="{{ route('manage.sr.show', $sr) }}" class="t-name">{{ \Illuminate\Support\Str::limit($sr->title, 50) }}</a></td>
                <td>
                    @if(in_array($sr->priority, ['high', 'urgent']))
                        <span class="badge {{ $sr->priority === 'urgent' ? 'off' : 'warn' }}">{{ $sr->priority_label }}</span>
                    @else
                        <span class="t-sub">{{ $sr->priority_label }}</span>
                    @endif
                </td>
                @if($isStaff)<td class="t-sub">{{ $sr->user->name ?? '-' }}</td>@endif
                <td class="t-sub">{{ $sr->assignee->name ?? '미지정' }}</td>
                <td class="t-sub">{{ $sr->reply_count }}</td>
                <td><span class="badge {{ $sr->status_badge }}">{{ $sr->status_label }}</span></td>
                <td class="t-sub">{{ optional($sr->created_at)->format('Y.m.d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="{{ $isStaff ? 9 : 8 }}" class="empty">등록된 SR이 없습니다. ‘SR 등록’으로 요청을 남겨 주세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $requests->links('manage.pagination') }}
@endsection
