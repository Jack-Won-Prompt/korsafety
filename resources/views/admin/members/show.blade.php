@extends('manage.layout')
@section('title', '회원 상세')
@section('page', $member->name.' 회원')
@section('crumb', '가입 정보 · 주문 이력 · 로그인 이력')
@section('actions')
    <a href="{{ route('admin.members') }}" class="btn btn-sm">← 회원 목록</a>
@endsection

@push('styles')
<style>
    .mb-dl{display:grid;grid-template-columns:120px 1fr;gap:10px 14px;font-size:13.5px;align-items:center}
    .mb-dl dt{color:#8a90a0;font-weight:700;margin:0}
    .mb-dl dd{margin:0;word-break:break-all}
</style>
@endpush

@section('content')
{{-- 가입 정보 --}}
<div class="panel">
    <div class="panel-h">
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            @if($member->suspended_at)<span class="badge off" style="font-size:13px;padding:6px 14px">이용 정지</span>
            @else<span class="badge ok" style="font-size:13px;padding:6px 14px">이용중</span>@endif
            <div>
                <h2 style="margin:0">{{ $member->name }}</h2>
                <div class="sub">{{ $member->email }}</div>
            </div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <form method="post" action="{{ route('admin.members.reset-link', $member) }}"
                  onsubmit="return confirm('{{ $member->email }} 로 비밀번호 재설정 링크를 보낼까요?')">@csrf
                <button class="btn btn-sm">비밀번호 재설정 링크 보내기</button>
            </form>
            <form method="post" action="{{ route('admin.members.suspend', $member) }}"
                  onsubmit="return confirm('{{ $member->suspended_at ? '이용 정지를 해제할까요?' : '이 회원을 이용 정지할까요? 로그인할 수 없게 됩니다.' }}')">@csrf
                <button class="btn btn-sm {{ $member->suspended_at ? '' : 'btn-danger' }}">{{ $member->suspended_at ? '정지 해제' : '이용 정지' }}</button>
            </form>
            @if($member->role === 'customer' && $summary['orders'] === 0)
                <form method="post" action="{{ route('admin.members.destroy', $member) }}"
                      onsubmit="return confirm('이 회원을 삭제할까요? 되돌릴 수 없습니다.')">@csrf @method('DELETE')
                    <button class="btn btn-sm btn-danger">회원 삭제</button>
                </form>
            @endif
        </div>
    </div>
    <div class="panel-b">
        <dl class="mb-dl">
            <dt>구분</dt><dd>{{ \App\Http\Controllers\Admin\MemberController::ROLES[$member->role] ?? ($member->role ?: '일반 회원') }}</dd>
            <dt>가입일</dt><dd>{{ optional($member->created_at)->format('Y.m.d H:i') }}</dd>
            @if($member->suspended_at)
                <dt>정지 일시</dt><dd>{{ $member->suspended_at->format('Y.m.d H:i') }}</dd>
            @endif
            <dt>주문</dt><dd><b>{{ number_format($summary['orders']) }}건</b> · {{ number_format($summary['amount']) }}원
                @if($summary['last_order_at']) · 최근 {{ \Illuminate\Support\Carbon::parse($summary['last_order_at'])->format('Y.m.d') }}@endif
            </dd>
            @if($member->seller)<dt>소속 판매점</dt><dd>{{ $member->seller->name }}</dd>@endif
        </dl>
    </div>
</div>

{{-- 주문 이력 --}}
<div class="panel">
    <div class="panel-h"><div><h2>최근 주문</h2><div class="sub">최대 10건</div></div></div>
    <table class="table">
        <thead><tr><th style="width:150px">주문번호</th><th style="width:130px">주문일</th><th>받는 분</th><th style="width:120px">금액</th><th style="width:90px">상태</th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td class="t-name">{{ $o->order_no }}</td>
                <td class="t-sub">{{ optional($o->created_at)->format('Y.m.d H:i') }}</td>
                <td class="t-sub">{{ $o->receiver_name ?: $o->customer_name }}</td>
                <td>{{ number_format((int) $o->total) }}원</td>
                <td class="t-sub">{{ $o->status }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">주문 이력이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

{{-- 로그인 이력 --}}
<div class="panel">
    <div class="panel-h"><div><h2>최근 로그인</h2><div class="sub">최대 10건 · 실패 기록 포함</div></div></div>
    <table class="table">
        <thead><tr><th style="width:150px">일시</th><th style="width:80px">결과</th><th style="width:130px">IP</th><th>비고 / 접속 환경</th></tr></thead>
        <tbody>
        @forelse($logins as $l)
            <tr>
                <td class="t-sub">{{ optional($l->created_at)->format('Y.m.d H:i:s') }}</td>
                <td>@if($l->status === 'success')<span class="badge ok">성공</span>@else<span class="badge off">실패</span>@endif</td>
                <td class="t-sub">{{ $l->ip_address }}</td>
                <td class="t-sub">{{ $l->note }} @if($l->user_agent)<span style="font-size:11px">· {{ \Illuminate\Support\Str::limit($l->user_agent, 60) }}</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="4" class="empty">로그인 기록이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
