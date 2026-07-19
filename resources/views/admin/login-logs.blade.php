@extends('manage.layout')
@section('title', '로그인 이력')
@section('page', '로그인 이력')
@section('crumb', '관리 콘솔 로그인 기록 (성공·실패)')

@section('content')
<div class="tiles">
    <div class="tile"><div class="lab">전체 로그인</div><div class="val">{{ number_format($summary['total']) }}<span class="won">건</span></div></div>
    <div class="tile"><div class="lab">성공</div><div class="val" style="color:var(--ok)">{{ number_format($summary['success']) }}<span class="won">건</span></div></div>
    <div class="tile"><div class="lab">실패</div><div class="val" style="color:var(--danger)">{{ number_format($summary['failed']) }}<span class="won">건</span></div></div>
    <div class="tile"><div class="lab">오늘</div><div class="val">{{ number_format($summary['today']) }}<span class="won">건</span></div></div>
</div>

<div class="panel">
    <div class="panel-h">
        <div><h2>로그인 기록</h2><div class="sub">총 {{ number_format($logs->total()) }}건 · 최신순</div></div>
        <form method="get" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
            <select class="select" style="height:38px;width:auto;padding:0 10px" name="status" onchange="this.form.submit()">
                @foreach(['all'=>'전체 상태','success'=>'성공','failed'=>'실패'] as $k=>$v)
                    <option value="{{ $k }}" {{ $status===$k?'selected':'' }}>{{ $v }}</option>
                @endforeach
            </select>
            <select class="select" style="height:38px;width:auto;padding:0 10px" name="role" onchange="this.form.submit()">
                @foreach(['all'=>'전체 역할','hq_admin'=>'본사 관리자','seller'=>'판매점','agent'=>'협력사','purchaser'=>'구매 대행자','customer'=>'고객'] as $k=>$v)
                    <option value="{{ $k }}" {{ $role===$k?'selected':'' }}>{{ $v }}</option>
                @endforeach
            </select>
            <input class="input" style="height:38px;width:180px" name="q" value="{{ $q }}" placeholder="이메일·이름·IP 검색">
            <button class="btn btn-sm">검색</button>
        </form>
    </div>
    <table class="table">
        <thead><tr><th>일시</th><th>계정</th><th>역할</th><th>상태</th><th>IP</th><th>브라우저</th><th>비고</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td class="t-sub" style="white-space:nowrap">{{ $log->created_at?->format('Y.m.d H:i:s') }}</td>
                <td><span class="t-name">{{ $log->email }}</span>@if($log->name)<div class="t-sub">{{ $log->name }}</div>@endif</td>
                <td>{{ $log->role_label }}</td>
                <td>@if($log->status==='success')<span class="badge ok">성공</span>@else<span class="badge off">실패</span>@endif</td>
                <td class="t-sub">{{ $log->ip_address }}</td>
                <td class="t-sub">{{ $log->browser }}</td>
                <td class="t-sub">{{ $log->note }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">로그인 기록이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $logs->links('manage.pagination') }}
@endsection
