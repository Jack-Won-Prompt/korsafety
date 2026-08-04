<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\RichTextSanitizer;
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

    /** 정렬 옵션 (라벨 → orderBy 처리는 아래 match) */
    public const SORTS = [
        'latest' => '최근 등록순', 'display' => '진열 순서', 'name' => '상품명순',
        'price_desc' => '높은 가격순', 'price_asc' => '낮은 가격순', 'stock_asc' => '재고 적은순',
    ];

    /** 판매 상태 필터 */
    public const STATES = [
        'onsale' => '판매중', 'soldout' => '품절', 'hidden' => '미노출', 'noimage' => '이미지 없음', 'best' => '베스트 셀러',
    ];

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $state = $request->query('state');
        $stock = $request->query('stock');
        $sort = $request->query('sort', 'latest');

        $query = $this->scoped()->with('category');

        if ($q !== '') {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")
                ->orWhere('brand', 'like', "%$q%")
                ->orWhere('sku', 'like', "%$q%"));
        }
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }
        match ($state) {
            'onsale' => $query->where('is_active', true)->where('is_soldout', false),
            'soldout' => $query->where('is_soldout', true),
            'hidden' => $query->where('is_active', false),
            'noimage' => $query->where(fn ($w) => $w->whereNull('main_image')->orWhere('main_image', '')),
            'best' => $query->where('is_best', true),
            default => null,
        };
        match ($stock) {
            'out' => $query->outOfStock(),
            'low' => $query->lowStock(),
            'untracked' => $query->where('track_stock', false),
            default => null,
        };
        match ($sort) {
            'display' => $query->orderBy('sort')->orderByDesc('id'),
            'name' => $query->orderBy('name'),
            'price_desc' => $query->orderByRaw('COALESCE(sale_price, price) desc'),
            'price_asc' => $query->orderByRaw('COALESCE(sale_price, price) asc'),
            'stock_asc' => $query->orderBy('stock'),
            default => $query->latest('id'),
        };

        $products = $query->paginate(20)->withQueryString();

        // 요약 타일 — 필터와 무관하게 스토어 전체 기준
        $base = fn () => $this->scoped();
        $stats = [
            'total' => $base()->count(),
            'onsale' => $base()->where('is_active', true)->where('is_soldout', false)->count(),
            'soldout' => $base()->where('is_soldout', true)->count(),
            'hidden' => $base()->where('is_active', false)->count(),
            'low' => $base()->lowStock()->count(),
            'out' => $base()->outOfStock()->count(),
            'tracked' => $base()->where('track_stock', true)->count(),
        ];

        return view('manage.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('sort')->orderBy('name')->get(),
            'stats' => $stats,
            'q' => $q,
            'categoryId' => $categoryId,
            'state' => $state,
            'stock' => $stock,
            'sort' => $sort,
        ]);
    }

    /** 선택 상품 일괄 처리 — 판매상태 · 노출 · 카테고리 이동 · 삭제 */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'bulk_action' => 'required|in:onsale,soldout,activate,deactivate,track_on,track_off,best_on,best_off,category,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'bulk_category_id' => 'nullable|exists:categories,id',
        ], [], ['ids' => '상품', 'bulk_action' => '일괄 작업']);

        // 남의 스토어 상품이 섞여 들어와도 자기 것만 처리
        $ids = $this->scoped()->whereIn('id', $data['ids'])->pluck('id');
        if ($ids->isEmpty()) {
            return back()->with('error', '처리할 상품이 없습니다.');
        }

        $rows = Product::whereIn('id', $ids);
        $n = $ids->count();

        switch ($data['bulk_action']) {
            case 'onsale':   $rows->update(['is_soldout' => false]); $msg = '판매중으로 변경'; break;
            case 'soldout':  $rows->update(['is_soldout' => true]);  $msg = '품절 처리'; break;
            case 'activate': $rows->update(['is_active' => true]);   $msg = '노출 처리'; break;
            case 'deactivate': $rows->update(['is_active' => false]); $msg = '미노출 처리'; break;
            case 'track_on':  $rows->update(['track_stock' => true]);  $msg = '재고 관리 켜기'; break;
            case 'track_off': $rows->update(['track_stock' => false]); $msg = '재고 관리 끄기'; break;
            case 'best_on':   $rows->update(['is_best' => true]);  $msg = '베스트 셀러 지정'; break;
            case 'best_off':  $rows->update(['is_best' => false]); $msg = '베스트 셀러 해제'; break;
            case 'category':
                if (empty($data['bulk_category_id'])) {
                    return back()->with('error', '이동할 카테고리를 선택하세요.');
                }
                $rows->update(['category_id' => $data['bulk_category_id']]);
                // 예전 카테고리 연결이 남으면 쇼핑몰에서 이동이 되지 않으므로 sync로 교체
                foreach ($ids as $id) {
                    Product::find($id)?->categories()->sync([$data['bulk_category_id']]);
                }
                $msg = '카테고리 이동';
                break;
            default:
                $rows->delete();
                $msg = '삭제';
        }

        return back()->with('status', "{$n}개 상품을 {$msg}했습니다.");
    }

    /** 목록에서 고친 판매가 · 할인가 · 재고를 한 번에 저장 */
    public function quickSave(Request $request)
    {
        $rows = (array) $request->input('rows', []);
        $changed = 0;

        foreach ($rows as $id => $vals) {
            $product = $this->scoped()->find((int) $id);
            if (! $product) continue;

            $update = [];
            foreach (['price', 'sale_price', 'stock'] as $field) {
                if (! array_key_exists($field, $vals)) continue;
                $raw = trim((string) $vals[$field]);
                $value = $raw === '' ? null : (int) $raw;
                if ($value !== null && $value < 0) continue;
                if ($field === 'stock') { $value = (int) $value; }   // 재고는 비우면 0
                if ($product->{$field} != $value) {
                    $update[$field] = $value;
                }
            }
            if ($update) {
                $product->update($update);
                $changed++;
            }
        }

        return back()->with('status', $changed ? "{$changed}개 상품의 가격·재고를 저장했습니다." : '변경된 값이 없습니다.');
    }

    public function create()
    {
        $categories = Category::orderBy('sort')->orderBy('name')->get();
        $product = new Product([
            'is_soldout' => false, 'is_active' => true, 'track_stock' => true,
            'stock' => 0, 'safety_stock' => 0, 'sort' => 0,
        ]);
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
        $this->syncOptions($product, $request);

        return redirect()->route('manage.products.index')->with('status', '상품이 등록되었습니다.');
    }

    public function edit(Product $product)
    {
        $this->authorizeOwner($product);
        $product->load('galleryImages', 'detailImages', 'options');
        $categories = Category::orderBy('sort')->orderBy('name')->get();
        return view('manage.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        $data = $this->validated($request);
        $this->fill($product, $data, $request);
        $product->save();
        $this->syncCategory($product, $data['category_id'] ?? null);

        // 선택한 이미지 삭제 (갤러리 · 상세 공통)
        $removeIds = array_filter((array) $request->input('remove_images', []));
        if ($removeIds) {
            ProductImage::where('product_id', $product->id)->whereIn('id', $removeIds)->delete();
        }
        $this->handleGallery($product, $request);
        $this->syncOptions($product, $request);

        return redirect()->route('manage.products.edit', $product)->with('status', '상품이 수정되었습니다.');
    }

    public function destroy(Product $product)
    {
        $this->authorizeOwner($product);
        $product->delete();
        return back()->with('status', '상품이 삭제되었습니다.');
    }

    /**
     * 상세 설명 리치 에디터의 이미지 업로드 (붙여넣기 · 툴바).
     * 스토어별 업로드 폴더에 저장하고 공개 URL을 돌려준다.
     */
    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:8192'], [], ['image' => '이미지']);

        $dir = public_path('shop/uploads/'.$this->sellerId().'/editor');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $request->file('image');
        $name = date('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension());
        $file->move($dir, $name);

        return response()->json(['url' => asset('shop/uploads/'.$this->sellerId().'/editor/'.$name)]);
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
    private const CSV_HEADER = ['상품ID', 'SKU', '상품명', '브랜드', '카테고리', '판매가', '할인가', '재고', '품절(1=품절)', '노출(1=노출)', '대표이미지경로'];

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
                    $p->sku,
                    $p->name,
                    $p->brand,
                    optional($p->category)->name,
                    $p->price,
                    $p->sale_price,
                    $p->stock,
                    $p->is_soldout ? 1 : 0,
                    $p->is_active ? 1 : 0,
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
            // 엑셀이 저장한 CP949(한글 ANSI) → UTF-8 변환 (UTF-8이면 그대로)
            $row = array_map(fn ($v) => $this->toUtf8($v), $row);
            // 헤더 줄 스킵
            if ($line === 1 && trim((string) ($row[0] ?? '')) === '상품ID') { continue; }
            // 빈 줄 스킵
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) { continue; }

            [$id, $sku, $name, $brand, $catName, $price, $sale, $stock, $soldout, $active, $image] = array_pad($row, 11, null);
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
            $product->sku = trim((string) $sku) ?: null;
            $product->brand = trim((string) $brand) ?: null;
            if ($catId) { $product->category_id = $catId; }
            $product->price = is_numeric($price) ? (int) $price : null;
            $product->sale_price = is_numeric($sale) ? (int) $sale : null;
            if (is_numeric($stock)) { $product->stock = max(0, (int) $stock); }
            $product->is_soldout = (trim((string) $soldout) === '1');
            // 노출 칸이 비어 있으면 기존 값 유지 (신규는 노출)
            $activeRaw = trim((string) $active);
            if ($activeRaw !== '') { $product->is_active = ($activeRaw === '1'); }
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

    /** 셀 값을 UTF-8로 정규화 (엑셀 CP949/EUC-KR 저장분 대응) */
    private function toUtf8($value): ?string
    {
        if ($value === null) { return null; }
        $value = (string) $value;
        if ($value === '' || mb_check_encoding($value, 'UTF-8')) { return $value; }
        // CP949(EUC-KR 상위집합)로 간주하고 변환
        return mb_convert_encoding($value, 'UTF-8', 'CP949');
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
            'sku' => 'nullable|string|max:64',
            'brand' => 'nullable|string|max:120',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'nullable|integer|min:0',
            'cost_price' => 'nullable|integer|min:0',
            'sale_price' => 'nullable|integer|min:0',
            'stock' => 'nullable|integer|min:0|max:999999',
            'safety_stock' => 'nullable|integer|min:0|max:999999',
            'track_stock' => 'nullable|boolean',
            'sort' => 'nullable|integer|min:0|max:9999',
            'description' => 'nullable|string|max:500000',
            'is_soldout' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'main_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
            'gallery.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
            'detail_images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:8192',
            'options' => 'nullable|array|max:100',
            'options.*.name' => 'nullable|string|max:120',
            'options.*.group_name' => 'nullable|string|max:60',
            'options.*.extra_price' => 'nullable|integer|min:-10000000|max:10000000',
            'options.*.stock' => 'nullable|integer|min:0|max:999999',
        ], [], [
            'name' => '상품명', 'price' => '판매가', 'cost_price' => '매입가', 'sale_price' => '할인가',
            'stock' => '재고', 'safety_stock' => '안전재고', 'sort' => '진열 순서', 'main_image' => '대표 이미지',
        ]);
    }

    private function fill(Product $product, array $data, Request $request): void
    {
        $product->name = $data['name'];
        $product->sku = $data['sku'] ?? null;
        $product->brand = $data['brand'] ?? null;
        $product->category_id = $data['category_id'] ?? null;
        $product->price = $data['price'] ?? null;
        $product->cost_price = $data['cost_price'] ?? null;
        $product->sale_price = $data['sale_price'] ?? null;
        $product->stock = (int) ($data['stock'] ?? 0);
        $product->safety_stock = (int) ($data['safety_stock'] ?? 0);
        $product->track_stock = $request->boolean('track_stock');
        $product->sort = (int) ($data['sort'] ?? 0);
        $product->description = RichTextSanitizer::clean($data['description'] ?? null);
        $product->is_soldout = $request->boolean('is_soldout');
        $product->is_active = $request->boolean('is_active');
        if (! $product->slug) {
            $product->slug = Str::limit(Str::slug($data['name']) ?: 'p'.Str::random(6), 120, '');
        }
        if ($request->hasFile('main_image')) {
            $product->main_image = $this->saveUpload($request->file('main_image'));
        }
    }

    /**
     * 대표 카테고리를 다대다 연결과 일치시킨다.
     * syncWithoutDetaching을 쓰면 예전 카테고리 연결이 남아 쇼핑몰에서 카테고리 이동이 되지 않는다.
     */
    private function syncCategory(Product $product, $categoryId): void
    {
        $product->categories()->sync($categoryId ? [$categoryId] : []);
    }

    /** 옵션 행 저장 — 화면에서 지운 행은 삭제 */
    private function syncOptions(Product $product, Request $request): void
    {
        $rows = (array) $request->input('options', []);
        $keepIds = [];
        $sort = 0;

        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;   // 옵션명이 비면 빈 행으로 보고 건너뜀
            }
            $attrs = [
                'group_name' => trim((string) ($row['group_name'] ?? '')) ?: null,
                'name' => mb_substr($name, 0, 120),
                'extra_price' => (int) ($row['extra_price'] ?? 0),
                'stock' => max(0, (int) ($row['stock'] ?? 0)),
                'is_active' => ! empty($row['is_active']),
                'sort' => $sort++,
            ];

            $id = (int) ($row['id'] ?? 0);
            $option = $id ? $product->options()->find($id) : null;
            if ($option) {
                $option->update($attrs);
            } else {
                $option = $product->options()->create($attrs);
            }
            $keepIds[] = $option->id;
        }

        $product->options()->whereNotIn('id', $keepIds ?: [0])->delete();
    }

    private function handleGallery(Product $product, Request $request): void
    {
        $this->storeImages($product, $request, 'gallery', 'gallery');
        $this->storeImages($product, $request, 'detail_images', 'detail');

        if (! $product->main_image && $product->galleryImages()->exists()) {
            $product->update(['main_image' => $product->galleryImages()->first()->path]);
        }
    }

    /** 업로드된 이미지들을 지정한 타입(gallery|detail)으로 저장 */
    private function storeImages(Product $product, Request $request, string $field, string $type): void
    {
        if (! $request->hasFile($field)) {
            return;
        }
        $sort = (int) $product->images()->where('type', $type)->max('sort');
        foreach ($request->file($field) as $file) {
            if (! $file) continue;
            ProductImage::create([
                'product_id' => $product->id,
                'path' => $this->saveUpload($file),
                'type' => $type,
                'sort' => ++$sort,
            ]);
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
