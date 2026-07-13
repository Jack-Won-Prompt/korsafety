<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Model
{
    protected $fillable = [
        'name', 'slug', 'is_hq', 'status', 'business_no',
        'owner_name', 'phone', 'email', 'commission_rate', 'memo',
    ];

    protected $casts = ['is_hq' => 'boolean', 'commission_rate' => 'decimal:2'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
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
