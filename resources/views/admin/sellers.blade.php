@extends('manage.layout')
@section('title', '판매점 관리')
@section('page', '판매점 관리')
@section('crumb', '입점 판매점 승인 및 관리')

@section('content')
<div class="panel">
    <div class="panel-h"><div><h2>입점 판매점</h2><div class="sub">총 {{ $sellers->count() }}곳 · 승인대기 {{ $sellers->where('status','pending')->count() }}곳</div></div></div>
    <table class="table">
        <thead>
            <tr><th>판매점</th><th>대표자 / 사업자번호</th><th>연락처</th><th>상품</th><th style="text-align:right">매출</th><th>상태</th><th style="width:230px">관리</th></tr>
        </thead>
        <tbody>
        @forelse($sellers as $s)
            <tr>
                <td><span class="t-name">{{ $s->name }}</span><div class="t-sub">{{ $s->email }}</div></td>
                <td>{{ $s->owner_name }}<div class="t-sub">{{ $s->business_no }}</div></td>
                <td class="t-sub">{{ $s->phone }}</td>
                <td>{{ number_format($s->products_count) }}</td>
                <td style="text-align:right;font-weight:800">{{ number_format($s->sales) }}원</td>
                <td>
                    @php $b=['pending'=>['warn','승인대기'],'approved'=>['ok','승인완료'],'suspended'=>['off','정지']][$s->status]; @endphp
                    <span class="badge {{ $b[0] }}">{{ $b[1] }}</span>
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        @if($s->status !== 'approved')
                            <form action="{{ route('admin.sellers.status', $s) }}" method="post">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-accent">승인</button></form>
                        @endif
                        @if($s->status !== 'suspended')
                            <form action="{{ route('admin.sellers.status', $s) }}" method="post">@csrf<input type="hidden" name="status" value="suspended"><button class="btn btn-sm btn-danger">정지</button></form>
                        @else
                            <form action="{{ route('admin.sellers.status', $s) }}" method="post">@csrf<input type="hidden" name="status" value="approved"><button class="btn btn-sm">정지해제</button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">입점 신청한 판매점이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
