@php
    $final = $product->final_price;
    $badge = $badge ?? null;
@endphp
<article class="p-card">
    <a href="{{ route('product.show', $product) }}" class="p-thumb">
        <div class="p-badges">
            @if($product->has_discount)
                <span class="badge badge-sale">{{ $product->discount_percent }}% OFF</span>
            @elseif($badge === 'new')
                <span class="badge badge-new">NEW</span>
            @elseif($badge === 'best')
                <span class="badge badge-best">BEST</span>
            @endif
        </div>
        @if($product->is_soldout)
            <div class="p-soldout"><span>SOLD OUT</span></div>
        @endif
        <img src="{{ asset($product->main_image) }}" alt="{{ $product->name }}" loading="lazy"
             onerror="this.style.visibility='hidden'">
        @unless($product->is_soldout)
            <button class="p-quickadd js-quickadd" data-url="{{ route('cart.add', $product) }}" aria-label="장바구니 담기" onclick="event.preventDefault();event.stopPropagation();">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            </button>
        @endunless
    </a>
    <div class="p-info">
        @if($product->brand)<div class="p-brand">{{ $product->brand }}</div>@else<div class="p-brand"></div>@endif
        <a href="{{ route('product.show', $product) }}" class="p-name">{{ $product->name }}</a>
        <div class="p-price">
            @if(\App\Models\Setting::get('price_display_mode') === 'price' && $final)
                <span class="now">{{ number_format($final) }}<span class="won">원</span></span>
                @if($product->has_discount)
                    <span class="was">{{ number_format($product->price) }}원</span>
                @endif
            @else
                <span class="ask">가격 문의</span>
            @endif
        </div>
    </div>
</article>
