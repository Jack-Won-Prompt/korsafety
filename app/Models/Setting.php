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
        'maintenance_mode'     => '0',    // 유지보수 모드 (메인 본문을 안내 문구로 대체)
        'maintenance_message'  => '더 좋은 서비스를 위해서 준비중에 있습니다.',
        // 모바일 앱 연락처 배너 (홈 상단·상품 상세, 탭하면 전화 연결)
        'contact_banner_enabled' => '1',
        'contact_banner_text'    => '안전제품 관련 제작 및 제품문의',
        'contact_banner_phone'   => '02-2273-9533',
        // 쇼핑몰 회원가입 영역 노출 (끄면 가입 링크가 사라지고 가입 페이지도 막힘)
        'signup_enabled'         => '1',
        // 검색엔진 사이트 소유확인 코드 (네이버 서치어드바이저 · 구글 서치콘솔)
        'seo_naver_verify'       => '',
        'seo_google_verify'      => '',
        // SR 접수 알림 수신 주소 (쉼표로 여러 명 지정 가능)
        'sr_notify_email'        => 'jack@withworks.co.kr',

        // ── 자동 처리 Agent (운영 오류 자동 수정 · SR 자동 답변)
        // 사고가 나면 서버를 만지지 않고 이 화면에서 바로 끌 수 있도록 설정으로 둔다.
        'agent_enabled'          => '0',   // 전체 스위치 (꺼져 있으면 접수 자체를 하지 않는다)
        'agent_error_enabled'    => '0',   // 운영 오류 접수
        'agent_sr_enabled'       => '0',   // SR 접수
        'agent_auto_fix'         => '0',   // 코드 수정·배포까지 자동으로

        'agent_model'            => 'claude-sonnet-5',       // 분석용
        'agent_model_fix'        => 'claude-opus-5',         // 코드 수정용
        'agent_daily_limit'      => '20',  // 하루 처리 건수 상한 (비용 보호)
    ];

    /**
     * 비밀값 저장 — API 키처럼 그대로 두면 안 되는 값은 암호화해 담는다.
     * 빈 값을 주면 기존 값을 지우지 않고 그대로 둔다(화면에서 빈칸으로 제출해도 키가 날아가지 않게).
     */
    public static function putSecret(string $key, ?string $value): void
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }

        static::put($key, encrypt($value));
    }

    /** 비밀값 읽기 — 저장된 적이 없으면 빈 문자열 */
    public static function secret(string $key): string
    {
        $raw = (string) static::get($key);
        if ($raw === '') {
            return '';
        }

        try {
            return (string) decrypt($raw);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** 비밀값을 지운다 */
    public static function forgetSecret(string $key): void
    {
        static::put($key, '');
    }

    /** 쉼표로 구분된 설정값을 배열로 (빈 값 제거) */
    public static function emails(string $key): array
    {
        $raw = (string) static::get($key);

        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL)));
    }

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
