<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 협력사 회원(할인가 거래처)의 사업자 정보와 승인 상태 */
class PartnerProfile extends Model
{
    public const STATUSES = [
        'pending' => '승인 대기',
        'approved' => '승인 완료',
        'rejected' => '반려',
    ];

    protected $fillable = [
        'user_id', 'company_name', 'company_phone', 'company_fax', 'owner_name',
        'business_address', 'license_path', 'status', 'approved_at', 'approved_by', 'reject_reason',
    ];

    protected $casts = ['approved_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'approved' => 'ok',
            'rejected' => 'off',
            default => 'warn',
        };
    }
}
