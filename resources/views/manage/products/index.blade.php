@extends('manage.layout')
@section('title', '상품 관리')
@section('page', '상품 관리')
@section('crumb', '내 스토어 상품 등록 · 수정')
@section('actions')
    <a href="{{ route('manage.products.export') }}" class="btn btn-sm">⭳ 엑셀 다운로드</a>
    <button type="button" class="btn btn-sm" onclick="document.getElementById('imp-box').hidden=!document.getElementById('imp-box').hidden">⭱ 엑셀 업로드</button>
    <a href="{{ route('manage.products.create') }}" class="btn btn-accent btn-sm">+ 상품 등록</a>
@endsection

@section('content')
<div id="imp-box" class="panel" hidden>
    <div class="panel-b">
        <form action="{{ route('manage.products.import') }}" method="post" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
            @csrf
            <div style="font-weight:700;font-size:14px">엑셀(CSV) 업로드</div>
            <input type="file" name="file" accept=".csv,text/csv" required class="input" style="max-width:320px">
            <button class="btn btn-accent btn-sm" type="submit">반영하기</button>
            <a href="{{ route('manage.products.import.template') }}" class="btn btn-sm">빈 양식 받기</a>
            <span class="t-sub" style="flex-basis:100%;margin-top:4px">‘상품ID’가 있으면 수정, 비어 있으면 신규 등록됩니다. 카테고리는 이름이 일치할 때 연결됩니다. UTF-8 CSV 형식.</span>
        </form>
    </div>
</div>
<div class="panel">
    <div class="panel-h">
        <div><h2>내 상품</h2><div class="sub">총 {{ number_format($products->total()) }}개</div></div>
        <form method="get" style="display:flex;gap:8px">
            <input class="input" style="height:38px;width:220px" name="q" value="{{ $q }}" placeholder="상품명·브랜드 검색">
            <button class="btn btn-sm">검색</button>
        </form>
    </div>
    <table class="table">
        <thead><tr><th style="width:60px">이미지</th><th>상품명</th><th>카테고리</th><th style="text-align:right">판매가</th><th>상태</th><th style="width:150px">관리</th></tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>@if($p->main_image)<img class="thumb" src="{{ asset($p->main_image) }}" alt="" onerror="this.style.visibility='hidden'">@else<div class="thumb"></div>@endif</td>
                <td><span class="t-name">{{ \Illuminate\Support\Str::limit($p->name, 40) }}</span><div class="t-sub">{{ $p->brand }}</div></td>
                <td class="t-sub">{{ $p->category->name ?? '-' }}</td>
                <td style="text-align:right;font-weight:800">{{ $p->final_price ? number_format($p->final_price).'원' : '—' }}</td>
                <td>@if($p->is_soldout)<span class="badge off">품절</span>@else<span class="badge ok">판매중</span>@endif</td>
                <td>
                    <div style="display:flex;gap:6px">
                        <a href="{{ route('manage.products.edit', $p) }}" class="btn btn-sm">수정</a>
                        @if($p->main_image)<a href="{{ route('manage.products.image', $p) }}" class="btn btn-sm" title="이미지 편집">✎</a>@endif
                        <form action="{{ route('manage.products.destroy', $p) }}" method="post" onsubmit="return confirm('삭제하시겠습니까?')">@csrf @method('DELETE')<button class="btn btn-sm btn-danger">삭제</button></form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">등록된 상품이 없습니다. ‘상품 등록’으로 첫 상품을 추가해 보세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $products->links('manage.pagination') }}
@endsection
