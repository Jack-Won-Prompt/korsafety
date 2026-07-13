@extends('manage.layout')
@section('title', '협력사 관리')
@section('page', '협력사 관리')
@section('crumb', '영업대행 협력사 승인 · 수수료율 관리')

@section('content')
<div class="panel">
    <div class="panel-h"><div><h2>협력사</h2><div class="sub">총 {{ $agents->count() }}곳 · 승인대기 {{ $agents->where('status','pending')->count() }}곳</div></div></div>
    <table class="table">
        <thead><tr><th>협력사</th><th>대표자 / 사업자번호</th><th>거래처</th><th>주문</th><th>수수료율</th><th style="text-align:right">지급대기 커미션</th><th>상태</th><th style="width:170px">관리</th></tr></thead>
        <tbody>
        @forelse($agents as $a)
            <tr>
                <td><span class="t-name">{{ $a->name }}</span><div class="t-sub">{{ $a->email }}</div></td>
                <td>{{ $a->owner_name }}<div class="t-sub">{{ $a->business_no }}</div></td>
                <td>{{ $a->clients_count }}</td>
                <td>{{ $a->orders_count }}</td>
                <td>
                    <form action="{{ route('admin.agents.commission', $a) }}" method="post" style="display:flex;gap:5px;align-items:center">@csrf
                        <input class="input" style="height:32px;width:64px;padding:0 8px" type="number" step="0.5" name="commission_rate" value="{{ rtrim(rtrim($a->commission_rate,'0'),'.') }}">
                        <span style="font-size:12px;color:var(--muted)">%</span>
                        <button class="btn btn-sm">저장</button>
                    </form>
                </td>
                <td style="text-align:right;font-weight:800">{{ number_format($a->commission_pending) }}원</td>
                <td>@php $b=['pending'=>['warn','승인대기'],'approved'=>['ok','승인완료'],'suspended'=>['off','정지']][$a->status]; @endphp<span class="badge {{ $b[0] }}">{{ $b[1] }}</span></td>
                <td>
                    <div style="display:flex;gap:6px">
                        @if($a->status !== 'approved')
                            <form action="{{ route('admin.agents.status', $a) }}" method="post">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-accent">승인</button></form>
                        @endif
                        @if($a->status !== 'suspended')
                            <form action="{{ route('admin.agents.status', $a) }}" method="post">@csrf<input type="hidden" name="status" value="suspended"><button class="btn btn-sm btn-danger">정지</button></form>
                        @else
                            <form action="{{ route('admin.agents.status', $a) }}" method="post">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm">정지해제</button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">신청한 협력사가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
