@extends('manage.layout')
@section('title', '상품 관리')
@section('page', '상품 관리')
@section('crumb', '재고 · 가격 · 노출 · 카테고리 통합 관리')
@section('actions')
    @if(auth()->user()->isHqAdmin())
        <a href="{{ route('manage.categories.index') }}" class="btn btn-sm">카테고리 관리</a>
    @endif
    <a href="{{ route('manage.products.export') }}" class="btn btn-sm">⭳ 엑셀 다운로드</a>
    <button type="button" class="btn btn-sm" onclick="var b=document.getElementById('imp-box');b.hidden=!b.hidden">⭱ 엑셀 업로드</button>
    <a href="{{ route('manage.products.create') }}" class="btn btn-accent btn-sm">+ 상품 등록</a>
@endsection

@section('content')
@php
    $filterUrl = fn(array $params) => route('manage.products.index', array_merge(request()->query(), $params));
@endphp

{{-- 요약 --}}
<div class="tiles" style="grid-template-columns:repeat(5,1fr)">
    <a href="{{ route('manage.products.index') }}" class="tile">
        <div class="lab">전체 상품</div>
        <div class="val">{{ number_format($stats['total']) }}<span class="won"> 개</span></div>
    </a>
    <a href="{{ $filterUrl(['state' => 'onsale', 'stock' => null, 'page' => null]) }}" class="tile" style="{{ $state === 'onsale' ? 'border-color:var(--accent)' : '' }}">
        <div class="lab">판매중</div>
        <div class="val">{{ number_format($stats['onsale']) }}<span class="won"> 개</span></div>
    </a>
    <a href="{{ $filterUrl(['state' => 'soldout', 'stock' => null, 'page' => null]) }}" class="tile" style="{{ $state === 'soldout' ? 'border-color:var(--accent)' : '' }}">
        <div class="lab">품절</div>
        <div class="val">{{ number_format($stats['soldout']) }}<span class="won"> 개</span></div>
    </a>
    <a href="{{ $filterUrl(['state' => 'hidden', 'stock' => null, 'page' => null]) }}" class="tile" style="{{ $state === 'hidden' ? 'border-color:var(--accent)' : '' }}">
        <div class="lab">미노출</div>
        <div class="val">{{ number_format($stats['hidden']) }}<span class="won"> 개</span></div>
    </a>
    <a href="{{ $filterUrl(['stock' => 'low', 'state' => null, 'page' => null]) }}" class="tile" style="{{ $stock === 'low' ? 'border-color:var(--accent)' : '' }}">
        <div class="lab">재고 부족</div>
        <div class="val">{{ number_format($stats['low']) }}<span class="won"> 개</span></div>
        <div class="sub">재고 소진 {{ number_format($stats['out']) }}개 · 관리중 {{ number_format($stats['tracked']) }}개</div>
    </a>
</div>

<div id="imp-box" class="panel" hidden>
    <div class="panel-b">
        <form action="{{ route('manage.products.import') }}" method="post" enctype="multipart/form-data" style="display:flex;flex-wrap:wrap;align-items:center;gap:12px">
            @csrf
            <div style="font-weight:700;font-size:14px">엑셀(CSV) 업로드</div>
            <input type="file" name="file" accept=".csv,text/csv" required class="input" style="max-width:320px">
            <button class="btn btn-accent btn-sm" type="submit">반영하기</button>
            <a href="{{ route('manage.products.import.template') }}" class="btn btn-sm">빈 양식 받기</a>
            <span class="t-sub" style="flex-basis:100%;margin-top:4px">‘상품ID’가 있으면 수정, 비어 있으면 신규 등록됩니다. 카테고리는 이름이 일치할 때 연결되며, 재고·노출 칸도 함께 반영됩니다. UTF-8 CSV 형식.</span>
        </form>
    </div>
</div>

