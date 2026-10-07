@extends('manage.layout')
@section('title', '상품 관리')
@section('page', '상품 관리')
@section('crumb', '재고 · 가격 · 노출 · 카테고리 통합 관리')
@section('actions')
    @if(auth()->user()->isHqAdmin())
        <a href="{{ route('manage.categories.index') }}" class="btn btn-sm">카테고리 관리</a>
    @endif
    <a href="{{ route('manage.products.trash') }}" class="btn btn-sm">🗑 삭제한 상품</a>
    <a href="{{ route('manage.products.export') }}" class="btn btn-sm">⭳ 엑셀 다운로드</a>
    <button type="button" class="btn btn-sm" onclick="var b=document.getElementById('imp-box');b.hidden=!b.hidden">⭱ 엑셀 업로드</button>
    <a href="{{ route('manage.products.create') }}" class="btn btn-accent btn-sm">+ 상품 등록</a>
@endsection

@section('content')
@php
    $filterUrl = fn(array $params) => route('manage.products.index', array_merge(request()->query(), $params));
    $sortUrl = fn(string $key) => $filterUrl(['sort' => $key, 'page' => null]);
    // 표 머리글 정렬 — ▼ 높은(많은)순 · ▲ 낮은(적은)순
    $sortCol = function (string $label, string $descKey, string $ascKey, string $descTitle = '높은순', string $ascTitle = '낮은순') use ($sort, $sortUrl) {
        $style = fn(bool $on) => 'text-decoration:none;font-size:11px;margin-left:2px;color:'.($on ? 'var(--accent)' : '#b0b5ba');
        return $label
            .'<a href="'.e($sortUrl($descKey)).'" title="'.e($descTitle).'" style="'.$style($sort === $descKey).'">▼</a>'
            .'<a href="'.e($sortUrl($ascKey)).'" title="'.e($ascTitle).'" style="'.$style($sort === $ascKey).'">▲</a>';
    };
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
            <input class="input" style="height:38px;flex:1 1 0;min-width:120px" name="q" value="{{ $q }}" placeholder="상품명 · 브랜드 · SKU · 상품코드 검색">
            <select class="input" style="height:38px;flex:0 0 140px" name="category_id">
                <option value="">전체 카테고리</option>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected((string) $categoryId === (string) $c->id)>{{ str_repeat("　", $c->tree_depth) }}{{ $c->tree_depth ? "└ " : "" }}{{ $c->name }}</option>
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
            <select class="input" style="height:38px;flex:0 0 104px" name="per_page" title="한 번에 볼 개수">
                @foreach(\App\Http\Controllers\Manage\ProductController::PER_PAGES as $n)
                    <option value="{{ $n }}" @selected($perPage === $n)>{{ $n }}개씩</option>
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
            <div><h2>상품 목록</h2><div class="sub">총 {{ number_format($products->total()) }}개 · <span id="selCount">0</span>개 선택
                @if(($stats['trashed'] ?? 0) > 0) · <a href="{{ route('manage.products.trash') }}">휴지통 {{ number_format($stats['trashed']) }}개</a>@endif
            </div></div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;justify-content:flex-end">
                <select class="input" name="bulk_action" id="bulkAction" style="height:34px;width:126px;font-size:12.5px">
                    <option value="">일괄 작업…</option>
                    <option value="onsale">판매중으로</option>
                    <option value="soldout">품절 처리</option>
                    <option value="activate">쇼핑몰 노출</option>
                    <option value="deactivate">노출 중지</option>
                    <option value="track_on">재고 관리 켜기</option>
                    <option value="track_off">재고 관리 끄기</option>
                    <option value="best_on">베스트 셀러 지정</option>
                    <option value="best_off">베스트 셀러 해제</option>
                    <option value="category">카테고리 이동</option>
                    <option value="delete">삭제</option>
                </select>
                {{-- 카테고리 이동 : 여러 개를 체크할 수 있다 --}}
                <div class="bulkcat" id="bulkCatWrap" hidden>
                    <button type="button" class="input bulkcat-btn" id="bulkCatBtn" aria-expanded="false">
                        <span id="bulkCatLabel">이동할 카테고리 선택</span><i>▾</i>
                    </button>
                    <div class="bulkcat-pop" id="bulkCatPop" hidden>
                        <input type="search" class="input" id="bulkCatSearch" placeholder="카테고리 검색 (대 > 중 > 소)" autocomplete="off">
                        <div class="cat-picker" id="bulkCatList">
                            @forelse($categories as $c)
                                <label class="cat-opt d{{ $c->tree_depth }}" data-path="{{ $c->tree_path }}">
                                    <input type="checkbox" name="bulk_category_ids[]" value="{{ $c->id }}">
                                    <span>{{ $c->tree_depth ? '└ ' : '' }}{{ $c->name }}</span>
                                    <em>{{ \App\Models\Category::DEPTH_LABELS[$c->tree_depth] }}</em>
                                </label>
                            @empty
                                <div class="t-sub" style="padding:8px">등록된 카테고리가 없습니다.</div>
                            @endforelse
                        </div>
                        <div class="bulkcat-foot">
                            <span class="t-sub" id="bulkCatCount">0개 선택</span>
                            <button type="button" class="btn btn-sm" id="bulkCatClear">선택 해제</button>
                            <button type="button" class="btn btn-sm btn-accent" id="bulkCatDone">확인</button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm" onclick="runBulk()">적용</button>
                <button class="btn btn-sm btn-accent" formaction="{{ route('manage.products.quicksave') }}">가격·재고 저장</button>
            </div>
        </div>
        <table class="table">
            <thead><tr>
                <th style="width:34px"><input type="checkbox" id="chkAll" title="전체 선택"></th>
                <th style="width:56px">이미지</th>
                <th>상품명 / 코드 · SKU</th>
                <th style="width:170px">카테고리</th>
                <th style="width:112px">{!! $sortCol('판매가', 'price_desc', 'price_asc') !!}</th>
                <th style="width:112px">{!! $sortCol('할인가', 'sale_desc', 'sale_asc') !!}</th>
                <th style="width:112px">{!! $sortCol('협력사가', 'partner_desc', 'partner_asc') !!}</th>
                <th style="width:96px">{!! $sortCol('재고', 'stock_desc', 'stock_asc', '많은순', '적은순') !!}</th>
                <th style="width:120px">{!! $sortCol('상태', 'state_onsale', 'state_off', '판매중 먼저', '미판매중 먼저') !!}</th>
                <th style="width:132px">관리</th>
            </tr></thead>
            <tbody>
            @php $catRank = $catPaths->keys()->values()->flip(); @endphp
            @forelse($products as $p)
                @php
                    // 연결된 카테고리를 트리 순서대로, 대 > 중 > 소 전체 경로로 보여준다
                    $rowCats = $p->categories->sortBy(fn ($c) => $catRank[$c->id] ?? 9999)
                        ->map(fn ($c) => $catPaths[$c->id] ?? $c->name)->values();
                    if ($rowCats->isEmpty() && $p->category) {
                        $rowCats = collect([$catPaths[$p->category->id] ?? $p->category->name]);
                    }
                @endphp
                <tr>
                    <td><input type="checkbox" class="chkRow" name="ids[]" value="{{ $p->id }}"></td>
                    <td>@if($p->main_image)<img class="thumb" src="{{ asset($p->main_image) }}" alt="" onerror="this.style.visibility='hidden'">@else<div class="thumb"></div>@endif</td>
                    <td>
                        <a href="{{ route('manage.products.edit', $p) }}" class="t-name">{{ \Illuminate\Support\Str::limit($p->name, 42) }}</a>
                        <div class="t-sub">
                            @if($p->product_code)<b class="pcode">{{ $p->product_code }}</b> · @endif{{ $p->sku ? "SKU ".$p->sku : "SKU 미지정" }}@if($p->brand) · {{ $p->brand }}@endif
                            @if($p->margin_percent !== null) · 마진 {{ $p->margin_percent }}%@endif
                            @if(auth()->user()->isHqAdmin() && $p->seller && ! $p->seller->is_hq) · <span class="badge hq">{{ $p->seller->name }}</span>@endif
                        </div>
                    </td>
                    <td class="t-sub catcell">
                        @forelse($rowCats as $path)
                            <div class="catpath" title="{{ $path }}">{{ $path }}</div>
                        @empty
                            -
                        @endforelse
                    </td>
                    <td><input class="input qi" type="number" min="0" name="rows[{{ $p->id }}][price]" value="{{ $p->price }}" placeholder="0"></td>
                    <td>
                        <input class="input qi" type="number" min="0" name="rows[{{ $p->id }}][sale_price]" value="{{ $p->sale_price }}" placeholder="-">
                        @if($p->has_discount)<div class="t-sub" style="text-align:right">{{ $p->discount_percent }}% ↓</div>@endif
                    </td>
                    <td>
                        <input class="input qi" type="number" min="0" name="rows[{{ $p->id }}][partner_price]" value="{{ $p->partner_price }}" placeholder="-" title="협력사 회원에게만 보이는 가격">
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
                        @if($p->is_best)<span class="badge hq">베스트</span>@endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('manage.products.edit', $p) }}" class="btn btn-sm">수정</a>
                            <a href="{{ route('product.show', $p) }}" target="_blank" class="btn btn-sm" title="쇼핑몰에서 보기">↗</a>
                            <button type="submit" class="btn btn-sm" title="이 상품 복사"
                                    formaction="{{ route('manage.products.duplicate', $p) }}"
                                    onclick="return confirm('이 상품을 복사할까요?\n복사본은 미노출 상태로 만들어집니다.')">복사</button>
                            @if($p->main_image)<a href="{{ route('manage.products.image', $p) }}" class="btn btn-sm" title="이미지 편집">✎</a>@endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="empty">조건에 맞는 상품이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>
{{ $products->links('manage.pagination') }}

