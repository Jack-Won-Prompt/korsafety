@extends('manage.layout')
@section('title', '구매 대행자 관리')
@section('page', '구매 대행자 관리')
@section('crumb', '구매 대행자 승인 · 캐쉬백 비율 관리')

@section('content')
<div class="panel">
    <div class="panel-h"><div><h2>구매 대행자</h2><div class="sub">총 {{ $purchasers->count() }}곳 · 승인대기 {{ $purchasers->where('status','pending')->count() }}곳</div></div></div>
    <table class="table">
        <thead><tr><th>구매 대행자</th><th>대표자 / 사업자번호</th><th>구매자</th><th>주문</th><th>캐쉬백율</th><th style="text-align:right">지급대기 캐쉬백</th><th>상태</th><th style="width:170px">관리</th></tr></thead>
        <tbody>
        @forelse($purchasers as $p)
            <tr>
                <td><span class="t-name">{{ $p->name }}</span><div class="t-sub">{{ $p->email }}</div></td>
                <td>{{ $p->owner_name }}<div class="t-sub">{{ $p->business_no }}</div></td>
                <td>{{ $p->buyers_count }}</td>
                <td>{{ $p->orders_count }}</td>
                <td>
                    <form action="{{ route('admin.purchasers.cashback', $p) }}" method="post" style="display:flex;gap:5px;align-items:center">@csrf
                        <input class="input" style="height:32px;width:64px;padding:0 8px" type="number" step="0.5" name="cashback_rate" value="{{ rtrim(rtrim($p->cashback_rate,'0'),'.') }}">
                        <span style="font-size:12px;color:var(--muted)">%</span>
                        <button class="btn btn-sm">저장</button>
                    </form>
                </td>
                <td style="text-align:right;font-weight:800">{{ number_format($p->cashback_pending) }}원</td>
                <td>@php $b=['pending'=>['warn','승인대기'],'approved'=>['ok','승인완료'],'suspended'=>['off','정지']][$p->status]; @endphp<span class="badge {{ $b[0] }}">{{ $b[1] }}</span></td>
                <td>
                    <div style="display:flex;gap:6px">
                        @if($p->status !== 'approved')
                            <form action="{{ route('admin.purchasers.status', $p) }}" method="post">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-accent">승인</button></form>
                        @endif
                        @if($p->status !== 'suspended')
                            <form action="{{ route('admin.purchasers.status', $p) }}" method="post">@csrf<input type="hidden" name="status" value="suspended"><button class="btn btn-sm btn-danger">정지</button></form>
                        @else
                            <form action="{{ route('admin.purchasers.status', $p) }}" method="post">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm">정지해제</button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">신청한 구매 대행자가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
