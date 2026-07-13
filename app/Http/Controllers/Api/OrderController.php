<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /** 내 주문 목록 */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->latest('id')
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    /** 주문 상세 */
    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items.product');

        return new OrderResource($order);
    }

    /**
     * 주문 생성 (결제 전 상태). 서버가 가격을 재계산하여 total을 확정한다.
     * 반환된 order_no / amount 로 앱이 토스 결제를 요청한다.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1|max:999',
            'receiver_name' => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'postcode' => 'nullable|string|max:10',
            'address1' => 'required|string|max:255',
            'address2' => 'nullable|string|max:255',
            'delivery_memo' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        $order = DB::transaction(function () use ($data, $user) {
            $order = Order::create([
                'order_no' => 'APP'.now()->format('ymd').Str::upper(Str::random(5)),
                'user_id' => $user->id,
                'customer_name' => $user->name,
                'customer_phone' => $data['phone'],
                'receiver_name' => $data['receiver_name'],
                'postcode' => $data['postcode'] ?? null,
                'address1' => $data['address1'],
                'address2' => $data['address2'] ?? null,
                'delivery_memo' => $data['delivery_memo'] ?? null,
                'total' => 0,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            $total = 0;
            foreach ($data['items'] as $row) {
                $p = Product::find($row['product_id']);
                if (! $p || $p->is_soldout) {
                    abort(422, "판매 종료된 상품이 포함되어 있습니다: ".($p->name ?? ''));
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

            $order->update(['total' => $total]);

            return $order;
        });

        $order->load('items.product');

        return response()->json([
            'order' => new OrderResource($order),
            'payment' => [
                'order_no' => $order->order_no,
                'amount' => $order->total,
                'order_name' => $this->orderName($order),
                'customer_name' => $user->name,
                'client_key' => config('services.toss.client_key'),
            ],
        ], 201);
    }

    private function orderName(Order $order): string
    {
        $first = $order->items->first();
        $count = $order->items->count();
        if (! $first) {
            return '주문';
        }

        return $count > 1
            ? "{$first->product_name} 외 ".($count - 1)."건"
            : $first->product_name;
    }
}
