<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private function sellerId(Request $request): int
    {
        return $request->user()->seller_id;
    }

    public function index(Request $request)
    {
        $query = Product::where('seller_id', $this->sellerId($request))->with('category')->latest('id');

        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('brand', 'like', "%$q%"));
        }

        return ProductResource::collection($query->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $product = new Product();
        $product->seller_id = $this->sellerId($request);
        $this->fill($product, $data, $request);
        $product->save();
        if (! empty($data['category_id'])) {
            $product->categories()->sync([$data['category_id']]);
        }

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->seller_id === $this->sellerId($request), 403);
        $data = $this->validated($request);
        $this->fill($product, $data, $request);
        $product->save();
        if (array_key_exists('category_id', $data)) {
            $product->categories()->sync(array_filter([$data['category_id'] ?? null]));
        }

        return response()->json(['data' => new ProductResource($product)]);
    }

    public function destroy(Request $request, Product $product)
    {
        abort_unless($product->seller_id === $this->sellerId($request), 403);
        $product->delete();

        return response()->json(['message' => '상품이 삭제되었습니다.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:250',
            'brand' => 'nullable|string|max:120',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'nullable|integer|min:0',
            'sale_price' => 'nullable|integer|min:0',
            'is_soldout' => 'nullable|boolean',
            'main_image' => 'nullable|string|max:500',
        ]);
    }

    private function fill(Product $product, array $data, Request $request): void
    {
        $product->name = $data['name'];
        $product->brand = $data['brand'] ?? null;
        $product->category_id = $data['category_id'] ?? null;
        $product->price = $data['price'] ?? null;
        $product->sale_price = $data['sale_price'] ?? null;
        $product->is_soldout = $request->boolean('is_soldout');
        if (array_key_exists('main_image', $data)) {
            $product->main_image = $data['main_image'] ?: $product->main_image;
        }
        if (! $product->slug) {
            $product->slug = Str::limit(Str::slug($data['name']) ?: 'p'.Str::random(6), 120, '');
        }
    }
}
