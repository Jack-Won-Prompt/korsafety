<?php

namespace App\Http\Controllers\Api\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use Illuminate\Http\Request;

class BuyerController extends Controller
{
    private function purchaserId(Request $request): int
    {
        return $request->user()->purchaser->id;
    }

    public function index(Request $request)
    {
        $buyers = Buyer::where('purchaser_id', $this->purchaserId($request))
            ->withCount('orders')
            ->latest('id')
            ->get()
            ->map(fn ($b) => $this->payload($b));

        return response()->json(['data' => $buyers]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['purchaser_id'] = $this->purchaserId($request);
        $buyer = Buyer::create($data);

        return response()->json(['data' => $this->payload($buyer)], 201);
    }

    public function update(Request $request, Buyer $buyer)
    {
        abort_unless($buyer->purchaser_id === $this->purchaserId($request), 403);
        $buyer->update($this->validated($request));

        return response()->json(['data' => $this->payload($buyer)]);
    }

    public function destroy(Request $request, Buyer $buyer)
    {
        abort_unless($buyer->purchaser_id === $this->purchaserId($request), 403);
        $buyer->delete();

        return response()->json(['message' => '삭제되었습니다.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'shop_name' => 'required|string|max:100',
            'name' => 'required|string|max:50',
            'business_no' => 'nullable|string|max:30',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:200',
            'memo' => 'nullable|string|max:500',
        ]);
    }

    private function payload(Buyer $b): array
    {
        return [
            'id' => $b->id,
            'shop_name' => $b->shop_name,
            'name' => $b->name,
            'business_no' => $b->business_no,
            'phone' => $b->phone,
            'address' => $b->address,
            'memo' => $b->memo,
            'orders_count' => $b->orders_count ?? $b->orders()->count(),
        ];
    }
}
