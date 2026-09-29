@extends('manage.layout')
@section('title', '삭제한 상품')
@section('page', '삭제한 상품 (휴지통)')
@section('crumb', '삭제한 상품 되살리기 · 완전 삭제')
@section('actions')
    <a href="{{ route('manage.products.index') }}" class="btn btn-sm">← 상품 관리</a>
@endsection

@section('content')
<div class="panel">
    <div class="panel-b">
        <form method="get" style="display:flex;gap:8px;align-items:center;width:100%">
            <input class="input" style="height:38px;flex:1 1 0;min-width:140px" name="q" value="{{ $q }}" placeholder="상품명 · 브랜드 · SKU · 상품코드 · 상품ID 검색">
            <select class="input" style="height:38px;flex:0 0 104px" name="per_page" title="한 번에 볼 개수">
                @foreach(\App\Http\Controllers\Manage\ProductController::PER_PAGES as $n)
                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}개씩</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-accent" style="flex:0 0 auto">검색</button>
            <a href="{{ route('manage.products.trash') }}" class="btn btn-sm" style="flex:0 0 auto">초기화</a>
        </form>
    </div>
</div>

<div class="panel">
    <div class="panel-h">
        <div><h2>삭제한 상품</h2><div class="sub">총 {{ number_format($products->total()) }}개 · 되살리면 <b>미노출</b> 상태 그대로 상품 관리 목록에 다시 나타납니다</div></div>
    </div>
    <table class="table">
        <thead><tr>
            <th style="width:56px">이미지</th>
            <th>상품명 / 코드 · SKU</th>
            <th style="width:110px">카테고리</th>
            <th style="width:112px">판매가</th>
            <th style="width:150px">삭제 일시</th>
            <th style="width:190px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($products as $p)
            <tr>
                <td>@if($p->main_image)<img class="thumb" src="{{ asset($p->main_image) }}" alt="" onerror="this.style.visibility='hidden'">@else<div class="thumb"></div>@endif</td>
                <td>
                    <span class="t-name">{{ \Illuminate\Support\Str::limit($p->name, 42) }}</span>
                    <div class="t-sub">
                        상품ID {{ $p->id }}@if($p->product_code) · <b class="pcode">{{ $p->product_code }}</b>@endif@if($p->sku) · SKU {{ $p->sku }}@endif@if($p->brand) · {{ $p->brand }}@endif
                    </div>
                </td>
                <td class="t-sub">{{ $p->category->name ?? '-' }}</td>
                <td class="t-sub">{{ $p->price ? number_format($p->price).'원' : '-' }}</td>
                <td class="t-sub">{{ optional($p->deleted_at)->format('Y.m.d H:i') }}</td>
                <td>
                    <div style="display:flex;gap:6px">
                        <form method="post" action="{{ route('manage.products.restore', $p->id) }}"
                              onsubmit="return confirm('이 상품을 되살릴까요?')">@csrf
                            <button class="btn btn-sm btn-accent">되살리기</button>
                        </form>
                        <form method="post" action="{{ route('manage.products.force-delete', $p->id) }}"
                              onsubmit="return confirm('완전히 삭제하면 되돌릴 수 없습니다. 진행할까요?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">완전 삭제</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">삭제한 상품이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $products->links('manage.pagination') }}
@endsection
