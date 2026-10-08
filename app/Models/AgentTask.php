<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Agent 가 처리할 일감 한 줄 (운영 오류 또는 SR) */
class AgentTask extends Model
{
    public const TYPES = ['error' => '운영 오류', 'sr' => 'SR 요청'];

    public const STATUSES = [
        'pending' => '대기',
        'working' => '처리중',
        'done' => '처리완료',
        'failed' => '실패',
        'skipped' => '사람 확인 필요',
    ];

    protected $fillable = [
        'type', 'ref_id', 'dedupe_key', 'payload', 'status', 'attempts',
        'analysis', 'result', 'commit_hash', 'tokens', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'done' => 'ok',
            'working' => 'warn',
            'failed' => 'off',
            'skipped' => 'hq',
            default => 'warn',
        };
    }

    /** 분석 결과를 배열로 */
    public function analysisArray(): array
    {
        return json_decode((string) $this->analysis, true) ?: [];
    }
}
