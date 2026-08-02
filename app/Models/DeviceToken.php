<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 앱 푸시 발송용 FCM 등록 토큰 (기기 1대 = 1행) */
class DeviceToken extends Model
{
    protected $fillable = ['user_id', 'token', 'platform', 'last_used_at'];

    protected $casts = ['last_used_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
