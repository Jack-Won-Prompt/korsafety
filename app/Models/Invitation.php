<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** 관리자가 보낸 회원 가입 초대 */
class Invitation extends Model
{
    public const ROLES = ['customer' => '일반 회원', 'partner' => '협력사 회원'];

    public const STATUSES = [
        'sent' => '발송됨',
        'accepted' => '가입 완료',
        'cancelled' => '취소됨',
        'expired' => '기간 만료',
    ];

    /** 초대 링크 유효 기간 (일) */
    public const VALID_DAYS = 14;

    protected $fillable = [
        'email', 'name', 'company_name', 'role', 'token', 'status',
        'invited_by', 'user_id', 'sent_at', 'accepted_at', 'expires_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function newToken(): string
    {
        return Str::lower(Str::random(64));
    }

    public function isUsable(): bool
    {
        return $this->status === 'sent' && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    public function getStatusLabelAttribute(): string
    {
        // 발송 상태라도 기간이 지났으면 만료로 보여준다
        if ($this->status === 'sent' && $this->expires_at && $this->expires_at->isPast()) {
            return self::STATUSES['expired'];
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        if ($this->status === 'accepted') return 'ok';
        if ($this->status === 'cancelled') return 'off';
        if ($this->status === 'sent' && (! $this->expires_at || $this->expires_at->isFuture())) return 'warn';

        return 'off';
    }
}
