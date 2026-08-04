<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function cart(): array
    {
        return session()->get('cart', []);
    }

    /**
     * 장바구니 키 — 옵션 없는 상품은 "12", 옵션이 있으면 "12:3,7" 형태.
     * 기존 세션(숫자 키)과 호환된다.
     */
    private function key(int $productId, array $optionIds = []): string
    {
        sort($optionIds);

        return $optionIds ? $productId.':'.implode(',', $optionIds) : (string) $productId;
    }

    /** 키에서 상품 id와 옵션 id들을 되돌린다 */
    private function parse(string $key): array
    {
        [$productId, $optionPart] = array_pad(explode(':', $key, 2), 2, '');
        $optionIds = $optionPart === '' ? [] : array_filter(array_map('intval', explode(',', $optionPart)));

        return [(int) $productId, $optionIds];
    }

    public function index()
    {
        $cart = $this->cart();

        $productIds = [];
        $optionIds = [];
        foreach (array_keys($cart) as $key) {
            [$pid, $opts] = $this->parse((string) $key);
            $productIds[] = $pid;
            $optionIds = array_merge($optionIds, $opts);
        }

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $options = $optionIds ? ProductOption::whereIn('id', $optionIds)->get()->keyBy('id') : collect();

        $items = [];
        $subtotal = 0;
        foreach ($cart as $key => $qty) {
            [$pid, $opts] = $this->parse((string) $key);
            if (! isset($products[$pid])) continue;
            $p = $products[$pid];

            $extra = 0;
            $labels = [];
            foreach ($opts as $oid) {
                if (! isset($options[$oid])) continue;
                $extra += $options[$oid]->extra_price;
                $labels[] = trim(($options[$oid]->group_name ? $options[$oid]->group_name.' ' : '').$options[$oid]->name);
            }

            $unit = max(0, (int) $p->final_price + $extra);
            $line = $unit * $qty;
            $subtotal += $line;
            $items[] = [
                'key' => (string) $key,
                'product' => $p,
                'qty' => $qty,
                'unit' => $unit,
                'line' => $line,
                'options' => $labels,
            ];
        }

        return view('shop.cart', compact('items', 'subtotal'));
    }

    public function add(Request $request, Product $product)
    {
        $qty = max(1, (int) $request->input('qty', 1));

        // 옵션이 있는 상품은 옵션을 골라야 담을 수 있다
        $selected = array_filter(array_map('intval', (array) $request->input('options', [])));
        $valid = $product->activeOptions()->whereIn('id', $selected)->pluck('id')->all();
        if ($product->activeOptions()->exists() && count($valid) === 0) {
            $message = '옵션을 선택해 주세요.';

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->with('error', $message);
        }

        $key = $this->key($product->id, $valid);
        $cart = $this->cart();
        $cart[$key] = ($cart[$key] ?? 0) + $qty;
        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json(['count' => array_sum($cart), 'message' => '장바구니에 담았습니다.']);
        }

        return redirect()->route('cart.index')->with('status', '장바구니에 담았습니다.');
    }

    public function update(Request $request, string $key)
    {
        $qty = (int) $request->input('qty', 1);
        $cart = $this->cart();
        if ($qty <= 0) {
            unset($cart[$key]);
        } else {
            $cart[$key] = $qty;
        }
        session()->put('cart', $cart);

        return redirect()->route('cart.index');
    }

    public function remove(string $key)
    {
        $cart = $this->cart();
        unset($cart[$key]);
        session()->put('cart', $cart);

        return redirect()->route('cart.index');
    }
}
