<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function about()
    {
        // 종합 라인업 — 카테고리별 취급 브랜드 · 품목 (+ 대표 이미지)
        $lineup = [
            ['slug' => 'safety-shoes',  'title' => '안전화',          'brands' => 'K2 · 아이더 · 네파 · 블랙야크 · 지벤 · YK · 힘맨 · 로드페이스 · 몽크로스 · 한스', 'items' => '브랜드 안전화, 절연화'],
            ['slug' => 'safety-gear',   'title' => '개인보호구',       'brands' => '3M · 오토스 · 안셀 · 명신 · 국제 · 성안 · 에버그린', 'items' => '안전모, 안전장갑, 보안경, 마스크'],
            ['slug' => 'harness',       'title' => '고소작업안전용품',  'brands' => 'K2 · 네파 · 블랙야크 · 국제 · 에스탑 · 코브', 'items' => '안전벨트 및 추락방지'],
            ['slug' => 'road-safety',   'title' => '도로안전용품',      'brands' => '신도 · 해광 · 동광', 'items' => '라바콘, 안전휀스, 안전표지판'],
            ['slug' => 'facilities',    'title' => '안전시설용품',      'brands' => '한국제일안전 · 예원몰 · 영도 · 금창', 'items' => '보호구함, 각종 사다리 및 작업대'],
            ['slug' => 'workwear',      'title' => '작업복·보호복',     'brands' => '지벤 · 티뷰크 · 마크 · 부일', 'items' => '안전조끼, 근무복, 방염복, 제전복, 방진복'],
            ['slug' => 'fire-rescue',   'title' => '소방,비상구호용품',  'brands' => '삼우산기 · 한울 · 일진 · 도부', 'items' => '소화기, 비상조명등, 화재대피용마스크, 구급함'],
            ['slug' => 'yuhankimberly', 'title' => '유한킴벌리',        'brands' => '킴테크 · 크린가드 · 와이프올 · 크리넥스 · 스카트', 'items' => '산업위생용품'],
        ];

        // 카테고리별 대표 상품 이미지 1장 매핑
        $slugs = array_column($lineup, 'slug');
        $cats = Category::whereIn('slug', $slugs)->with(['products' => function ($q) {
            $q->whereNotNull('main_image')->where('main_image', '!=', '')->orderBy('id')->limit(1);
        }])->get()->keyBy('slug');

        foreach ($lineup as &$row) {
            $cat = $cats->get($row['slug']);
            $prod = $cat?->products->first();
            $row['image'] = $prod?->main_image;
        }
        unset($row);

        return view('shop.about', compact('lineup'));
    }

    public function home()
    {
        $categories = Category::orderBy('sort')->withCount('products')->get();

        $best = Product::query()
            ->whereNotNull('main_image')
            ->where('is_soldout', false)
            ->inRandomOrder()
            ->limit(8)->get();

        $newIn = Product::query()
            ->whereNotNull('main_image')
            ->orderByDesc('external_no')
            ->limit(10)->get();

        // A curated block per top category
        $showcase = $categories->take(6)->map(function ($cat) {
            return [
                'category' => $cat,
                'products' => $cat->products()
                    ->whereNotNull('main_image')
                    ->inRandomOrder()->limit(4)->get(),
            ];
        });

        $showCategories = Setting::bool('home_show_categories');

        return view('shop.home', compact('categories', 'best', 'newIn', 'showcase', 'showCategories'));
    }

    public function category(Request $request, Category $category)
    {
        $sort = $request->query('sort', 'recommended');
        $query = $category->products()->whereNotNull('main_image');

        match ($sort) {
            'price_asc'  => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'newest'     => $query->orderByDesc('external_no'),
            'name'       => $query->orderBy('name'),
            default      => $query->orderByRaw('is_soldout asc')->orderByDesc('external_no'),
        };

        $products = $query->paginate(24)->withQueryString();
        $categories = Category::orderBy('sort')->get();

        return view('shop.category', compact('category', 'products', 'categories', 'sort'));
    }

    public function product(Product $product)
    {
        $product->load(['images', 'category']);
        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->whereNotNull('main_image')
            ->inRandomOrder()->limit(4)->get();

        return view('shop.product', compact('product', 'related'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $products = collect();
        if ($q !== '') {
            $products = Product::query()
                ->whereNotNull('main_image')
                ->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('brand', 'like', "%$q%"))
                ->orderByDesc('external_no')
                ->paginate(24)->withQueryString();
        }
        $categories = Category::orderBy('sort')->get();

        return view('shop.search', compact('q', 'products', 'categories'));
    }
}
