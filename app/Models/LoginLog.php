<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'email', 'name', 'role', 'status', 'note', 'ip_address', 'user_agent', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRoleLabelAttribute(): string
    {
        return [
            'hq_admin' => '본사 관리자', 'seller' => '판매점', 'agent' => '협력사',
            'purchaser' => '구매 대행자', 'customer' => '고객',
        ][$this->role] ?? ($this->role ?: '-');
    }

    /** 브라우저/OS 간단 요약 */
    public function getBrowserAttribute(): string
    {
        $ua = (string) $this->user_agent;
        if ($ua === '') return '-';
        $browser = 'Unknown';
        foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $needle => $label) {
            if (stripos($ua, $needle) !== false) { $browser = $label; break; }
        }
        $os = 'Unknown';
        foreach (['Windows' => 'Windows', 'Mac' => 'macOS', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Linux' => 'Linux'] as $needle => $label) {
            if (stripos($ua, $needle) !== false) { $os = $label; break; }
        }
        return $browser.' · '.$os;
    }
}
