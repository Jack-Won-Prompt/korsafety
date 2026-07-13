<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function cart(): array
    {
        return session()->get('cart', []);
    }

    public function index()
    {
        $cart = $this->cart();
        $ids = array_keys($cart);
        $products = Product::whereIn('id', $ids)->get()->keyBy('id');

        $items = [];
        $subtotal = 0;
        foreach ($cart as $id => $qty) {
            if (! isset($products[$id])) continue;
            $p = $products[$id];
            $line = $p->final_price * $qty;
            $subtotal += $line;
            $items[] = ['product' => $p, 'qty' => $qty, 'line' => $line];
        }

        return view('shop.cart', compact('items', 'subtotal'));
    }

    public function add(Request $request, Product $product)
    {
        $qty = max(1, (int) $request->input('qty', 1));
        $cart = $this->cart();
        $cart[$product->id] = ($cart[$product->id] ?? 0) + $qty;
        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json(['count' => array_sum($cart), 'message' => '장바구니에 담았습니다.']);
        }
        return redirect()->route('cart.index')->with('status', '장바구니에 담았습니다.');
    }

    public function update(Request $request, Product $product)
    {
        $qty = (int) $request->input('qty', 1);
        $cart = $this->cart();
        if ($qty <= 0) {
            unset($cart[$product->id]);
        } else {
            $cart[$product->id] = $qty;
        }
        session()->put('cart', $cart);
        return redirect()->route('cart.index');
    }

    public function remove(Product $product)
    {
        $cart = $this->cart();
        unset($cart[$product->id]);
        session()->put('cart', $cart);
        return redirect()->route('cart.index');
    }
}
