<?php

namespace App\Http\Controllers\Purchaser;

use App\Http\Controllers\Controller;
use App\Models\Buyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerController extends Controller
{
    private function purchaserId(): int
    {
        return Auth::user()->purchaser_id;
    }

    public function index()
    {
        $buyers = Buyer::where('purchaser_id', $this->purchaserId())
            ->withCount('orders')->latest('id')->paginate(15);
        return view('purchaser.buyers.index', compact('buyers'));
    }

    public function create()
    {
        return view('purchaser.buyers.form', ['buyer' => new Buyer()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['purchaser_id'] = $this->purchaserId();
        Buyer::create($data);
        return redirect()->route('purchaser.buyers.index')->with('status', '구매자가 등록되었습니다.');
    }

    public function edit(Buyer $buyer)
    {
        $this->authorizeOwner($buyer);
        return view('purchaser.buyers.form', compact('buyer'));
    }

    public function update(Request $request, Buyer $buyer)
    {
        $this->authorizeOwner($buyer);
        $buyer->update($this->validated($request));
        return redirect()->route('purchaser.buyers.index')->with('status', '구매자가 수정되었습니다.');
    }

    public function destroy(Buyer $buyer)
    {
        $this->authorizeOwner($buyer);
        $buyer->delete();
        return back()->with('status', '구매자가 삭제되었습니다.');
    }

    private function authorizeOwner(Buyer $buyer): void
    {
        abort_unless($buyer->purchaser_id === $this->purchaserId(), 403);
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
        ], [], ['shop_name' => '소매처명', 'name' => '구매자 이름']);
    }
}
