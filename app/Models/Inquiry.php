<?php

namespace App\Models;

use App\Events\InquiryActivity;
use App\Events\InquiryMessageSent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Inquiry extends Model
{
    protected $fillable = ['token', 'name', 'phone', 'status', 'ip_address', 'last_message_at', 'unread_admin', 'unread_customer'];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Inquiry $inquiry) {
            if (! $inquiry->token) {
                $inquiry->token = Str::random(40);
            }
        });
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InquiryMessage::class)->orderBy('id');
    }

    /** 메시지 저장 + 미확인 카운터 갱신 + 실시간 브로드캐스트 */
    public function postMessage(string $sender, string $body): InquiryMessage
    {
        $message = $this->messages()->create(['sender' => $sender, 'body' => $body]);

        $this->forceFill(['last_message_at' => now(), 'status' => 'open']);
        // 상대방 미확인 수 증가 (고객 발신 → 관리자 미확인 / 관리자 발신 → 고객 미확인)
        $this->{$sender === 'customer' ? 'unread_admin' : 'unread_customer'}++;
        $this->save();

        $message->setRelation('inquiry', $this);
        // 브로드캐스트 실패(예: Pusher 장애)가 메시지 저장을 막지 않도록 방어
        // — 실시간이 끊겨도 폴링 폴백으로 전달된다.
        try {
            broadcast(new InquiryMessageSent($message));
            InquiryActivity::dispatch($this->id, 'message');
        } catch (\Throwable $e) {
            report($e);
        }

        return $message;
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
