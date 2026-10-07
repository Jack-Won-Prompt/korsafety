@extends('manage.layout')
@section('title', '회원 초대')
@section('page', '회원 초대')
@section('crumb', '이메일로 가입 안내 발송 · 엑셀 명단 일괄 초대')
@section('actions')
    <a href="{{ route('admin.members') }}" class="btn btn-sm">회원 관리</a>
    <a href="{{ route('admin.invitations.template') }}" class="btn btn-sm">⭳ 엑셀 양식</a>
@endsection

@push('styles')
<style>
    .inv-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px}
    .inv-tabs a{padding:7px 14px;border-radius:999px;border:1px solid #e3e6ee;background:#fff;font-size:13px;font-weight:700;color:#555;text-decoration:none}
    .inv-tabs a.on{background:#1b2130;border-color:#1b2130;color:#fff}
    .inv-tabs a b{margin-left:4px;font-weight:800}
</style>
@endpush

@section('content')
@php $tabUrl = fn ($s) => route('admin.invitations', array_merge(request()->except(['page','status']), ['status' => $s])); @endphp

<div class="tiles">
    <div class="tile">
        <div class="lab">보낸 초대</div>
        <div class="val">{{ number_format($stats['all']) }}<span class="won"> 건</span></div>
        <div class="sub">가입 완료 {{ number_format($stats['accepted']) }}건</div>
    </div>
    <div class="tile">
        <div class="lab">응답 대기</div>
        <div class="val" style="{{ $stats['sent'] ? 'color:#a35a06' : '' }}">{{ number_format($stats['sent']) }}<span class="won"> 건</span></div>
        <div class="sub">아직 가입하지 않은 초대</div>
    </div>
    <div class="tile">
        <div class="lab">승인 대기 회원</div>
        <div class="val" style="{{ $stats['waiting'] ? 'color:#c11c0f' : '' }}">{{ number_format($stats['waiting']) }}<span class="won"> 명</span></div>
        <div class="sub"><a href="{{ route('admin.members', ['approval' => 'pending']) }}">승인 처리하러 가기 →</a></div>
    </div>
    <div class="tile">
        <div class="lab">취소된 초대</div>
        <div class="val">{{ number_format($stats['cancelled']) }}<span class="won"> 건</span></div>
        <div class="sub">링크가 열리지 않습니다</div>
    </div>
</div>

{{-- 초대 보내기 --}}
<div class="panel">
    <div class="panel-h"><div><h2>초대 보내기</h2><div class="sub">초대 링크는 {{ \App\Models\Invitation::VALID_DAYS }}일간 유효합니다</div></div></div>
    <div class="panel-b">
        <form method="post" action="{{ route('admin.invitations.store') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">@csrf
            <input class="input" style="height:38px;flex:1 1 220px;min-width:180px" type="email" name="email" placeholder="이메일 *" required>
            <input class="input" style="height:38px;flex:0 0 130px" name="name" placeholder="이름">
            <input class="input" style="height:38px;flex:0 0 180px" name="company_name" placeholder="회사명">
            <select class="input" style="height:38px;flex:0 0 140px" name="role">
                @foreach(\App\Models\Invitation::ROLES as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-accent">초대 메일 보내기</button>
        </form>

        <div style="margin-top:18px;padding-top:18px;border-top:1px solid #eef0f4">
            <form method="post" action="{{ route('admin.invitations.import') }}" enctype="multipart/form-data"
                  style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">@csrf
                <div style="font-weight:700;font-size:14px">엑셀 명단으로 일괄 초대</div>
                <input class="input" type="file" name="file" accept=".csv,.txt" required style="height:38px;flex:1 1 260px;padding:7px 10px">
                <button class="btn btn-sm">명단 올려서 보내기</button>
                <span class="t-sub">양식: 이메일 · 이름 · 회사명 · 구분(일반/협력사) — 위 "엑셀 양식"을 받아 사용하세요</span>
            </form>
        </div>
    </div>
</div>

<div class="inv-tabs">
    <a href="{{ $tabUrl('all') }}" class="{{ $status === 'all' ? 'on' : '' }}">전체<b>{{ number_format($stats['all']) }}</b></a>
    @foreach(\App\Models\Invitation::STATUSES as $k => $v)
        @if($k !== 'expired')
            <a href="{{ $tabUrl($k) }}" class="{{ $status === $k ? 'on' : '' }}">{{ $v }}<b>{{ number_format($stats[$k] ?? 0) }}</b></a>
        @endif
    @endforeach
</div>

<div class="panel">
    <div class="panel-h">
        <div><h2>초대 내역</h2><div class="sub">총 {{ number_format($invitations->total()) }}건</div></div>
        <form method="get" style="display:flex;gap:8px;align-items:center">
            <input type="hidden" name="status" value="{{ $status }}">
            <input class="input" style="height:34px;width:220px;font-size:12.5px" name="q" value="{{ $q }}" placeholder="이메일 · 이름 · 회사명">
            <button class="btn btn-sm">검색</button>
        </form>
    </div>
    <table class="table">
        <thead><tr>
            <th>이메일 / 이름</th>
            <th style="width:160px">회사명</th>
            <th style="width:100px">구분</th>
            <th style="width:100px">상태</th>
            <th style="width:130px">보낸 날짜</th>
            <th style="width:130px">유효 기간</th>
            <th style="width:170px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($invitations as $inv)
            <tr>
                <td>
                    <span class="t-name">{{ $inv->email }}</span>
                    <div class="t-sub">{{ $inv->name ?: '-' }}@if($inv->inviter) · 보낸 사람 {{ $inv->inviter->name }}@endif</div>
                </td>
                <td class="t-sub">{{ $inv->company_name ?: '-' }}</td>
                <td><span class="badge {{ $inv->role === 'partner' ? 'hq' : 'ok' }}">{{ $inv->role_label }}</span></td>
                <td><span class="badge {{ $inv->status_badge }}">{{ $inv->status_label }}</span></td>
                <td class="t-sub">{{ optional($inv->sent_at)->format('Y.m.d H:i') }}</td>
                <td class="t-sub">{{ optional($inv->expires_at)->format('Y.m.d') }}까지</td>
                <td>
                    <div style="display:flex;gap:6px">
                        @if($inv->status === 'accepted')
                            @if($inv->user)<a href="{{ route('admin.members.show', $inv->user) }}" class="btn btn-sm">회원 보기</a>@endif
                        @else
                            <form method="post" action="{{ route('admin.invitations.resend', $inv) }}">@csrf
                                <button class="btn btn-sm">다시 보내기</button>
                            </form>
                            @if($inv->status !== 'cancelled')
                                <form method="post" action="{{ route('admin.invitations.cancel', $inv) }}"
                                      onsubmit="return confirm('이 초대를 취소할까요? 링크가 열리지 않게 됩니다.')">@csrf
                                    <button class="btn btn-sm btn-danger">취소</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">보낸 초대가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $invitations->links('manage.pagination') }}
@endsection
