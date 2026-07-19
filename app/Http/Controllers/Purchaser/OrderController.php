<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private function purchaser()
    {
        return Auth::user()->purchaser;
    }

    public function index()
    {
        $orders = Order::where('purchaser_id', $this->purchaser()->id)
            ->with('buyer')->latest('id')->paginate(15);
        return view('purchaser.orders.index', compact('orders'));
    }

    public function create()
    {
        $buyers = Buyer::where('purchaser_id', $this->purchaser()->id)->orderBy('shop_name')->get();
        return view('purchaser.orders.create', compact('buyers'));
    }

    /** 상품 검색 (주문 등록용 AJAX) */
    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 1) return response()->json([]);
        $items = Product::query()
            ->whereNotNull('main_image')->whereNotNull('price')->where('price', '>', 0)
            ->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('brand', 'like', "%$q%"))
            ->limit(15)->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand,
                'price' => $p->final_price ?: $p->price,
                'image' => $p->main_image ? asset($p->main_image) : null,
            ]);
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $purchaser = $this->purchaser();
        $data = $request->validate([
            'buyer_id' => 'required|exists:buyers,id',
            'status' => 'required|in:pending,paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ], [], ['buyer_id' => '구매자', 'items' => '주문 상품']);

        $buyer = Buyer::where('id', $data['buyer_id'])->where('purchaser_id', $purchaser->id)->firstOrFail();

        $result = DB::transaction(function () use ($data, $purchaser, $buyer) {
            $order = Order::create([
                'order_no' => 'PB'.now()->format('ymd').Str::upper(Str::random(4)),
                'purchaser_id' => $purchaser->id, 'buyer_id' => $buyer->id,
                'customer_name' => $buyer->shop_name.' / '.$buyer->name,
                'customer_phone' => $buyer->phone,
                'total' => 0, 'status' => $data['status'],
                'cashback_rate' => $purchaser->cashback_rate, 'cashback_amount' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $p = Product::find($row['product_id']);
                if (! $p) continue;
                $price = $p->final_price ?: $p->price ?: 0;
                $qty = (int) $row['qty'];
                $line = $price * $qty;
                $total += $line;
                OrderItem::create([
                    'order_id' => $order->id, 'product_id' => $p->id, 'seller_id' => $p->seller_id,
                    'product_name' => $p->name, 'price' => $price, 'qty' => $qty, 'line_total' => $line,
                ]);
            }
            $order->update([
                'total' => $total,
                'cashback_amount' => (int) round($total * $purchaser->cashback_rate / 100),
            ]);
            return $order;
        });

        return redirect()->route('purchaser.orders.show', $result)->with('status', '주문이 등록되었습니다.');
    }

    public function show(Order $order)
    {
        $this->authorizeOwner($order);
        $order->load('items', 'buyer');
        return view('purchaser.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $this->authorizeOwner($order);
        $request->validate(['status' => 'required|in:pending,paid,shipped,done,cancelled']);
        $order->update(['status' => $request->status]);
        return back()->with('status', '주문 상태가 변경되었습니다.');
    }

    private function authorizeOwner(Order $order): void
    {
        abort_unless($order->purchaser_id === $this->purchaser()->id, 403);
    }
}
