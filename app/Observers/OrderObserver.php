<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
use App\Services\Push\FcmService;
use Illuminate\Support\Facades\Log;

class OrderObserver
{
    /** 상태 코드별 알림 문구 */
    private const MESSAGES = [
        'pending' => '결제를 기다리고 있습니다.',
        'paid' => '결제가 완료되었습니다.',
        'shipped' => '상품이 발송되었습니다.',
        'done' => '배송이 완료되었습니다.',
        'cancelled' => '주문이 취소되었습니다.',
    ];

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status')) {
            return;
        }

        $status = $order->status;
        $title = '주문 '.($order->status_label);
        $body = ($order->order_no ? $order->order_no.' · ' : '').(self::MESSAGES[$status] ?? '주문 상태가 변경되었습니다.');
        $userIds = $this->recipients($order);

        if (! $userIds) {
            return;
        }

        // 응답을 지연시키지 않도록 응답 전송 후 발송 (큐 워커 불필요)
        dispatch(function () use ($userIds, $title, $body, $order, $status) {
            try {
                app(FcmService::class)->sendToUsers($userIds, $title, $body, [
                    'type' => 'order_status',
                    'order_id' => (string) $order->id,
                    'order_no' => (string) $order->order_no,
                    'status' => (string) $status,
                ]);
            } catch (\Throwable $e) {
                Log::warning('주문 상태 푸시 발송 오류: '.$e->getMessage());
            }
        })->afterResponse();
    }

    /**
     * 알림 대상: 주문한 고객 + 해당 주문을 등록한 협력사/구매처 담당 사용자.
     *
     * @return array<int>
     */
    private function recipients(Order $order): array
    {
        $ids = [];

        if ($order->user_id) {
            $ids[] = (int) $order->user_id;
        }

        if ($order->agent_id) {
            $ids = array_merge($ids, User::where('agent_id', $order->agent_id)->pluck('id')->all());
        }

        if ($order->purchaser_id) {
            $ids = array_merge($ids, User::where('purchaser_id', $order->purchaser_id)->pluck('id')->all());
        }

        return array_values(array_unique($ids));
    }
}
