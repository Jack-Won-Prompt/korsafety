<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchaser extends Model
{
    protected $fillable = [
        'name', 'slug', 'status', 'cashback_rate',
        'owner_name', 'business_no', 'phone', 'email', 'memo',
    ];

    protected $casts = ['cashback_rate' => 'decimal:2'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function buyers(): HasMany
    {
        return $this->hasMany(Buyer::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function getStatusLabelAttribute(): string
    {
        return ['pending' => '승인대기', 'approved' => '승인완료', 'suspended' => '정지'][$this->status] ?? $this->status;
    }
}
