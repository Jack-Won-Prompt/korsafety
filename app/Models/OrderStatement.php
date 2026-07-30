<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatement extends Model
{
    protected $fillable = [
        'order_id', 'seq', 'file_name', 'statement_date', 'total_amount',
        'action', 'sent_to', 'issued_by', 'error_message',
    ];

    protected $casts = [
        'statement_date' => 'date',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function getActionLabelAttribute(): string
    {
        return ['download' => '다운로드', 'email' => '이메일 발송'][$this->action] ?? $this->action;
    }
}
