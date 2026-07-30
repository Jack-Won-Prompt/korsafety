<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxInvoice extends Model
{
    protected $fillable = [
        'order_id', 'mgt_key', 'invoice_kind',
        'supply_amount', 'tax_amount', 'total_amount',
        'receiver_corp_num', 'receiver_corp_name', 'receiver_ceo', 'receiver_email',
        'status', 'popbill_state', 'nts_confirm_num', 'error_message',
        'issued_at', 'cancelled_at', 'issued_by',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getKindLabelAttribute(): string
    {
        return ['tax' => '과세', 'plain' => '면세'][$this->invoice_kind] ?? $this->invoice_kind;
    }

    public function getStatusLabelAttribute(): string
    {
        return [
            'issued' => '발행완료', 'simulated' => '발행(시뮬레이트)',
            'cancelled' => '취소', 'failed' => '실패',
        ][$this->status] ?? $this->status;
    }

    /** 발행 성공(실제+시뮬레이트) 상태인가 */
    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['issued', 'simulated'], true);
    }
}
