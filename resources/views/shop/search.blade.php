@extends('layouts.app')
@section('title', '검색: ' . $q . ' · KOR SAFETY')

@section('content')
<div class="wrap">
    <div class="crumb"><a href="{{ route('home') }}">홈</a><span class="sep">/</span><span>검색</span></div>
    <div class="list-head">
        <div>
            <h1>‘{{ $q }}’ 검색결과</h1>
            @if($q !== '')<div class="cnt">총 <b>{{ number_format($products->total()) }}</b>개의 상품</div>@endif
        </div>
    </div>

    @if($q === '')
        <div class="empty-state"><h2>검색어를 입력해 주세요</h2><p>상품명 또는 브랜드로 검색할 수 있습니다.</p></div>
    @elseif($products->count())
        <div class="p-grid">
            @foreach($products as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
        {{ $products->onEachSide(1)->links('pagination.shop') }}
    @else
        <div class="empty-state">
            <div class="ei"><svg viewBox="0 0 24 24" width="34" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg></div>
            <h2>검색결과가 없습니다</h2>
            <p>다른 검색어로 다시 시도해 보세요.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">홈으로 가기</a>
        </div>
    @endif
</div>
@endsection
