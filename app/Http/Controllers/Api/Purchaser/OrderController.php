<?php

namespace App\Http\Controllers\Api\Purchaser;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Buyer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private function purchaser(Request $request)
    {
        return $request->user()->purchaser;
    }

    public function index(Request $request)
    {
        $orders = Order::where('purchaser_id', $this->purchaser($request)->id)
            ->with('buyer')
            ->latest('id')
            ->paginate(15);

        $orders->getCollection()->transform(fn ($o) => $this->summary($o));

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->purchaser_id === $this->purchaser($request)->id, 403);
        $order->load('items', 'buyer');

        return response()->json(['data' => array_merge($this->summary($order), [
            'cashback_rate' => $order->cashback_rate,
            'cashback_amount' => $order->cashback_amount,
            'cashback_status_label' => $order->cashback_status_label,
            'items' => $order->items->map(fn ($it) => [
                'product_name' => $it->product_name,
                'price' => $it->price,
                'qty' => $it->qty,
                'line_total' => $it->line_total,
            ]),
        ])]);
    }

    public function searchProducts(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 1) {
            return ProductResource::collection(collect());
        }

        $items = Product::whereNotNull('main_image')->whereNotNull('price')->where('price', '>', 0)
            ->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('brand', 'like', "%$q%"))
            ->limit(20)->get();

        return ProductResource::collection($items);
    }

    public function store(Request $request)
    {
        $purchaser = $this->purchaser($request);
        $data = $request->validate([
            'buyer_id' => 'required|exists:buyers,id',
            'status' => 'required|in:pending,paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $buyer = Buyer::where('id', $data['buyer_id'])->where('purchaser_id', $purchaser->id)->firstOrFail();

        $order = DB::transaction(function () use ($data, $purchaser, $buyer) {
            $order = Order::create([
                'order_no' => 'PB'.now()->format('ymd').Str::upper(Str::random(4)),
                'purchaser_id' => $purchaser->id,
                'buyer_id' => $buyer->id,
                'customer_name' => $buyer->shop_name.' / '.$buyer->name,
                'customer_phone' => $buyer->phone,
                'total' => 0,
                'status' => $data['status'],
                'payment_status' => $data['status'] === 'paid' ? 'paid' : 'pending',
                'cashback_rate' => $purchaser->cashback_rate,
                'cashback_amount' => 0,
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $p = Product::find($row['product_id']);
                if (! $p) {
                    continue;
                }
                $price = $p->final_price ?: $p->price ?: 0;
                $qty = (int) $row['qty'];
                $line = $price * $qty;
                $total += $line;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $p->id,
                    'seller_id' => $p->seller_id,
                    'product_name' => $p->name,
                    'price' => $price,
                    'qty' => $qty,
                    'line_total' => $line,
                ]);
            }
            $order->update([
                'total' => $total,
                'cashback_amount' => (int) round($total * $purchaser->cashback_rate / 100),
            ]);

            return $order;
        });

        return response()->json(['data' => ['id' => $order->id, 'order_no' => $order->order_no]], 201);
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_unless($order->purchaser_id === $this->purchaser($request)->id, 403);
        $data = $request->validate(['status' => 'required|in:pending,paid,shipped,done,cancelled']);
        $order->update(['status' => $data['status']]);

        return response()->json(['message' => '주문 상태가 변경되었습니다.']);
    }

    private function summary(Order $o): array
    {
        return [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'buyer_name' => optional($o->buyer)->shop_name,
            'total' => $o->total,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'created_at' => optional($o->created_at)->toIso8601String(),
        ];
    }
}
