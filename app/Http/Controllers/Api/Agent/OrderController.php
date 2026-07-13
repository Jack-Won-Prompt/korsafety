<?php

namespace App\Http\Controllers\Api\Agent;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private function agent(Request $request)
    {
        return $request->user()->agent;
    }

    public function index(Request $request)
    {
        $orders = Order::where('agent_id', $this->agent($request)->id)
            ->with('client')
            ->latest('id')
            ->paginate(15);

        $orders->getCollection()->transform(fn ($o) => $this->summary($o));

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->agent_id === $this->agent($request)->id, 403);
        $order->load('items', 'client');

        return response()->json(['data' => array_merge($this->summary($order), [
            'commission_rate' => $order->commission_rate,
            'commission_amount' => $order->commission_amount,
            'commission_status_label' => $order->commission_status_label,
            'items' => $order->items->map(fn ($it) => [
                'product_name' => $it->product_name,
                'price' => $it->price,
                'qty' => $it->qty,
                'line_total' => $it->line_total,
            ]),
        ])]);
    }

    /** 상품 검색 (주문 등록용) */
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
        $agent = $this->agent($request);
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'status' => 'required|in:pending,paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $client = Client::where('id', $data['client_id'])->where('agent_id', $agent->id)->firstOrFail();

        $order = DB::transaction(function () use ($data, $agent, $client) {
            $order = Order::create([
                'order_no' => 'AG'.now()->format('ymd').Str::upper(Str::random(4)),
                'agent_id' => $agent->id,
                'client_id' => $client->id,
                'customer_name' => $client->name,
                'customer_phone' => $client->phone,
                'total' => 0,
                'status' => $data['status'],
                'payment_status' => $data['status'] === 'paid' ? 'paid' : 'pending',
                'commission_rate' => $agent->commission_rate,
                'commission_amount' => 0,
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
                'commission_amount' => (int) round($total * $agent->commission_rate / 100),
            ]);

            return $order;
        });

        return response()->json(['data' => ['id' => $order->id, 'order_no' => $order->order_no]], 201);
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_unless($order->agent_id === $this->agent($request)->id, 403);
        $data = $request->validate(['status' => 'required|in:pending,paid,shipped,done,cancelled']);
        $order->update(['status' => $data['status']]);

        return response()->json(['message' => '주문 상태가 변경되었습니다.']);
    }

    private function summary(Order $o): array
    {
        return [
            'id' => $o->id,
            'order_no' => $o->order_no,
            'client_name' => optional($o->client)->name,
            'total' => $o->total,
            'status' => $o->status,
            'status_label' => $o->status_label,
            'created_at' => optional($o->created_at)->toIso8601String(),
        ];
    }
}
