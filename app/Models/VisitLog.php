<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * 웹 방문 이력 — 고객이 어떤 검색어로 들어와 어떤 상품을 봤는지 남긴다.
 * 기록 실패가 쇼핑몰 화면을 막지 않도록 모든 쓰기는 예외를 삼킨다.
 */
class VisitLog extends Model
{
    public const UPDATED_AT = null;   // created_at만 사용

    public const TYPES = ['search' => '검색', 'product' => '상품 진입'];

    protected $fillable = [
        'type', 'keyword', 'product_id', 'product_name', 'result_count',
        'user_id', 'session_id', 'ip_address', 'user_agent', 'referer',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /** 검색 기록 */
    public static function recordSearch(Request $request, string $keyword, int $resultCount): void
    {
        // 깨진 인코딩으로 들어온 검색어도 기록이 실패하지 않도록 UTF-8로 정규화
        $keyword = trim(mb_convert_encoding($keyword, 'UTF-8', 'UTF-8'));
        if ($keyword === '') {
            return;
        }
        self::write($request, ['type' => 'search', 'keyword' => mb_substr($keyword, 0, 200), 'result_count' => $resultCount]);
    }

    /** 상품 상세 진입 기록 */
    public static function recordProduct(Request $request, Product $product): void
    {
        self::write($request, [
            'type' => 'product',
            'product_id' => $product->id,
            'product_name' => mb_substr((string) $product->name, 0, 200),
        ]);
    }

    private static function write(Request $request, array $data): void
    {
        try {
            if (self::skip($request)) {
                return;
            }
            self::create($data + [
                'user_id' => Auth::id(),
                'session_id' => $request->hasSession() ? substr($request->session()->getId(), 0, 64) : null,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 512),
                'referer' => mb_substr((string) $request->headers->get('referer'), 0, 512) ?: null,
            ]);
        } catch (\Throwable $e) {
            // 로그 기록 실패로 쇼핑몰이 죽지 않도록 무시
        }
    }

    /** 봇 · 관리 계정의 열람은 통계에서 제외 */
    private static function skip(Request $request): bool
    {
        $user = Auth::user();
        if ($user && ! $user->isCustomer()) {
            return true;   // 본사·판매점·협력사 등 관리 계정의 미리보기
        }

        $ua = strtolower((string) $request->userAgent());
        if ($ua === '') {
            return true;
        }
        foreach (['bot', 'crawler', 'spider', 'slurp', 'headlesschrome', 'python-requests', 'curl/', 'wget', 'facebookexternalhit'] as $needle) {
            if (str_contains($ua, $needle)) {
                return true;
            }
        }

        return false;
    }
}
