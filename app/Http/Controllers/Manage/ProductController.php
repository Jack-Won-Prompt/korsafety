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

    /** CSV 헤더 (엑셀 호환, UTF-8) */
    private const CSV_HEADER = ['상품ID', '상품명', '브랜드', '카테고리', '판매가', '할인가', '품절(1=품절)', '대표이미지경로'];

    /** 전체 품목 엑셀(CSV) 다운로드 — 현재 스토어 스코프 */
    public function exportCsv()
    {
        $filename = 'products_'.date('Ymd_His').'.csv';
        $products = $this->scoped()->with('category')->orderBy('id')->get();

        return response()->streamDownload(function () use ($products) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM (엑셀 한글 깨짐 방지)
            fputcsv($out, self::CSV_HEADER);
            foreach ($products as $p) {
                fputcsv($out, [
                    $p->id,
                    $p->name,
                    $p->brand,
                    optional($p->category)->name,
                    $p->price,
                    $p->sale_price,
                    $p->is_soldout ? 1 : 0,
                    $p->main_image,
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 업로드용 빈 템플릿(헤더만) 다운로드 */
    public function importTemplate()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::CSV_HEADER);
            fclose($out);
        }, 'products_template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 엑셀(CSV) 업로드 — 상품ID가 있으면 수정, 없으면 신규 등록 */
    public function importCsv(Request $request)
    {
        $request->validate(['file' => 'required|file|max:8192'], [], ['file' => '파일']);

        $upload = $request->file('file');
        $ext = strtolower($upload->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'CSV(.csv) 파일만 업로드할 수 있습니다.']);
        }

        $path = $upload->getRealPath();
        $fh = fopen($path, 'r');
        if ($fh === false) {
            return back()->withErrors(['file' => '파일을 열 수 없습니다.']);
        }

        $categories = Category::pluck('id', 'name'); // 이름 → id
        $created = 0; $updated = 0; $skipped = 0; $line = 0;

        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            // 첫 열의 BOM 제거
            if (isset($row[0])) { $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]); }
            // 헤더 줄 스킵
            if ($line === 1 && trim((string) ($row[0] ?? '')) === '상품ID') { continue; }
            // 빈 줄 스킵
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) { continue; }

            [$id, $name, $brand, $catName, $price, $sale, $soldout, $image] = array_pad($row, 8, null);
            $name = trim((string) $name);
            if ($name === '') { $skipped++; continue; }

            $id = (int) trim((string) $id);
            $product = $id > 0 ? $this->scoped()->find($id) : null;
            $isNew = false;
            if (! $product) {
                $product = new Product();
                $product->seller_id = $this->sellerId();
                $isNew = true;
            }

            $catId = null;
            $catName = trim((string) $catName);
            if ($catName !== '') { $catId = $categories[$catName] ?? null; }

            $product->name = $name;
            $product->brand = trim((string) $brand) ?: null;
            if ($catId) { $product->category_id = $catId; }
            $product->price = is_numeric($price) ? (int) $price : null;
            $product->sale_price = is_numeric($sale) ? (int) $sale : null;
            $product->is_soldout = (trim((string) $soldout) === '1');
            if (trim((string) $image) !== '') { $product->main_image = trim((string) $image); }
            if (! $product->slug) {
                $product->slug = Str::limit(Str::slug($name) ?: 'p'.Str::random(6), 120, '');
            }
            $product->save();
            if ($catId) { $product->categories()->syncWithoutDetaching([$catId]); }

            $isNew ? $created++ : $updated++;
        }
        fclose($fh);

        return redirect()->route('manage.products.index')
            ->with('status', "엑셀 반영 완료 — 신규 {$created}건, 수정 {$updated}건".($skipped ? ", 건너뜀 {$skipped}건" : ''));
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
