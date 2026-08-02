<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ServiceRequest extends Model
{
    /** 처리 상태 — 종료(closed)까지 오면 더 이상 답글을 받지 않는다 */
    public const STATUSES = [
        'open' => '접수', 'in_progress' => '처리중', 'resolved' => '처리완료', 'closed' => '종료',
    ];

    public const PRIORITIES = ['low' => '낮음', 'normal' => '보통', 'high' => '높음', 'urgent' => '긴급'];

    public const CATEGORIES = [
        'system' => '시스템·오류', 'product' => '상품', 'order' => '주문·배송',
        'settlement' => '정산', 'account' => '계정·권한', 'etc' => '기타',
    ];

    /** 아직 손이 필요한 상태 (상단 SR 배지 기준) */
    public const OPEN_STATUSES = ['open', 'in_progress'];

    protected $fillable = [
        'sr_no', 'user_id', 'requester_role', 'title', 'category', 'priority',
        'status', 'content', 'assignee_id', 'reply_count', 'closed_at', 'resolved_notified_at',
    ];

    protected $casts = ['closed_at' => 'datetime', 'resolved_notified_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ServiceRequestReply::class)->oldest('id');
    }

    public static function nextNo(): string
    {
        return 'SR'.now()->format('ymd').Str::upper(Str::random(4));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /** 목록·상세에서 쓰는 배지 색상 클래스 */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'open' => 'warn',
            'in_progress' => 'hq',
            'resolved' => 'ok',
            default => 'off',
        };
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 'closed';
    }
}
