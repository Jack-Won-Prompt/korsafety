<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'seller_id', 'agent_id', 'purchaser_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'seller_id', 'agent_id', 'purchaser_id', 'suspended_at',
        'phone', 'postcode', 'address1', 'address2',
        'approval_status', 'approved_at', 'approved_by',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function partnerProfile()
    {
        return $this->hasOne(PartnerProfile::class);
    }

    /** 협력사 회원인가 (가입만 한 상태 포함) */
    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }

    /** 본사 승인까지 끝나 협력사 할인가를 받을 수 있는가 */
    public function isApprovedPartner(): bool
    {
        return $this->isPartner() && optional($this->partnerProfile)->status === 'approved';
    }

    /** 이용 정지된 계정 — 웹·앱 어디서도 로그인할 수 없다 */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    /**
     * 자동 처리 Agent 담당자인가.
     * Agent 는 코드를 고쳐 배포까지 하므로, 본사 관리자 중에서도 정해 둔 담당자에게만 보인다.
     */
    public function isAgentOperator(): bool
    {
        $allowed = mb_strtolower(trim((string) config('services.agent.operator_email')));

        return $this->isHqAdmin() && $allowed !== '' && mb_strtolower(trim((string) $this->email)) === $allowed;
    }

    /** 가입 승인을 기다리는 중인가 (초대로 가입한 회원) */
    public function awaitingApproval(): bool
    {
        return $this->approval_status === 'pending';
    }

    /** 가입이 반려된 계정인가 */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /** 로그인할 수 없는 이유 — 없으면 null */
    public function loginBlockReason(): ?string
    {
        if ($this->isSuspended()) {
            return '이용이 정지된 계정입니다. 고객센터로 문의해 주세요.';
        }
        if ($this->awaitingApproval()) {
            return '가입 승인을 기다리는 중입니다. 승인 후 이용하실 수 있습니다.';
        }
        if ($this->isRejected()) {
            return '가입이 승인되지 않은 계정입니다. 고객센터로 문의해 주세요.';
        }

        return null;
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(Purchaser::class);
    }

    public function isHqAdmin(): bool
    {
        return $this->role === 'hq_admin';
    }

    public function isSeller(): bool
    {
        return $this->role === 'seller';
    }

    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    public function isPurchaser(): bool
    {
        return $this->role === 'purchaser';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer' || $this->role === 'partner' || $this->role === null;
    }

    /** 한국어 비밀번호 재설정 메일 */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordKo($token));
    }

    /** Store this user manages (HQ admin and sellers both map to a seller row). */
    public function managedSeller(): ?Seller
    {
        return $this->seller;
    }
}
