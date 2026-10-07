@extends('manage.layout')
@section('title', '회원 관리')
@section('page', '회원 관리')
@section('crumb', '가입 회원 조회 · 비밀번호 재설정 안내 · 이용 정지')

@push('styles')
<style>
    .mb-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
    .mb-tabs a{padding:7px 14px;border-radius:999px;border:1px solid #e3e6ee;background:#fff;font-size:13px;font-weight:700;color:#555;text-decoration:none}
    .mb-tabs a.on{background:#1b2130;border-color:#1b2130;color:#fff}
    .mb-tabs a b{margin-left:4px;font-weight:800}
</style>
@endpush

@section('content')
@php
    $tabUrl = fn ($r) => route('admin.members', array_merge(request()->except(['page', 'role']), ['role' => $r]));
    $roleBadge = ['customer' => 'ok', 'hq_admin' => 'hq', 'seller' => 'warn', 'agent' => 'warn', 'purchaser' => 'warn'];
@endphp

{{-- 요약 --}}
<div class="tiles">
    <div class="tile">
        <div class="lab">전체 회원</div>
        <div class="val">{{ number_format($stats['all']) }}<span class="won"> 명</span></div>
        <div class="sub">일반 회원 {{ number_format($stats['customer']) }}명</div>
    </div>
    <div class="tile">
        <div class="lab">오늘 가입</div>
        <div class="val">{{ number_format($stats['today']) }}<span class="won"> 명</span></div>
        <div class="sub">최근 7일 {{ number_format($stats['week']) }}명</div>
    </div>
    <div class="tile">
        <div class="lab">판매점 · 협력사 · 구매대행</div>
        <div class="val">{{ number_format($stats['seller'] + $stats['agent'] + $stats['purchaser']) }}<span class="won"> 명</span></div>
        <div class="sub">판매점 {{ $stats['seller'] }} · 협력사 {{ $stats['agent'] }} · 구매대행 {{ $stats['purchaser'] }}</div>
    </div>
    <div class="tile">
        <div class="lab">이용 정지</div>
        <div class="val" style="{{ $stats['suspended'] ? 'color:#c11c0f' : '' }}">{{ number_format($stats['suspended']) }}<span class="won"> 명</span></div>
        <div class="sub">로그인할 수 없는 계정</div>
    </div>
</div>

<div class="mb-tabs">
    @foreach(\App\Http\Controllers\Admin\MemberController::ROLES as $k => $v)
        <a href="{{ $tabUrl($k) }}" class="{{ $role === $k ? 'on' : '' }}">{{ $v }}<b>{{ number_format($stats[$k] ?? 0) }}</b></a>
    @endforeach
    <a href="{{ $tabUrl('all') }}" class="{{ $role === 'all' ? 'on' : '' }}">전체<b>{{ number_format($stats['all']) }}</b></a>
</div>

{{-- 검색 --}}
<div class="panel">
    <div class="panel-b">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;width:100%">
            <input type="hidden" name="role" value="{{ $role }}">
            <input class="input" style="height:38px;flex:1 1 220px;min-width:160px" name="q" value="{{ $q }}" placeholder="이름 · 이메일 검색">
            <select class="input" style="height:38px;flex:0 0 130px" name="state">
                <option value="">전체 상태</option>
                <option value="active" @selected($state === 'active')>이용중</option>
                <option value="suspended" @selected($state === 'suspended')>이용 정지</option>
            </select>
            <span class="t-sub">가입일</span>
            <input class="input" style="height:38px;flex:0 0 138px" type="date" name="from" value="{{ $from }}">
            <span class="t-sub">~</span>
            <input class="input" style="height:38px;flex:0 0 138px" type="date" name="to" value="{{ $to }}">
            <button class="btn btn-sm btn-accent">조회</button>
            <a href="{{ route('admin.members') }}" class="btn btn-sm">초기화</a>
        </form>
    </div>
</div>

{{-- 목록 --}}
<div class="panel">
    <div class="panel-h">
        <div><h2>회원 목록</h2><div class="sub">총 {{ number_format($members->total()) }}명 · 비밀번호는 보관하지 않으므로 재설정 링크를 메일로 보냅니다</div></div>
    </div>
    <table class="table">
        <thead><tr>
            <th>이름 / 이메일</th>
            <th style="width:96px">구분</th>
            <th style="width:120px">주문</th>
            <th style="width:120px">가입일</th>
            <th style="width:130px">최근 로그인</th>
            <th style="width:90px">상태</th>
            <th style="width:230px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($members as $m)
            <tr>
                <td>
                    <a class="t-name" href="{{ route('admin.members.show', $m) }}">{{ $m->name }}</a>
                    <div class="t-sub">{{ $m->email }}</div>
                </td>
                <td><span class="badge {{ $roleBadge[$m->role] ?? 'off' }}">{{ \App\Http\Controllers\Admin\MemberController::ROLES[$m->role] ?? ($m->role ?: '일반 회원') }}</span></td>
                <td class="t-sub">
                    @if($m->orders_count){{ number_format($m->orders_count) }}건<div style="font-size:11px">{{ number_format((int) $m->orders_sum_total) }}원</div>@else-@endif
                </td>
                <td class="t-sub">{{ optional($m->created_at)->format('Y.m.d') }}</td>
                <td class="t-sub">{{ $m->last_login_at ? \Illuminate\Support\Carbon::parse($m->last_login_at)->format('Y.m.d H:i') : '기록 없음' }}</td>
                <td>
                    @if($m->suspended_at)<span class="badge off">정지</span>@else<span class="badge ok">이용중</span>@endif
                </td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <a href="{{ route('admin.members.show', $m) }}" class="btn btn-sm">상세</a>
                        <form method="post" action="{{ route('admin.members.reset-link', $m) }}"
                              onsubmit="return confirm('{{ $m->email }} 로 비밀번호 재설정 링크를 보낼까요?')">@csrf
                            <button class="btn btn-sm">비밀번호 재설정</button>
                        </form>
                        <form method="post" action="{{ route('admin.members.suspend', $m) }}"
                              onsubmit="return confirm('{{ $m->suspended_at ? '이용 정지를 해제할까요?' : '이 회원을 이용 정지할까요? 로그인할 수 없게 됩니다.' }}')">@csrf
                            <button class="btn btn-sm {{ $m->suspended_at ? '' : 'btn-danger' }}">{{ $m->suspended_at ? '정지 해제' : '이용 정지' }}</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">조건에 맞는 회원이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $members->links('manage.pagination') }}
@endsection
