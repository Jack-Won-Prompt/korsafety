<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\RichTextSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /** 상세 설명 에디터 업로드로 허용하는 확장자 */
    private const EDITOR_IMAGE_EXTS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif'];

    private function sellerId(): int
    {
        return Auth::user()->seller_id;
    }

    /** 본사 계정인가 — 본사는 판매점 상품까지 모두 관리한다 */
    private function isHq(): bool
    {
        return (bool) optional(Auth::user())->isHqAdmin();
    }

    /** 조회 스코프 — 본사는 전체 스토어, 판매점은 자기 상품만 */
    private function scoped()
    {
        $query = Product::query();

        return $this->isHq() ? $query : $query->where('seller_id', $this->sellerId());
    }

    /** 정렬 옵션 (라벨 → orderBy 처리는 아래 match) */
    public const SORTS = [
        'latest' => '최근 등록순', 'display' => '진열 순서', 'name' => '상품명순',
        'price_desc' => '판매가 높은순', 'price_asc' => '판매가 낮은순',
        'sale_desc' => '할인가 높은순', 'sale_asc' => '할인가 낮은순',
        'stock_desc' => '재고 많은순', 'stock_asc' => '재고 적은순',
        'state_onsale' => '판매중 먼저', 'state_off' => '미판매중 먼저',
    ];

    /** 판매 상태 필터 */
    public const STATES = [
        'onsale' => '판매중', 'soldout' => '품절', 'hidden' => '미노출', 'noimage' => '이미지 없음', 'best' => '베스트 셀러',
    ];

    /** 한 화면에 보여줄 상품 수 */
    public const PER_PAGES = [20, 50, 100, 200];

    /** 요청한 목록 개수 (허용값 밖이면 기본 20개) */
    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 20);

        return in_array($perPage, self::PER_PAGES, true) ? $perPage : 20;
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $state = $request->query('state');
        $stock = $request->query('stock');
        $sort = $request->query('sort', 'latest');

        // categories까지 미리 불러와 목록에서 대 > 중 > 소 경로를 전부 보여준다
        $query = $this->scoped()->with(['category', 'categories', 'seller']);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                    ->orWhere('brand', 'like', "%$q%")
                    ->orWhere('sku', 'like', "%$q%")
                    ->orWhere('product_code', 'like', "%$q%");

                // 스크랩 원본 번호와 상품 ID로도 찾을 수 있게 (입력 전체가 숫자일 때만)
                if (preg_match('/^\d+$/', $q)) {
                    $w->orWhere('external_no', (int) $q)->orWhere('id', (int) $q);
                }
            });
        }
        if ($categoryId) {
            // 다중 카테고리를 쓰므로 연결 기준으로 찾고, 상위를 고르면 하위 분류까지 포함한다
            $catIds = optional(Category::find($categoryId))->descendantIds() ?: [(int) $categoryId];
            $query->whereHas('categories', fn ($w) => $w->whereIn('categories.id', $catIds));
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
            // 값이 비어 있는 상품은 어느 방향이든 뒤로 보낸다
            'price_desc' => $query->orderByRaw('price is null, price desc')->orderByDesc('id'),
            'price_asc' => $query->orderByRaw('price is null, price asc')->orderByDesc('id'),
            'sale_desc' => $query->orderByRaw('sale_price is null, sale_price desc')->orderByDesc('id'),
            'sale_asc' => $query->orderByRaw('sale_price is null, sale_price asc')->orderByDesc('id'),
            'stock_desc' => $query->orderByDesc('stock')->orderByDesc('id'),
            'stock_asc' => $query->orderBy('stock')->orderByDesc('id'),
            // 판매중 = 쇼핑몰 노출 + 품절 아님
            'state_onsale' => $query->orderByRaw('(is_active = 1 and is_soldout = 0) desc')->orderByDesc('id'),
            'state_off' => $query->orderByRaw('(is_active = 1 and is_soldout = 0) asc')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $perPage = $this->perPage($request);
        $products = $query->paginate($perPage)->withQueryString();

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
            'trashed' => $base()->onlyTrashed()->count(),
        ];

        $categories = Category::flatTree();

        return view('manage.products.index', [
            'products' => $products,
            'categories' => $categories,
            // id => '대 > 중 > 소' 경로 (트리 순서 그대로여서 표시 순서로도 쓴다)
            'catPaths' => $categories->pluck('tree_path', 'id'),
            'stats' => $stats,
            'q' => $q,
            'categoryId' => $categoryId,
            'state' => $state,
            'stock' => $stock,
            'sort' => $sort,
            'perPage' => $perPage,
        ]);
    }

    /** 삭제한 상품 목록 (휴지통) — 복구하거나 완전히 지울 수 있다 */
    public function trash(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $query = $this->scoped()->onlyTrashed()->with('category');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                    ->orWhere('brand', 'like', "%$q%")
                    ->orWhere('sku', 'like', "%$q%")
                    ->orWhere('product_code', 'like', "%$q%");
                if (preg_match('/^\d+$/', $q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        $perPage = $this->perPage($request);
        $products = $query->orderByDesc('deleted_at')->paginate($perPage)->withQueryString();

        return view('manage.products.trash', compact('products', 'q', 'perPage'));
    }

    /** 삭제한 상품을 되살린다 */
    public function restore(int $id)
    {
        $product = $this->scoped()->onlyTrashed()->findOrFail($id);
        $product->restore();

        return back()->with('status', "'{$product->name}' 상품을 되살렸습니다. 쇼핑몰 노출 상태를 확인해 주세요.");
    }

    /** 휴지통에서 완전히 지운다 (되돌릴 수 없음) */
    public function forceDestroy(int $id)
    {
        $product = $this->scoped()->onlyTrashed()->findOrFail($id);
        $name = $product->name;
        $product->forceDelete();

        return back()->with('status', "'{$name}' 상품을 완전히 삭제했습니다.");
    }

    /** 선택 상품 일괄 처리 — 판매상태 · 노출 · 카테고리 이동 · 삭제 */
    public function bulk(Request $request)
    {
        $data = $request->validate([
            'bulk_action' => 'required|in:onsale,soldout,activate,deactivate,track_on,track_off,best_on,best_off,category,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'bulk_category_id' => 'nullable|exists:categories,id',
            'bulk_category_ids' => 'nullable|array',
            'bulk_category_ids.*' => 'integer|exists:categories,id',
        ], [], ['ids' => '상품', 'bulk_action' => '일괄 작업', 'bulk_category_ids' => '카테고리']);

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
                // 카테고리를 여러 개 고를 수 있다 (예전 단일 선택 요청도 그대로 받는다)
                $catIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['bulk_category_ids'] ?? [])))));
                if (! $catIds && ! empty($data['bulk_category_id'])) {
                    $catIds = [(int) $data['bulk_category_id']];
                }
                if (! $catIds) {
                    return back()->with('error', '이동할 카테고리를 선택하세요.');
                }

                // 대표 분류: 상품 수정 화면과 같은 규칙으로 가장 하위(소분류 우선)를 쓴다
                $primary = Category::whereIn('id', $catIds)->get()
                    ->sortByDesc(fn ($c) => $c->depth)->first()?->id;
                $rows->update(['category_id' => $primary]);
                // 예전 카테고리 연결이 남으면 쇼핑몰에서 이동이 되지 않으므로 sync로 교체
                foreach ($ids as $id) {
                    Product::find($id)?->categories()->sync($catIds);
                }
                $msg = count($catIds) > 1 ? '카테고리 '.count($catIds).'개로 이동' : '카테고리 이동';
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
        $categories = Category::flatTree();
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
        $this->syncCategories($product, $data);
        $this->handleGallery($product, $request);
        $this->syncOptions($product, $request);

        return redirect()->route('manage.products.index')->with('status', '상품이 등록되었습니다.');
    }

    public function edit(Product $product)
    {
        $this->authorizeOwner($product);
        $product->load('galleryImages', 'detailImages', 'options', 'categories');
        $categories = Category::flatTree();
        return view('manage.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $this->authorizeOwner($product);
        $data = $this->validated($request, $product);
        $this->fill($product, $data, $request);
        $product->save();
        $this->syncCategories($product, $data);

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
        return back()->with('status', '상품을 삭제했습니다. 휴지통에서 되살릴 수 있습니다.');
    }

    /** 상품 복사 — 카테고리·이미지·옵션까지 복제하고 미노출 상태로 만든다 */
    public function duplicate(Product $product)
    {
        $this->authorizeOwner($product);
        $product->load('images', 'options', 'categories');

        $copy = $product->replicate(['external_no', 'sku', 'product_code', 'slug']);
        $copy->name = mb_substr($product->name, 0, 240).' (복사)';
        // slug 는 비워 둘 수 없다 — 이름 기준으로 새로 만들되 겹치지 않게 뒤에 임의 문자를 붙인다
        $copy->slug = Str::limit(Str::slug($copy->name) ?: 'p', 112, '').'-'.Str::lower(Str::random(6));
        $copy->is_active = false;
        $copy->is_best = false;
        $copy->best_sort = 0;
        $copy->save();

        $copy->categories()->sync($product->categories->pluck('id')->all());

        foreach ($product->images as $image) {
            $copy->images()->create(['path' => $image->path, 'type' => $image->type, 'sort' => $image->sort]);
        }
        foreach ($product->options as $option) {
            $copy->options()->create([
                'group_name' => $option->group_name, 'name' => $option->name,
                'extra_price' => $option->extra_price, 'stock' => $option->stock,
                'is_active' => $option->is_active, 'sort' => $option->sort,
            ]);
        }

        return redirect()->route('manage.products.edit', $copy)
            ->with('status', '상품을 복사했습니다. 내용을 고친 뒤 저장하세요. (복사본은 미노출 상태입니다)');
    }

    /**
     * 상세 설명 리치 에디터의 이미지 업로드 (붙여넣기 · 툴바).
     * 스토어별 업로드 폴더에 저장하고 공개 URL을 돌려준다.
     * 어떤 경우에도 JSON으로 응답한다 — 에디터는 fetch로 호출하므로 HTML이 오면 파싱에 실패한다.
     */
    public function uploadImage(Request $request)
    {
        // post_max_size 초과 등으로 파일 자체가 도착하지 않는 경우를 먼저 구분한다
        if (! $request->hasFile('image')) {
            return response()->json([
                'message' => '이미지가 전송되지 않았습니다. 파일 용량이 서버 허용치를 넘었을 수 있습니다.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'image' => [
                'required',
                'image',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/bmp,image/x-ms-bmp,image/avif',
                'max:8192',
            ],
        ], [
            'image.required' => '이미지를 선택해 주세요.',
            'image.image' => '이미지 파일만 올릴 수 있습니다.',
            'image.mimetypes' => 'JPG · PNG · GIF · WEBP · BMP 형식만 올릴 수 있습니다.',
            'image.max' => '이미지 용량은 8MB 이하여야 합니다.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first('image')], 422);
        }

        $dir = public_path('shop/uploads/'.$this->sellerId().'/editor');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return response()->json(['message' => '업로드 폴더를 만들 수 없습니다. 관리자에게 문의해 주세요.'], 500);
        }

        $file = $request->file('image');

        // 붙여넣기한 이미지는 파일명·확장자가 없을 수 있다(확장자가 빈 문자열이면
        // 저장 파일명이 점으로 끝나 Windows에서 move가 실패한다) → MIME으로 확장자를 정한다
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, self::EDITOR_IMAGE_EXTS, true)) {
            $ext = strtolower((string) $file->guessExtension());
        }
        if (! in_array($ext, self::EDITOR_IMAGE_EXTS, true)) {
            $ext = 'png';
        }
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $name = date('Ymd_His').'_'.Str::lower(Str::random(6)).'.'.$ext;

        try {
            $file->move($dir, $name);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => '이미지를 저장하지 못했습니다. 잠시 후 다시 시도해 주세요.'], 500);
        }

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
        $fail = function (string $message) use ($request) {
            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['image' => $message]);
        };

        $data = (string) $request->input('image', '');
        if (! preg_match('/^data:image\/(png|jpeg|jpg|webp);base64,/', $data, $m)) {
            return $fail('이미지 데이터가 올바르지 않습니다.');
        }
        $ext = $m[1] === 'jpeg' ? 'jpg' : $m[1];
        $bin = base64_decode(substr($data, strpos($data, ',') + 1), true);
        if ($bin === false || strlen($bin) < 100) {
            return $fail('이미지 저장에 실패했습니다.');
        }

        $dir = public_path('shop/uploads/'.$this->sellerId());
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $name = 'edit_'.date('Ymd_His').'_'.Str::lower(Str::random(5)).'.'.$ext;
        file_put_contents($dir.'/'.$name, $bin);

        $path = '/shop/uploads/'.$this->sellerId().'/'.$name;
        $product->update(['main_image' => $path]);

        // 모달에서 저장한 경우 새 이미지 주소만 돌려주고 화면은 그대로 둔다
        if ($request->wantsJson()) {
            return response()->json(['url' => asset($path), 'message' => '이미지가 편집·저장되었습니다.']);
        }

        return redirect()->route('manage.products.edit', $product)->with('status', '이미지가 편집·저장되었습니다.');
    }

    /** CSV 헤더 (엑셀 호환, UTF-8) */
    private const CSV_HEADER = ['상품ID', '상품코드', 'SKU', '상품명', '브랜드', '카테고리', '판매가', '할인가', '재고', '품절(1=품절)', '노출(1=노출)', '대표이미지경로'];

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
                    $p->product_code,
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

            [$id, $code, $sku, $name, $brand, $catName, $price, $sale, $stock, $soldout, $active, $image] = array_pad($row, 12, null);
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
            $product->product_code = trim((string) $code) ?: null;
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
        abort_unless($this->isHq() || $product->seller_id === $this->sellerId(), 403, '본인 스토어 상품만 관리할 수 있습니다.');
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:250',
            'sku' => 'nullable|string|max:64',
            'product_code' => ['nullable', 'string', 'max:64',
                Rule::unique('products', 'product_code')->ignore($product?->id)],
            'brand' => 'nullable|string|max:120',
            'category_id' => 'nullable|exists:categories,id',
            'category_ids' => 'nullable|array|max:20',
            'category_ids.*' => 'integer|exists:categories,id',
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
        ], [
            'product_code.unique' => '이미 사용 중인 상품코드입니다. 다른 값을 입력해 주세요.',
        ], [
            'name' => '상품명', 'product_code' => '상품코드', 'price' => '판매가', 'cost_price' => '매입가', 'sale_price' => '할인가',
            'stock' => '재고', 'safety_stock' => '안전재고', 'sort' => '진열 순서', 'main_image' => '대표 이미지',
        ]);
    }

    private function fill(Product $product, array $data, Request $request): void
    {
        $product->name = $data['name'];
        $product->sku = $data['sku'] ?? null;
        // 상품코드는 관리자가 입력한 값을 그대로 쓴다 (비워 두면 없음)
        $product->product_code = trim((string) ($data['product_code'] ?? '')) ?: null;
        $product->brand = $data['brand'] ?? null;
        // 카테고리는 syncCategories()에서 다중 연결과 함께 대표 분류를 정한다
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
    /**
     * 상품 카테고리 저장 — 여러 개를 연결하고 첫 번째를 대표 분류로 삼는다.
     * syncWithoutDetaching을 쓰면 예전 카테고리 연결이 남아 이동이 되지 않으므로 sync로 교체한다.
     */
    private function syncCategories(Product $product, array $data): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($data['category_ids'] ?? [])))));

        // 다중 선택이 없으면 예전처럼 단일 category_id를 쓴다
        if (! $ids && ! empty($data['category_id'])) {
            $ids = [(int) $data['category_id']];
        }

        $product->categories()->sync($ids);

        // 대표 분류: 선택한 것 중 가장 하위(소분류 우선)를 쓰면 목록·연관상품이 더 정확해진다
        $primary = null;
        if ($ids) {
            $primary = Category::whereIn('id', $ids)->get()
                ->sortByDesc(fn ($c) => $c->depth)->first()?->id;
        }
        if ((int) $product->category_id !== (int) $primary) {
            $product->update(['category_id' => $primary]);
        }
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
