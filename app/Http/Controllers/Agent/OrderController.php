<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    private function agent()
    {
        return Auth::user()->agent;
    }

    public function index()
    {
        $orders = Order::where('agent_id', $this->agent()->id)
            ->with('client')->latest('id')->paginate(15);
        return view('agent.orders.index', compact('orders'));
    }

    public function create()
    {
        $clients = Client::where('agent_id', $this->agent()->id)->orderBy('name')->get();
        return view('agent.orders.create', compact('clients'));
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
        $agent = $this->agent();
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'status' => 'required|in:pending,paid',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ], [], ['client_id' => '거래처', 'items' => '주문 상품']);

        $client = Client::where('id', $data['client_id'])->where('agent_id', $agent->id)->firstOrFail();

        $result = DB::transaction(function () use ($data, $agent, $client) {
            $order = Order::create([
                'order_no' => 'AG'.now()->format('ymd').Str::upper(Str::random(4)),
                'agent_id' => $agent->id, 'client_id' => $client->id,
                'customer_name' => $client->name, 'customer_phone' => $client->phone,
                'total' => 0, 'status' => $data['status'],
                'commission_rate' => $agent->commission_rate, 'commission_amount' => 0,
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
                'commission_amount' => (int) round($total * $agent->commission_rate / 100),
            ]);
            return $order;
        });

        return redirect()->route('agent.orders.show', $result)->with('status', '주문이 등록되었습니다.');
    }

    public function show(Order $order)
    {
        $this->authorizeOwner($order);
        $order->load('items', 'client');
        return view('agent.orders.show', compact('order'));
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
        abort_unless($order->agent_id === $this->agent()->id, 403);
    }
}
