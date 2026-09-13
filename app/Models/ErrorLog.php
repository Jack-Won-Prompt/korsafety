<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * 서버 에러 이력.
 * 같은 원인(클래스 · 발생 위치 · 정규화한 메시지)의 미해결 에러는 한 건으로 묶고 발생 횟수만 올린다.
 * 해결 처리한 에러가 다시 나면 새 미해결 건으로 등록한다 (해결 이력 보존).
 * 기록 실패가 원래 에러 처리를 막지 않도록 모든 쓰기는 예외를 삼킨다.
 */
class ErrorLog extends Model
{
    public const TYPES = [
        'database' => 'DB 오류',
        'php' => 'PHP 오류',
        'http' => 'HTTP 오류',
        'external' => '외부 연동',
        'application' => '애플리케이션',
    ];

    public const STATUSES = ['unresolved' => '미해결', 'resolved' => '해결'];

    public const SOURCES = ['web' => '웹', 'api' => 'API', 'console' => '콘솔'];

    protected $fillable = [
        'fingerprint', 'type', 'source', 'exception_class', 'message', 'code', 'file', 'line', 'trace',
        'url', 'method', 'ip_address', 'user_id', 'user_agent', 'input',
        'occurrences', 'first_seen_at', 'last_seen_at',
        'status', 'resolved_at', 'resolved_by', 'resolution_note',
    ];

    protected $casts = [
        'input' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    private static bool $writing = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getShortClassAttribute(): string
    {
        return class_basename($this->exception_class);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /** 보고된 예외 기록 (bootstrap/app.php의 report 훅에서 호출) */
    public static function capture(Throwable $e): void
    {
        if (self::$writing) {
            return;   // 기록 중 난 에러로 인한 재귀 방지
        }
        self::$writing = true;

        try {
            [$file, $line] = self::location($e);
            $message = self::clean($e->getMessage(), 5000);
            $class = get_class($e);
            $fingerprint = sha1($class.'|'.$file.'|'.$line.'|'.self::normalize($message));
            $now = now();

            $context = self::requestContext() + ['message' => $message];

            $open = self::where('fingerprint', $fingerprint)->where('status', 'unresolved')->first();
            if ($open) {
                $open->fill($context + ['last_seen_at' => $now]);
                $open->occurrences = $open->occurrences + 1;
                $open->save();

                return;
            }

            self::create($context + [
                'fingerprint' => $fingerprint,
                'type' => self::classify($e),
                'exception_class' => $class,
                'code' => $e->getCode() !== 0 ? mb_substr((string) $e->getCode(), 0, 50) : null,
                'file' => $file,
                'line' => $line,
                'trace' => self::trace($e),
                'occurrences' => 1,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'status' => 'unresolved',
            ]);
        } catch (Throwable $ignored) {
            // DB 장애 등으로 기록하지 못해도 기본 파일 로그는 그대로 남는다
        } finally {
            self::$writing = false;
        }
    }

    /** 예외 클래스로 에러 유형 분류 */
    public static function classify(Throwable $e): string
    {
        return match (true) {
            $e instanceof \Illuminate\Database\QueryException,
            $e instanceof \PDOException => 'database',
            $e instanceof HttpExceptionInterface => 'http',
            $e instanceof \Illuminate\Http\Client\ConnectionException,
            $e instanceof \Illuminate\Http\Client\RequestException,
            $e instanceof \GuzzleHttp\Exception\GuzzleException => 'external',
            $e instanceof \Error,
            $e instanceof \ErrorException => 'php',
            default => 'application',
        };
    }

    /** vendor 밖(앱 코드)에서 처음 걸린 위치 — DB 에러처럼 vendor 안에서 던져진 예외도 원인 코드를 가리키도록 */
    private static function location(Throwable $e): array
    {
        $frames = array_merge([['file' => $e->getFile(), 'line' => $e->getLine()]], $e->getTrace());
        foreach ($frames as $f) {
            if (! empty($f['file']) && ! self::isVendor($f['file'])) {
                return [self::relative($f['file']), (int) ($f['line'] ?? 0)];
            }
        }

        return [self::relative($e->getFile()), $e->getLine()];
    }

    private static function isVendor(string $file): bool
    {
        $file = str_replace('\\', '/', $file);

        return str_contains($file, '/vendor/') || str_contains($file, '/storage/framework/views/');
    }

    private static function relative(string $file): string
    {
        $base = str_replace('\\', '/', base_path()).'/';

        return mb_substr(str_replace($base, '', str_replace('\\', '/', $file)), 0, 500);
    }

    /** 숫자 · 따옴표 값이 달라도 같은 에러로 묶이도록 정규화 */
    private static function normalize(string $message): string
    {
        $m = preg_replace(["/'[^']*'/", '/"[^"]*"/', '/\d+/'], ["'?'", '"?"', 'N'], $message);

        return mb_substr((string) $m, 0, 300);
    }

    private static function trace(Throwable $e): string
    {
        $base = str_replace('\\', '/', base_path()).'/';
        $out = str_replace([base_path().DIRECTORY_SEPARATOR, $base], '', $e->getTraceAsString());

        $prev = $e->getPrevious();
        $depth = 0;
        while ($prev && $depth++ < 5) {
            $out .= "\n\n── 원인 (Previous) ──\n".get_class($prev).': '.self::clean($prev->getMessage(), 2000)
                ."\n@ ".self::relative($prev->getFile()).':'.$prev->getLine();
            $prev = $prev->getPrevious();
        }

        return self::clean($out, 60000);
    }

    private static function requestContext(): array
    {
        if (app()->runningInConsole() || ! app()->bound('request')) {
            $argv = $_SERVER['argv'] ?? [];
            if ($argv && basename((string) $argv[0]) === 'artisan') {
                $argv[0] = 'artisan';
            }

            return [
                'source' => 'console',
                'url' => $argv ? mb_substr(implode(' ', $argv), 0, 1000) : null,
                'method' => 'CLI',
                'ip_address' => null,
                'user_id' => null,
                'user_agent' => null,
                'input' => null,
            ];
        }

        $request = request();
        $userId = null;
        try {
            $userId = Auth::id();
        } catch (Throwable $ignored) {
        }

        return [
            'source' => $request->is('api/*') ? 'api' : 'web',
            'url' => mb_substr($request->fullUrl(), 0, 1000),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_id' => $userId,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 512) ?: null,
            'input' => self::scrub($request->all()) ?: null,
        ];
    }

    /** 비밀번호 · 토큰 · 결제 정보는 가리고, 값은 짧게 자른다 */
    private static function scrub(array $data, int $depth = 0): array
    {
        $out = [];
        foreach (array_slice($data, 0, 50, true) as $k => $v) {
            if (preg_match('/pass|token|secret|card|cvc|cvv|api_?key|authorization|account_?no/i', (string) $k)) {
                $out[$k] = '[숨김]';
            } elseif ($v instanceof UploadedFile) {
                $out[$k] = '[파일] '.$v->getClientOriginalName();
            } elseif (is_array($v)) {
                $out[$k] = $depth < 3 ? self::scrub($v, $depth + 1) : '[...]';
            } elseif (is_string($v)) {
                $out[$k] = self::clean($v, 300);
            } elseif (is_scalar($v) || $v === null) {
                $out[$k] = $v;
            } else {
                $out[$k] = '['.get_debug_type($v).']';
            }
        }

        return $out;
    }

    private static function clean(string $s, int $max): string
    {
        return mb_substr(mb_convert_encoding($s, 'UTF-8', 'UTF-8'), 0, $max);
    }
}
