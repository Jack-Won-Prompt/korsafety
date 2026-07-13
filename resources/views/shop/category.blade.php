@extends('layouts.app')
@section('title', $category->name . ' · KOR SAFETY')

@section('content')
<div class="wrap">
    <div class="crumb">
        <a href="{{ route('home') }}">홈</a><span class="sep">/</span>
        <span>{{ $category->name }}</span>
    </div>

    <div class="list-head">
        <div>
            <h1>{{ $category->name }}</h1>
            <div class="cnt">총 <b>{{ number_format($products->total()) }}</b>개의 상품</div>
        </div>
        <div class="sortbar">
            @php $sorts = ['recommended'=>'추천순','newest'=>'신상품순','price_asc'=>'낮은가격순','price_desc'=>'높은가격순','name'=>'이름순']; @endphp
            @foreach($sorts as $key => $label)
                <a href="{{ route('category.show', [$category, 'sort' => $key]) }}" class="{{ $sort === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    @if($products->count())
        <div class="p-grid">
            @foreach($products as $product)
                @include('partials.product-card', ['product' => $product])
            @endforeach
        </div>
        {{ $products->onEachSide(1)->links('pagination.shop') }}
    @else
        <div class="empty-state">
            <div class="ei"><svg viewBox="0 0 24 24" width="34" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 6h15l-1.5 9h-12z"/><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/></svg></div>
            <h2>상품이 없습니다</h2>
            <p>이 카테고리에 등록된 상품이 아직 없습니다.</p>
            <a href="{{ route('home') }}" class="btn btn-primary">홈으로 가기</a>
        </div>
    @endif
</div>
@endsection
