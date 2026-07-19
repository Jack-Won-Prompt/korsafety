<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'agent_id', 'client_id', 'purchaser_id', 'buyer_id', 'user_id',
        'customer_name', 'customer_phone',
        'receiver_name', 'postcode', 'address1', 'address2', 'delivery_memo',
        'total', 'status', 'payment_status', 'payment_method', 'payment_key', 'paid_at',
        'commission_rate', 'commission_amount', 'commission_paid_at',
        'cashback_rate', 'cashback_amount', 'cashback_paid_at',
    ];

    protected $casts = [
        'commission_paid_at' => 'datetime',
        'cashback_paid_at' => 'datetime',
        'paid_at' => 'datetime',
        'commission_rate' => 'decimal:2',
        'cashback_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'pending' => '결제대기', 'paid' => '결제완료', 'shipped' => '배송중',
            'done' => '배송완료', 'cancelled' => '취소',
        ][$this->status] ?? $this->status;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function purchaser(): BelongsTo
    {
        return $this->belongsTo(Purchaser::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    /** 커미션이 적립(지급대상)되었는가 = 결제완료 이상 */
    public function getCommissionAccruedAttribute(): bool
    {
        return $this->agent_id && in_array($this->status, ['paid', 'shipped', 'done'], true);
    }

    public function getCommissionStatusLabelAttribute(): string
    {
        if (! $this->agent_id) return '-';
        if ($this->commission_paid_at) return '지급완료';
        if ($this->commission_accrued) return '지급대기';
        return '적립대기';
    }

    /** 캐쉬백이 적립(지급대상)되었는가 = 결제완료 이상 */
    public function getCashbackAccruedAttribute(): bool
    {
        return $this->purchaser_id && in_array($this->status, ['paid', 'shipped', 'done'], true);
    }

    public function getCashbackStatusLabelAttribute(): string
    {
        if (! $this->purchaser_id) return '-';
        if ($this->cashback_paid_at) return '지급완료';
        if ($this->cashback_accrued) return '지급대기';
        return '적립대기';
    }
}
