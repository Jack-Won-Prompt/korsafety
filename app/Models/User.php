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
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
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
