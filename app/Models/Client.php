<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $fillable = [
        'agent_id', 'name', 'type', 'contact_name', 'phone', 'business_no', 'address', 'memo',
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return ['company' => '기업', 'hospital' => '병원', 'etc' => '기타'][$this->type] ?? $this->type;
    }
}
