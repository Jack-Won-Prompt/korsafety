<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>(주)한국안전 · 산업안전용품 전문몰</title>
        <link>{{ url('/') }}</link>
        <description>안전화 · 개인보호구 · 작업복 · 안전시설물 등 산업안전용품 신규 상품 소식</description>
        <language>ko</language>
        <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
        <atom:link href="{{ route('rss') }}" rel="self" type="application/rss+xml"/>
@foreach($items as $p)
@php
    $rssLink = route('product.show', $p);
    $rssBody = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $p->description)));
    $rssDesc = '<p><img src="'.e(asset($p->main_image)).'" alt="'.e($p->name).'"></p>'
        .'<p>'.e(collect([$p->brand, $p->category?->name])->filter()->unique()->implode(' · ')).'</p>'
        .'<p>'.e($p->name).($rssBody !== '' ? ' — '.e(\Illuminate\Support\Str::limit($rssBody, 300)) : '').'</p>';
@endphp
        <item>
            <title>{{ $p->name }}</title>
            <link>{{ $rssLink }}</link>
            <guid isPermaLink="true">{{ $rssLink }}</guid>
            <description>{{ $rssDesc }}</description>
@if($p->category)
            <category>{{ $p->category->name }}</category>
@endif
            <pubDate>{{ ($p->created_at ?? now())->toRssString() }}</pubDate>
        </item>
@endforeach
    </channel>
</rss>