@push('scripts')
<style>
.table td .qi{height:32px;padding:0 8px;font-size:12.5px;text-align:right;border-radius:7px}
.catcell{line-height:1.45}
.catcell .catpath{word-break:keep-all}
.catcell .catpath + .catpath{margin-top:3px;padding-top:3px;border-top:1px dashed var(--line)}
.bulkcat{position:relative}
.bulkcat-btn{display:flex;align-items:center;gap:6px;height:34px;width:230px;font-size:12.5px;text-align:left;cursor:pointer;background:#fff}
.bulkcat-btn span{flex:1 1 auto;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.bulkcat-btn i{flex:0 0 auto;font-style:normal;color:var(--muted)}
.bulkcat-btn.on{border-color:var(--accent);color:var(--accent);font-weight:700}
.bulkcat-pop{position:absolute;top:38px;right:0;z-index:40;width:330px;padding:8px;background:#fff;border:1.5px solid var(--line);border-radius:12px;box-shadow:0 12px 30px rgba(16,24,40,.14)}
.bulkcat-pop > .input{height:32px;font-size:12.5px;margin-bottom:6px}
.bulkcat-pop .cat-picker{max-height:280px}
.bulkcat-foot{display:flex;align-items:center;gap:6px;margin-top:8px}
.bulkcat-foot .t-sub{flex:1 1 auto}
</style>
<script>
(function(){
    var all = document.getElementById('chkAll');
    var rows = function(){ return Array.prototype.slice.call(document.querySelectorAll('.chkRow')); };
    var count = document.getElementById('selCount');
    function refresh(){ count.textContent = rows().filter(function(c){return c.checked;}).length; }
    all.addEventListener('change', function(){ rows().forEach(function(c){ c.checked = all.checked; }); refresh(); });
    document.addEventListener('change', function(e){ if(e.target.classList.contains('chkRow')) refresh(); });

    var act = document.getElementById('bulkAction');
    var wrap = document.getElementById('bulkCatWrap');
    var btn = document.getElementById('bulkCatBtn'), pop = document.getElementById('bulkCatPop');
    var catLabel = document.getElementById('bulkCatLabel'), catCount = document.getElementById('bulkCatCount');
    var search = document.getElementById('bulkCatSearch');
    var catBoxes = function(){ return Array.prototype.slice.call(document.querySelectorAll('#bulkCatList input[type=checkbox]')); };
    var picked = function(){ return catBoxes().filter(function(c){ return c.checked; }); };

    act.addEventListener('change', function(){
        wrap.hidden = (act.value !== 'category');
        if(wrap.hidden) closePop();
    });

    function paths(){
        return picked().map(function(c){ return c.closest('.cat-opt').getAttribute('data-path'); });
    }
    function refreshCat(){
        var p = paths();
        catCount.textContent = p.length + '개 선택';
        btn.classList.toggle('on', p.length > 0);
        catLabel.textContent = p.length === 0 ? '이동할 카테고리 선택'
            : (p.length === 1 ? p[0] : p[0] + ' 외 ' + (p.length - 1) + '개');
        btn.title = p.join('\n');
    }
    function openPop(){ pop.hidden = false; btn.setAttribute('aria-expanded','true'); search.focus(); }
    function closePop(){ pop.hidden = true; btn.setAttribute('aria-expanded','false'); }

    btn.addEventListener('click', function(){ pop.hidden ? openPop() : closePop(); });
    document.getElementById('bulkCatDone').addEventListener('click', closePop);
    document.getElementById('bulkCatClear').addEventListener('click', function(){
        catBoxes().forEach(function(c){ c.checked = false; }); refreshCat();
    });
    pop.addEventListener('change', function(e){ if(e.target.type === 'checkbox') refreshCat(); });
    search.addEventListener('input', function(){
        var kw = search.value.trim().toLowerCase();
        Array.prototype.slice.call(document.querySelectorAll('#bulkCatList .cat-opt')).forEach(function(l){
            var hit = !kw || (l.getAttribute('data-path') || '').toLowerCase().indexOf(kw) >= 0;
            l.hidden = !hit;
        });
    });
    // 바깥을 누르면 닫는다 (Esc도 같이)
    document.addEventListener('click', function(e){ if(!pop.hidden && !wrap.contains(e.target)) closePop(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && !pop.hidden) closePop(); });
    refreshCat();

    window.runBulk = function(){
        var n = rows().filter(function(c){return c.checked;}).length;
        if(!act.value){ alert('실행할 일괄 작업을 선택하세요.'); act.focus(); return; }
        if(n === 0){ alert('작업할 상품을 체크하세요.'); return; }
        var cats = paths();
        if(act.value === 'category' && cats.length === 0){ alert('이동할 카테고리를 선택하세요.'); openPop(); return; }
        var label = act.options[act.selectedIndex].text;
        var warn = act.value === 'delete' ? '선택한 ' + n + '개 상품을 삭제합니다. 되돌릴 수 없습니다. 진행할까요?'
                 : act.value === 'category' ? '선택한 ' + n + '개 상품을 아래 카테고리로 이동할까요?\n\n' + cats.join('\n') + '\n\n기존 카테고리 연결은 이 목록으로 바뀝니다.'
                                          : '선택한 ' + n + '개 상품을 "' + label + '" 처리할까요?';
        if(!confirm(warn)) return;
        document.getElementById('prodForm').submit();
    };
})();
</script>
@endpush
@endsection
