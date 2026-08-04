@extends('manage.layout')
@section('title', '베스트 셀러 관리')
@section('page', '베스트 셀러 관리')
@section('crumb', '쇼핑몰 홈에 노출할 상품과 순서')
@section('actions')
    <a href="{{ route('home') }}#best" target="_blank" class="btn btn-sm">홈에서 보기 ↗</a>
    <a href="{{ route('manage.products.index', ['state' => 'best']) }}" class="btn btn-sm">상품 목록에서 보기</a>
@endsection

@section('content')
<form action="{{ route('admin.bestsellers.reorder') }}" method="post">@csrf
<div class="panel">
    <div class="panel-h">
        <div><h2>현재 베스트 셀러</h2>
            <div class="sub">
                {{ $bests->count() }}개 지정 · 홈에는 앞에서 <b>{{ $limit }}개</b>까지 노출됩니다.
                @if($bests->isEmpty())지정된 상품이 없으면 예전처럼 임의로 노출됩니다.@endif
            </div>
        </div>
        @if($bests->count())<button class="btn btn-sm btn-accent">순서 저장</button>@endif
    </div>
    <table class="table">
        <thead><tr>
            <th style="width:70px">순번</th>
            <th style="width:56px">이미지</th>
            <th>상품명</th>
            <th style="width:110px">카테고리</th>
            <th style="width:110px">판매가</th>
            <th style="width:100px">상태</th>
            <th style="width:150px">관리</th>
        </tr></thead>
        <tbody>
        @forelse($bests as $i => $p)
            <tr style="{{ $i >= $limit ? 'opacity:.5' : '' }}">
                <td>
                    <input class="input" type="number" min="1" name="order[{{ $p->id }}]" value="{{ $i + 1 }}"
                           style="height:34px;width:64px;text-align:center;font-size:12.5px">
                </td>
                <td>@if($p->main_image)<img class="thumb" src="{{ asset($p->main_image) }}" alt="" onerror="this.style.visibility='hidden'">@else<div class="thumb"></div>@endif</td>
                <td>
                    <a href="{{ route('manage.products.edit', $p) }}" class="t-name">{{ \Illuminate\Support\Str::limit($p->name, 44) }}</a>
                    <div class="t-sub">{{ $p->brand ?: 'ㅡ' }}@if($i >= $limit) · 홈 노출 범위 밖@endif</div>
                </td>
                <td class="t-sub">{{ $p->category->name ?? '-' }}</td>
                <td class="t-sub">{{ $p->final_price ? number_format($p->final_price).'원' : '—' }}</td>
                <td>
                    @if($p->is_soldout)<span class="badge off">품절</span>@else<span class="badge ok">판매중</span>@endif
                    @if(! $p->is_active)<span class="badge warn">미노출</span>@endif
                </td>
                <td>
                    <div style="display:flex;gap:6px">
                        <button class="btn btn-sm" formaction="{{ route('admin.bestsellers.move', $p) }}" name="dir" value="up" title="위로">▲</button>
                        <button class="btn btn-sm" formaction="{{ route('admin.bestsellers.move', $p) }}" name="dir" value="down" title="아래로">▼</button>
                        <button class="btn btn-sm btn-danger" formaction="{{ route('admin.bestsellers.remove', $p) }}"
                                onclick="return confirm('베스트 셀러에서 제외할까요?')">제외</button>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">지정된 베스트 셀러가 없습니다. 아래에서 상품을 검색해 추가하세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</form>

<div class="panel">
    <div class="panel-h">
        <div><h2>상품 추가</h2><div class="sub">상품명 · 브랜드 · SKU로 검색해서 베스트 셀러에 추가합니다</div></div>
        <form method="get" style="display:flex;gap:8px">
            <input class="input" style="height:38px;width:260px" name="q" value="{{ $q }}" placeholder="상품명 · 브랜드 · SKU 검색">
            <button class="btn btn-sm btn-accent">검색</button>
        </form>
    </div>
    @if($q !== '')
    <table class="table">
        <thead><tr><th style="width:56px">이미지</th><th>상품명</th><th style="width:110px">카테고리</th><th style="width:110px">판매가</th><th style="width:90px">추가</th></tr></thead>
        <tbody>
        @forelse($candidates as $p)
            <tr>
                <td>@if($p->main_image)<img class="thumb" src="{{ asset($p->main_image) }}" alt="" onerror="this.style.visibility='hidden'">@else<div class="thumb"></div>@endif</td>
                <td><span class="t-name">{{ \Illuminate\Support\Str::limit($p->name, 46) }}</span><div class="t-sub">{{ $p->brand }}</div></td>
                <td class="t-sub">{{ $p->category->name ?? '-' }}</td>
                <td class="t-sub">{{ $p->final_price ? number_format($p->final_price).'원' : '—' }}</td>
                <td>
                    <form action="{{ route('admin.bestsellers.add') }}" method="post">@csrf
                        <input type="hidden" name="product_id" value="{{ $p->id }}">
                        <button class="btn btn-sm btn-accent">추가</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">검색 결과가 없습니다. (이미 지정된 상품과 미노출 상품은 제외됩니다)</td></tr>
        @endforelse
        </tbody>
    </table>
    @else
        <div class="panel-b"><div class="t-sub">검색어를 입력하면 추가할 상품이 표시됩니다.</div></div>
    @endif
</div>
@endsection
