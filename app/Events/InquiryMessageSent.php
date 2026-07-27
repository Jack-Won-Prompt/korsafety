<?php

namespace App\Events;

use App\Models\InquiryMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 특정 대화방(추측 불가한 토큰 채널)으로 새 메시지를 브로드캐스트.
 * 고객 위젯과 관리자 대화 화면이 이 채널을 구독한다.
 */
class InquiryMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public InquiryMessage $message)
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('inquiry.'.$this->message->inquiry->token);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'inquiry_id' => $this->message->inquiry_id,
            'sender' => $this->message->sender,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
}
