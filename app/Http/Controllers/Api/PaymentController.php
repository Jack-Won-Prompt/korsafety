<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    /**
     * 토스페이먼츠 결제 승인.
     * 앱(위젯/결제창)이 성공하면 { paymentKey, orderId, amount }를 전달하고,
     * 서버는 시크릿 키로 최종 승인(confirm)을 호출한다.
     */
    public function confirm(Request $request)
    {
        $data = $request->validate([
            'payment_key' => 'required|string',
            'order_id' => 'required|string',   // = order_no
            'amount' => 'required|integer|min:1',
        ]);

        $order = Order::where('order_no', $data['order_id'])
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        // 금액 위변조 방지: 서버가 확정한 total과 일치해야 함
        if ((int) $data['amount'] !== (int) $order->total) {
            return response()->json(['message' => '결제 금액이 주문 금액과 일치하지 않습니다.'], 422);
        }

        if ($order->payment_status === 'paid') {
            $order->load('items.product');

            return response()->json(['order' => new OrderResource($order), 'message' => '이미 결제된 주문입니다.']);
        }

        $secret = config('services.toss.secret_key');
        $response = Http::withBasicAuth($secret, '')
            ->acceptJson()
            ->post(config('services.toss.base_url').'/v1/payments/confirm', [
                'paymentKey' => $data['payment_key'],
                'orderId' => $data['order_id'],
                'amount' => $data['amount'],
            ]);

        if (! $response->successful()) {
            $body = $response->json();
            $order->update(['payment_status' => 'failed']);

            return response()->json([
                'message' => $body['message'] ?? '결제 승인에 실패했습니다.',
                'code' => $body['code'] ?? null,
            ], 422);
        }

        $body = $response->json();

        $order->update([
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_method' => $body['method'] ?? null,
            'payment_key' => $data['payment_key'],
            'paid_at' => now(),
        ]);

        $order->load('items.product');

        return response()->json([
            'order' => new OrderResource($order),
            'message' => '결제가 완료되었습니다.',
        ]);
    }

    /** 결제 실패/취소 콜백 기록 */
    public function fail(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|string',
            'code' => 'nullable|string',
            'message' => 'nullable|string',
        ]);

        $order = Order::where('order_no', $data['order_id'])
            ->where('user_id', $request->user()->id)
            ->first();

        if ($order && $order->payment_status !== 'paid') {
            $order->update(['payment_status' => 'failed']);
        }

        return response()->json(['message' => $data['message'] ?? '결제가 취소되었습니다.']);
    }
}
