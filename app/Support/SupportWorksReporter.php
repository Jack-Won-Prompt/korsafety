<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 운영에서 난 예외를 SupportWorks 로 보낸다.
 *
 * 보고가 사이트를 망가뜨리면 안 된다. 그래서 예외를 절대 밖으로 내보내지 않고,
 * 응답을 돌려준 뒤에 보내고, 시간 제한을 짧게 둔다.
 */
class SupportWorksReporter
{
    /** 가려야 할 이름. 값이 그대로 남으면 오류 기록이 곧 자격증명 창고가 된다. */
    private const SECRET_KEYS = [
        'token', 'access_token', 'refresh_token', 'api_key', 'apikey',
        'secret', 'password', 'passwd', 'pw', 'signature', 'auth', 'key',
        'authorization', 'credential', 'credentials', 'bearer', 'otp', 'pin', 'session',
    ];

    private const MASK = '[숨김]';

    public static function report(Throwable $e, ?Request $request = null): void
    {
        try {
            $url = config('services.supportworks.error_url');
            $token = config('services.supportworks.error_token');

            if (! $url || ! $token) {
                return;
            }

            // 404 는 보내지 않는다. 고칠 것이 없고, 봇이 훑고 갈 때마다 쌓인다.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return;
            }

            $payload = [
                'level'     => 'error',
                'exception' => get_class($e),
                'message'   => mb_substr(self::maskMessage($e->getMessage()), 0, 2000),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'url'       => $request ? self::maskUrl($request) : null,
                'trace'     => mb_substr(self::trace($e), 0, 8000),
            ];

            // 응답을 돌려준 뒤에 보낸다. 콘솔에는 그 시점이 없으므로 바로 보낸다.
            $send = function () use ($url, $token, $payload) {
                try {
                    Http::withToken($token)->timeout(2)->connectTimeout(2)->post($url, $payload);
                } catch (Throwable) {
                    // 보고가 실패해도 사이트는 계속 돈다.
                }
            };

            app()->runningInConsole() ? $send() : app()->terminating($send);
        } catch (Throwable) {
            // 여기서 예외가 새어 나가면 예외 처리기 자체가 깨진다.
        }
    }

    /**
     * URL 에서 민감한 값을 가린다.
     *
     * 오류 기록은 오래 남고 여러 사람이 읽는다. 거기에 살아 있는 토큰이 적히면
     * 그 링크로 남의 계정 비밀번호를 바꿀 수 있다.
     *
     * 두 자리를 본다 — 쿼리스트링(`?token=...`)과 경로에 박힌 값
     * (`/reset-password/{token}`). 뒤엣것이 실제로 위험한 쪽이다.
     */
    public static function maskUrl(Request $request): string
    {
        $path = '/'.ltrim($request->path(), '/');

        // 경로 조각 중 토큰처럼 생긴 것(길고 무의미한 문자열)을 가린다.
        $segments = array_map(function (string $seg) {
            return self::looksLikeSecret($seg) ? self::MASK : $seg;
        }, explode('/', $path));

        $masked = implode('/', $segments);

        $query = self::maskParams($request->query());

        return $masked.($query ? '?'.http_build_query($query) : '');
    }

    /**
     * 토큰처럼 생긴 낱말인가 — 20자 이상이면서 영숫자 · `_` · `-` 로만 이루어진 것.
     * 경로 조각과 message 가 이 규칙 하나를 함께 쓴다. 두 벌이 되면 한쪽만 고쳐지는 날이 온다.
     */
    private static function looksLikeSecret(string $word): bool
    {
        return strlen($word) >= 20 && preg_match('/^[A-Za-z0-9_\-]+$/', $word) === 1;
    }

    /**
     * message 에서 토큰처럼 생긴 낱말만 가린다 — 예외 종류와 무관하게.
     * QueryException 은 SQL 과 바인딩 값을 message 에 그대로 담는다.
     *
     * 공백으로 끊은 낱말에서 앞뒤의 따옴표 · 괄호 · 쉼표 같은 문장부호만 떼고 가운데를 본다.
     * 역슬래시 · 슬래시 · 점이 섞인 클래스 이름이나 파일 경로는 한 낱말이 아니므로 남는다.
     * 무엇이 왜 났는지는 여전히 읽혀야 한다.
     */
    private static function maskMessage(string $message): string
    {
        // /u 없이 바이트 단위로 본다 — 깨진 UTF-8 이 와도 preg 가 실패하지 않도록
        $masked = preg_replace_callback('/[^ \t\r\n]+/', function (array $m) {
            if (preg_match('/^([\'"`(\[{<,;:=]*)(.*?)([\'"`)\]}>,;:.=]*)$/s', $m[0], $p) && self::looksLikeSecret($p[2])) {
                return $p[1].self::MASK.$p[3];
            }

            return $m[0];
        }, $message);

        return $masked ?? '[메시지를 가리지 못해 뺐다]';
    }

    /**
     * 스택 트레이스 — 파일 · 줄 · 클래스 · 함수만 남기고 인자 값은 아예 넣지 않는다.
     *
     * getTraceAsString() 은 인자를 앞 15자까지 적는다. /reset-password/{token} 의 토큰은
     * 컨트롤러 인자로 넘어가므로 그대로 트레이스에 남는다. php.ini 의
     * zend.exception_ignore_args 에 기대면 서버 하나만 설정이 달라도 새므로, 설정과 무관하게 직접 만든다.
     */
    private static function trace(Throwable $e): string
    {
        $lines = [];

        foreach ($e->getTrace() as $i => $frame) {
            $where = isset($frame['file'])
                ? $frame['file'].'('.($frame['line'] ?? 0).')'
                : '[internal function]';

            $call = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '');

            $lines[] = '#'.$i.' '.$where.': '.$call.'()';
        }

        $lines[] = '#'.count($lines).' {main}';

        return implode("\n", $lines);
    }

    /**
     * 이름을 낱말 단위로 본다 — 소문자로 만들고 영숫자가 아닌 것(`_` `-` `.` 등)으로 쪼갠 뒤,
     * 조각 하나가 목록의 이름과 정확히 같을 때만 가린다.
     * 부분 일치로 보면 keyword · author · monkey 까지 가려져 어느 화면인지 알 수 없게 된다.
     *
     * @param array<string, mixed> $params
     */
    private static function maskParams(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $params[$key] = self::maskParams($value);

                continue;
            }

            $name = strtolower((string) $key);
            $words = preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY);

            if (in_array($name, self::SECRET_KEYS, true) || array_intersect($words, self::SECRET_KEYS)) {
                $params[$key] = self::MASK;
            }
        }

        return $params;
    }
}
