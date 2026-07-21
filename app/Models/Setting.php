<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /** Simple per-request cache of all settings. */
    protected static ?array $cache = null;

    /** Default values for known settings. */
    public const DEFAULTS = [
        'home_show_categories' => '0',    // 메인 카테고리 영역 표시 (기본: 숨김)
        'price_display_mode'   => 'ask',  // 가격 표시 방식: 'ask'=가격 문의 / 'price'=제품 가격 노출
    ];

    public static function get(string $key, $default = null)
    {
        if (static::$cache === null) {
            try {
                static::$cache = static::query()->pluck('value', 'key')->all();
            } catch (\Throwable $e) {
                static::$cache = [];
            }
        }
        return static::$cache[$key] ?? self::DEFAULTS[$key] ?? $default;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        if (static::$cache !== null) {
            static::$cache[$key] = $value;
        }
    }

    public static function bool(string $key): bool
    {
        return (string) static::get($key) === '1';
    }
}