{{-- 검색 필터 --}}
<div class="panel">
    <div class="panel-b">
        <form method="get" style="display:flex;flex-wrap:nowrap;gap:8px;align-items:center;width:100%">
            <input class="input" style="height:38px;flex:1 1 0;min-width:120px" name="q" value="{{ $q }}" placeholder="상품명 · 브랜드 · SKU 검색">
            <select class="input" style="height:38px;flex:0 0 140px" name="category_id">
                <option value="">전체 카테고리</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) $categoryId === (string) $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
            <select class="input" style="height:38px;flex:0 0 112px" name="state">
                <option value="">전체 상태</option>
                @foreach(\App\Http\Controllers\Manage\ProductController::STATES as $k => $v)
                    <option value="{{ $k }}" @selected($state === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <select class="input" style="height:38px;flex:0 0 108px" name="stock">
                <option value="">전체 재고</option>
                <option value="low" @selected($stock === 'low')>재고 부족</option>
                <option value="out" @selected($stock === 'out')>재고 소진</option>
                <option value="untracked" @selected($stock === 'untracked')>재고 미관리</option>
            </select>
            <select class="input" style="height:38px;flex:0 0 122px" name="sort">
                @foreach(\App\Http\Controllers\Manage\ProductController::SORTS as $k => $v)
                    <option value="{{ $k }}" @selected($sort === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-accent" style="flex:0 0 auto">검색</button>
            <a href="{{ route('manage.products.index') }}" class="btn btn-sm" style="flex:0 0 auto">초기화</a>
        </form>
    </div>
</div>

{{-- 목록 : 체크 선택 → 일괄 처리 / 가격·재고 인라인 수정 --}}
<form id="prodForm" method="post" action="{{ route('manage.products.bulk') }}">
    @csrf
    <div class="panel">
        <div class="panel-h">
            <div><h2>상품 목록</h2><div class="sub">총 {{ number_format($products->total()) }}개 · <span id="selCount">0</span>개 선택</div></div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                <select class="input" name="bulk_action" id="bulkAction" style="height:34px;width:126px;font-size:12.5px">
                    <option value="">일괄 작업…</option>
                    <option value="onsale">판매중으로</option>
                    <option value="soldout">품절 처리</option>
                    <option value="activate">쇼핑몰 노출</option>
                    <option value="deactivate">노출 중지</option>
                    <option value="track_on">재고 관리 켜기</option>
                    <option value="track_off">재고 관리 끄기</option>
                    <option value="category">카테고리 이동</option>
                    <option value="delete">삭제</option>
                </select>
                <select class="input" name="bulk_category_id" id="bulkCategory" style="height:34px;width:130px;font-size:12.5px" hidden>
                    <option value="">이동할 카테고리</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm" onclick="runBulk()">적용</button>
                <button class="btn btn-sm btn-accent" formaction="{{ route('manage.products.quicksave') }}">가격·재고 저장</button>
            </div>
        </div>
        <table class="table">
            <thead><tr>
                <th style="width:34px"><input type="checkbox" id="chkAll" title="전체 선택"></th>
                <th style="width:56px">이미지</th>
                <th>상품명 / SKU</th>
                <th style="width:110px">카테고리</th>
                <th style="width:112px">판매가</th>
                <th style="width:112px">할인가</th>
                <th style="width:96px">재고</th>
                <th style="width:120px">상태</th>
                <th style="width:132px">관리</th>
            </tr></thead>
            <tbody>
            @forelse($products as $p)
                <tr>
                    <td><input type="checkbox" class="chkRow" name="ids[]" value="{{ $p->id }}"></td>
                    <td>@if($p->main_image)<img class="thumb" src="{{ asset($p->main_image) }}" alt="" onerror="this.style.visibility='hidden'">@else<div class="thumb"></div>@endif</td>
                    <td>
                        <a href="{{ route('manage.products.edit', $p) }}" class="t-name">{{ \Illuminate\Support\Str::limit($p->name, 42) }}</a>
                        <div class="t-sub">
                            {{ $p->sku ? 'SKU '.$p->sku : 'SKU 미지정' }}@if($p->brand) · {{ $p->brand }}@endif
                            @if($p->margin_percent !== null) · 마진 {{ $p->margin_percent }}%@endif
                        </div>
                    </td>
                    <td class="t-sub">{{ $p->category->name ?? '-' }}</td>
                    <td><input class="input qi" type="number" min="0" name="rows[{{ $p->id }}][price]" value="{{ $p->price }}" placeholder="0"></td>
                    <td>
                        <input class="input qi" type="number" min="0" name="rows[{{ $p->id }}][sale_price]" value="{{ $p->sale_price }}" placeholder="-">
                        @if($p->has_discount)<div class="t-sub" style="text-align:right">{{ $p->discount_percent }}% ↓</div>@endif
                    </td>
                    <td>
                        <input class="input qi" type="number" min="0" name="rows[{{ $p->id }}][stock]" value="{{ $p->stock }}">
                        @if($p->stock_level === 'out')<div class="t-sub" style="color:var(--danger);text-align:right">재고 소진</div>
                        @elseif($p->stock_level === 'low')<div class="t-sub" style="color:#a35a06;text-align:right">부족</div>
                        @elseif($p->stock_level === 'untracked')<div class="t-sub" style="text-align:right">미관리</div>@endif
                    </td>
                    <td>
                        @if($p->is_soldout)<span class="badge off">품절</span>@else<span class="badge ok">판매중</span>@endif
                        @if(! $p->is_active)<span class="badge warn">미노출</span>@endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('manage.products.edit', $p) }}" class="btn btn-sm">수정</a>
                            <a href="{{ route('product.show', $p) }}" target="_blank" class="btn btn-sm" title="쇼핑몰에서 보기">↗</a>
                            @if($p->main_image)<a href="{{ route('manage.products.image', $p) }}" class="btn btn-sm" title="이미지 편집">✎</a>@endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">조건에 맞는 상품이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>
{{ $products->links('manage.pagination') }}

@push('scripts')
<style>.table td .qi{height:32px;padding:0 8px;font-size:12.5px;text-align:right;border-radius:7px}</style>
<script>
(function(){
    var all = document.getElementById('chkAll');
    var rows = function(){ return Array.prototype.slice.call(document.querySelectorAll('.chkRow')); };
    var count = document.getElementById('selCount');
    function refresh(){ count.textContent = rows().filter(function(c){return c.checked;}).length; }
    all.addEventListener('change', function(){ rows().forEach(function(c){ c.checked = all.checked; }); refresh(); });
    document.addEventListener('change', function(e){ if(e.target.classList.contains('chkRow')) refresh(); });

    var act = document.getElementById('bulkAction'), cat = document.getElementById('bulkCategory');
    act.addEventListener('change', function(){ cat.hidden = (act.value !== 'category'); });

    window.runBulk = function(){
        var n = rows().filter(function(c){return c.checked;}).length;
        if(!act.value){ alert('실행할 일괄 작업을 선택하세요.'); act.focus(); return; }
        if(n === 0){ alert('작업할 상품을 체크하세요.'); return; }
        if(act.value === 'category' && !cat.value){ alert('이동할 카테고리를 선택하세요.'); cat.focus(); return; }
        var label = act.options[act.selectedIndex].text;
        var warn = act.value === 'delete' ? '선택한 ' + n + '개 상품을 삭제합니다. 되돌릴 수 없습니다. 진행할까요?'
                                          : '선택한 ' + n + '개 상품을 "' + label + '" 처리할까요?';
        if(!confirm(warn)) return;
        document.getElementById('prodForm').submit();
    };
})();
</script>
@endpush
@endsection
