<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /** 홈 화면용 큐레이션 묶음 */
    public function home()
    {
        $best = Product::whereNotNull('main_image')->where('is_soldout', false)
            ->inRandomOrder()->limit(10)->get();

        $newIn = Product::whereNotNull('main_image')
            ->orderByDesc('external_no')->limit(12)->get();

        $categories = Category::orderBy('sort')->withCount('products')->get();

        $showcase = $categories->take(6)->map(fn ($cat) => [
            'category' => new CategoryResource($cat),
            'products' => ProductResource::collection(
                $cat->products()->whereNotNull('main_image')->inRandomOrder()->limit(6)->get()
            ),
        ]);

        return response()->json([
            'categories' => CategoryResource::collection($categories),
            'best' => ProductResource::collection($best),
            'new_in' => ProductResource::collection($newIn),
            'showcase' => $showcase,
        ]);
    }

    /** 카테고리 목록 */
    public function categories()
    {
        $categories = Category::orderBy('sort')->withCount('products')->get();

        return CategoryResource::collection($categories);
    }

    /** 상품 목록 (카테고리/검색/정렬/페이지네이션) */
    public function index(Request $request)
    {
        $query = Product::query()->whereNotNull('main_image');

        if ($slug = $request->query('category')) {
            $cat = Category::where('slug', $slug)->first();
            if ($cat) {
                $query->where(fn ($w) => $w
                    ->whereHas('categories', fn ($q) => $q->where('categories.id', $cat->id))
                    ->orWhere('category_id', $cat->id));
            }
        }

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('brand', 'like', "%$q%"));
        }

        match ($request->query('sort', 'recommended')) {
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'newest' => $query->orderByDesc('external_no'),
            'name' => $query->orderBy('name'),
            default => $query->orderByRaw('is_soldout asc')->orderByDesc('external_no'),
        };

        $products = $query->paginate(min((int) $request->query('per_page', 20), 50));

        return ProductResource::collection($products);
    }

    /** 상품 상세 */
    public function show(Product $product)
    {
        $product->load(['images', 'category']);

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->whereNotNull('main_image')
            ->inRandomOrder()->limit(6)->get();

        return response()->json([
            'product' => new ProductDetailResource($product),
            'related' => ProductResource::collection($related),
        ]);
    }
}
