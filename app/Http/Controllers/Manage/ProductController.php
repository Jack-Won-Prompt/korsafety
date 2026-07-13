<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    private function sellerId(): int
    {
        return Auth::user()->seller_id;
    }

    /** 현재 스토어의 상품만 조회하도록 스코프 */
    private function scoped()
    {
        return Product::where('seller_id', $this->sellerId());
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = $this->scoped()->with('category')->latest('id');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('brand', 'like', "%$q%"));
        }
        $products = $query->paginate(15)->withQueryString();
        return view('manage.products.index', compact('products', 'q'));
    }

    public function create()
    {
        $categories = Category::orderBy('sort')->get();
        $product = new Product(['is_soldout' => false]);
        return view('manage.products.form', compact('product', 'categories'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $product = new Product();
        $product->seller_id = $this->sellerId();
        $this->fill($product, $data, $request);
        $product->save();
        $this->syncCategory($product, $data['category_id'] ?? null);
        $this->handleGallery($product, $request);

        return redirect()->route('manage.products.index')->with('status', '상품이 등록되었습니다.');
    }

    public function edit(Product $product)
    {
        $this->authorizeOwner($product);
        $product->load('galleryImages');
        $categories = Category::orderBy('sort')->get();
        return view('manage.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        $data = $this->validated($request);
        $this->fill($product, $data, $request);
        $product->save();
        $this->syncCategory($product, $data['category_id'] ?? null);

        // 선택 갤러리 삭제
        foreach ((array) $request->input('remove_images', []) as $imgId) {
            ProductImage::where('product_id', $product->id)->where('id', $imgId)->delete();
        }
        $this->handleGallery($product, $request);

        return redirect()->route('manage.products.index')->with('status', '상품이 수정되었습니다.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeOwner($product);
        $product->delete();
        return back()->with('status', '상품이 삭제되었습니다.');
    }

    /** 대표 이미지 편집기 (회전·밝기·대비·크롭) */
    public function editImage(Product $product)
    {
        $this->authorizeOwner($product);
        abort_if(! $product->main_image, 404, '편집할 대표 이미지가 없습니다.');
        return view('manage.products.image-editor', compact('product'));
    }

    public function saveImage(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        $data = (string) $request->input('image', '');
        if (! preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $data, $m)) {
            return back()->withErrors(['image' => '이미지 데이터가 올바르지 않습니다.']);
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $bin = base64_decode(substr($data, strpos($data, ',') + 1), true);
        if ($bin === false || strlen($bin) < 100) {
            return back()->withErrors(['image' => '이미지 저장에 실패했습니다.']);
        }

        $dir = public_path('shop/uploads/'.$this->sellerId());
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $name = 'edit_'.date('Ymd_His').'_'.Str::lower(Str::random(5)).'.'.$ext;
        file_put_contents($dir.'/'.$name, $bin);

        $product->update(['main_image' => '/shop/uploads/'.$this->sellerId().'/'.$name]);

        return redirect()->route('manage.products.edit', $product)->with('status', '이미지가 편집·저장되었습니다.');
    }

    // ---- helpers ----
    private function authorizeOwner(Product $product): void
    {
        abort_unless($product->seller_id === $this->sellerId(), 403, '본인 스토어 상품만 관리할 수 있습니다.');
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
            'main_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
            'gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
        ], [], ['name' => '상품명', 'price' => '판매가', 'main_image' => '대표 이미지']);
    }

    private function fill(Product $product, array $data, Request $request): void
    {
        $product->name = $data['name'];
        $product->brand = $data['brand'] ?? null;
        $product->category_id = $data['category_id'] ?? null;
        $product->price = $data['price'] ?? null;
        $product->sale_price = $data['sale_price'] ?? null;
        $product->is_soldout = $request->boolean('is_soldout');
        if (! $product->slug) {
            $product->slug = Str::limit(Str::slug($data['name']) ?: 'p'.Str::random(6), 120, '');
        }
        if ($request->hasFile('main_image')) {
            $product->main_image = $this->saveUpload($request->file('main_image'));
        }
    }

    private function syncCategory(Product $product, $categoryId): void
    {
        if ($categoryId) {
            $product->categories()->syncWithoutDetaching([$categoryId]);
        }
    }

    private function handleGallery(Product $product, Request $request): void
    {
        if (! $request->hasFile('gallery')) return;
        $sort = (int) $product->galleryImages()->max('sort');
        foreach ($request->file('gallery') as $file) {
            if (! $file) continue;
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $this->saveUpload($file),
                'type' => 'gallery',
                'sort' => ++$sort,
            ]);
        }
        if (! $product->main_image && $product->galleryImages()->exists()) {
            $product->update(['main_image' => $product->galleryImages()->first()->path]);
        }
    }

    private function saveUpload($file): string
    {
        $dir = public_path('shop/uploads/'.$this->sellerId());
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $name = date('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);
        return '/shop/uploads/'.$this->sellerId().'/'.$name;
    }
}
