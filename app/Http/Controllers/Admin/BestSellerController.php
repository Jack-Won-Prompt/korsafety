<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

/** 쇼핑몰 홈 '베스트 셀러' 진열 관리 (본사 전용) */
class BestSellerController extends Controller
{
    /** 홈에 노출할 최대 개수 */
    public const LIMIT = 8;

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $this->normalizeOrder();

        $bests = Product::where('is_best', true)
            ->orderBy('best_sort')->orderByDesc('id')->get();

        // 검색해서 추가할 후보 (이미 지정된 상품은 제외)
        $candidates = collect();
        if ($q !== '') {
            $candidates = Product::visible()
                ->where('is_best', false)
                ->where(fn ($w) => $w->where('name', 'like', "%$q%")
                    ->orWhere('brand', 'like', "%$q%")
                    ->orWhere('sku', 'like', "%$q%"))
                ->orderByDesc('id')->limit(20)->get();
        }

        return view('admin.bestsellers.index', [
            'bests' => $bests,
            'candidates' => $candidates,
            'q' => $q,
            'limit' => self::LIMIT,
        ]);
    }

    /**
     * 순번을 1부터 빈틈없이 다시 매긴다.
     * 상품 목록의 일괄 지정으로 들어온 상품은 best_sort가 0이라 위/아래 이동이 동작하지 않는다.
     */
    private function normalizeOrder(): void
    {
        $rows = Product::where('is_best', true)->orderBy('best_sort')->orderByDesc('id')->get(['id', 'best_sort']);
        $sort = 1;
        foreach ($rows as $row) {
            if ((int) $row->best_sort !== $sort) {
                Product::where('id', $row->id)->update(['best_sort' => $sort]);
            }
            $sort++;
        }
    }

    public function add(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|exists:products,id']);
        $product = Product::findOrFail($data['product_id']);
        $product->update([
            'is_best' => true,
            'best_sort' => (int) Product::where('is_best', true)->max('best_sort') + 1,
        ]);

        return back()->with('status', '‘'.$product->name.'’을(를) 베스트 셀러에 추가했습니다.');
    }

    public function remove(Product $product)
    {
        $product->update(['is_best' => false, 'best_sort' => 0]);

        return back()->with('status', '베스트 셀러에서 제외했습니다.');
    }

    /** 순서 저장 (입력한 숫자 순으로 재정렬) */
    public function reorder(Request $request)
    {
        $orders = (array) $request->input('order', []);
        asort($orders, SORT_NUMERIC);

        $sort = 1;
        foreach (array_keys($orders) as $id) {
            Product::where('id', (int) $id)->where('is_best', true)->update(['best_sort' => $sort++]);
        }

        return back()->with('status', '베스트 셀러 순서를 저장했습니다.');
    }

    /** 한 칸 위/아래로 이동 */
    public function move(Request $request, Product $product)
    {
        $dir = $request->input('dir') === 'up' ? 'up' : 'down';
        $neighbor = Product::where('is_best', true)
            ->when($dir === 'up',
                fn ($q) => $q->where('best_sort', '<', $product->best_sort)->orderByDesc('best_sort'),
                fn ($q) => $q->where('best_sort', '>', $product->best_sort)->orderBy('best_sort'))
            ->first();

        if ($neighbor) {
            $mine = $product->best_sort;
            $product->update(['best_sort' => $neighbor->best_sort]);
            $neighbor->update(['best_sort' => $mine]);
        }

        return back();
    }
}
