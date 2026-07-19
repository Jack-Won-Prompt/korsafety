@extends('manage.layout')
@section('title', '구매자 관리')
@section('page', '구매자 관리')
@section('crumb', '소매처 · 구매자 정보')
@section('actions')
    <a href="{{ route('purchaser.buyers.create') }}" class="btn btn-accent btn-sm">+ 구매자 등록</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-h"><div><h2>구매자(소매처)</h2><div class="sub">총 {{ number_format($buyers->total()) }}곳</div></div></div>
    <table class="table">
        <thead><tr><th>소매처명</th><th>구매자 이름</th><th>사업자번호</th><th>전화번호</th><th>주문</th><th style="width:150px">관리</th></tr></thead>
        <tbody>
        @forelse($buyers as $b)
            <tr>
                <td class="t-name">{{ $b->shop_name }}</td>
                <td>{{ $b->name }}</td>
                <td class="t-sub">{{ $b->business_no }}</td>
                <td class="t-sub">{{ $b->phone }}</td>
                <td>{{ $b->orders_count }}건</td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('purchaser.buyers.edit', $b) }}" class="btn btn-sm">수정</a>
                        <form action="{{ route('purchaser.buyers.destroy', $b) }}" method="post" onsubmit="return confirm('삭제하시겠습니까?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">삭제</button></form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">등록된 구매자가 없습니다. ‘구매자 등록’으로 추가해 보세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $buyers->links('manage.pagination') }}
@endsection
