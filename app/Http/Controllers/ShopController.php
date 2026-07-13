<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;

class ShopController extends Controller
{
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
