<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * 관리자 알림용 핑. 고정 채널(inquiries.admin)이므로 개인정보(이름·전화·본문)는
 * 싣지 않고 inquiry_id/종류만 전달한다. 관리자 화면은 이 핑을 받으면
 * 인증된 API로 상세를 다시 조회한다.
 */
class InquiryActivity implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /** @param string $type 'new'(신규 문의) | 'message'(새 메시지) */
    public function __construct(public int $inquiryId, public string $type = 'message')
    {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('inquiries.admin');
    }

    public function broadcastAs(): string
    {
        return 'inquiry.activity';
    }

    public function broadcastWith(): array
    {
        return [
            'inquiry_id' => $this->inquiryId,
            'type' => $this->type,
        ];
    }
}
