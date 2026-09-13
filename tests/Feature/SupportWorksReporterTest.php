<?php

namespace Tests\Feature;

use App\Support\SupportWorksReporter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** SupportWorks 오류 보고 — 민감한 값은 가리고, 보고가 사이트를 망가뜨리지 않아야 한다 */
class SupportWorksReporterTest extends TestCase
{
    private const MASKED = '%5B%EC%88%A8%EA%B9%80%5D';   // urlencode('[숨김]')

    private function mask(string $uri): string
    {
        return SupportWorksReporter::maskUrl(Request::create($uri));
    }

    public function test_only_secret_query_value_is_masked(): void
    {
        $masked = $this->mask('/search?token=SECRET&q=shoes');

        $this->assertStringNotContainsString('SECRET', $masked);
        $this->assertSame('/search?token='.self::MASKED.'&q=shoes', $masked);
    }

    public function test_token_in_path_is_masked(): void
    {
        $token = str_repeat('9f86d081884c7d65', 4);   // 64자, 비밀번호 재설정 토큰 모양

        $masked = $this->mask('/reset-password/'.$token.'?email=user%40example.com');

        $this->assertStringNotContainsString($token, $masked);
        $this->assertSame('/reset-password/[숨김]?email=user%40example.com', $masked);
    }

    public function test_short_numeric_id_is_not_masked(): void
    {
        $this->assertSame('/orders/123', $this->mask('/orders/123'));
    }

    public function test_plain_urls_are_kept_as_is(): void
    {
        $this->assertSame('/product/123?page=2', $this->mask('/product/123?page=2'));
        $this->assertSame('/category/safety-shoes', $this->mask('/category/safety-shoes'));
        $this->assertSame('/', $this->mask('/'));
    }

    /** @return array<string, mixed> */
    private function maskParams(array $params): array
    {
        return (new \ReflectionMethod(SupportWorksReporter::class, 'maskParams'))->invoke(null, $params);
    }

    public function test_secret_names_are_masked_word_by_word(): void
    {
        foreach (['token', 'api_key', 'access-token', 'user.password'] as $name) {
            $this->assertSame('[숨김]', $this->maskParams([$name => 'v'])[$name], $name.' 은 가려져야 한다');
        }
    }

    public function test_names_merely_containing_a_secret_word_are_kept(): void
    {
        foreach (['keyword', 'author', 'monkey', 'keynote'] as $name) {
            $this->assertSame('v', $this->maskParams([$name => 'v'])[$name], $name.' 은 가려지면 안 된다');
        }
    }

    public function test_query_keyword_is_kept_but_nested_password_is_masked(): void
    {
        $this->assertSame(
            '/search?keyword=shoes&user%5Bpassword%5D='.self::MASKED,
            $this->mask('/search?keyword=shoes&user[password]=pw'),
        );
    }

    /** 긴 문자열을 인자로 받아 예외를 던진다 — 토큰이 컨트롤러 인자로 넘어가는 상황 */
    private function throwWith(string $secret): never
    {
        throw new \RuntimeException('trace 점검');
    }

    public function test_trace_sent_does_not_contain_argument_values_but_stays_useful(): void
    {
        config(['services.supportworks.error_url' => 'https://example.invalid/errors', 'services.supportworks.error_token' => 'not-a-real-token']);
        Http::fake();

        $secret = 'Zq7xK2mP9vL4wR8tY3nB6cJ1hF5dG0sA';   // 32자, 조각으로도 남으면 안 된다
        $fail = fn (string $token) => $this->throwWith($token);

        try {
            $fail($secret);
        } catch (\RuntimeException $e) {
            SupportWorksReporter::report($e);
        }

        Http::assertSent(function ($req) use ($secret) {
            $trace = (string) $req['trace'];

            // 인자 값은 15자짜리 앞부분(getTraceAsString 이 남기던 만큼)도, 더 짧은 조각도 없어야 한다
            $this->assertStringNotContainsString(substr($secret, 0, 15), $trace);
            $this->assertStringNotContainsString(substr($secret, 0, 6), $trace);

            // 그래도 어디서 났는지는 알아볼 수 있어야 한다 — 파일 · 줄 · 클래스 · 함수
            $this->assertMatchesRegularExpression('/^#0 .*SupportWorksReporterTest\.php\(\d+\): /', $trace);
            $this->assertStringContainsString('SupportWorksReporterTest->throwWith()', $trace);
            $this->assertStringContainsString('{main}', $trace);

            return true;
        });
    }

    private function maskMessage(string $message): string
    {
        return (new \ReflectionMethod(SupportWorksReporter::class, 'maskMessage'))->invoke(null, $message);
    }

    public function test_long_token_in_message_is_masked(): void
    {
        $this->assertSame(
            'SQL: select * from users where token = [숨김]',
            $this->maskMessage('SQL: select * from users where token = a1b2c3d4e5f6g7h8i9j0k1l2'),
        );
    }

    public function test_class_names_paths_and_korean_in_message_are_kept(): void
    {
        foreach ([
            'App\Http\Controllers\OrderController',
            '/home/ubuntu/www/korsafety/app/X.php',
            '주문번호가 비어 있습니다',
        ] as $message) {
            $this->assertSame($message, $this->maskMessage($message));
        }
    }

    public function test_newly_listed_names_are_masked(): void
    {
        foreach (['authorization', 'credential', 'credentials', 'bearer', 'otp', 'pin', 'session', 'X-Authorization', 'session_id'] as $name) {
            $this->assertSame('[숨김]', $this->maskParams([$name => 'v'])[$name], $name.' 은 가려져야 한다');
        }
    }

    public function test_real_query_exception_message_does_not_carry_binding_value(): void
    {
        config(['services.supportworks.error_url' => 'https://example.invalid/errors', 'services.supportworks.error_token' => 'not-a-real-token']);
        Http::fake();

        $secret = 'Zq7xK2mP9vL4wR8tY3nB6cJ1hF5dG0sA';

        try {
            \Illuminate\Support\Facades\DB::select('select * from sw_no_such_table where token = ?', [$secret]);
            $this->fail('QueryException 이 나야 한다');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString($secret, $e->getMessage());   // 원래 message 에는 바인딩이 들어 있다
            SupportWorksReporter::report($e);
        }

        Http::assertSent(function ($req) use ($secret) {
            $message = (string) $req['message'];

            $this->assertStringNotContainsString(substr($secret, 0, 6), $message);
            $this->assertStringContainsString('[숨김]', $message);
            // 무엇이 왜 났는지는 읽혀야 한다
            $this->assertStringContainsString('no such table: sw_no_such_table', $message);
            $this->assertStringContainsString('where token =', $message);

            return true;
        });
    }

    public function test_report_is_skipped_without_config(): void
    {
        Http::fake();
        config(['services.supportworks.error_url' => null, 'services.supportworks.error_token' => null]);

        SupportWorksReporter::report(new \RuntimeException('boom'));

        Http::assertNothingSent();
    }

    public function test_report_failure_never_escapes(): void
    {
        config(['services.supportworks.error_url' => 'https://example.invalid/errors', 'services.supportworks.error_token' => 'not-a-real-token']);
        Http::fake(fn () => throw new ConnectionException('down'));

        SupportWorksReporter::report(new \RuntimeException('boom'));

        $this->assertTrue(true);   // 여기까지 오면 예외가 새지 않은 것
    }

    public function test_exception_in_web_request_is_reported_after_response_and_site_keeps_running(): void
    {
        config(['services.supportworks.error_url' => 'https://example.invalid/errors', 'services.supportworks.error_token' => 'not-a-real-token']);
        Http::fake();
        Route::get('/__sw-boom', fn () => throw new \RuntimeException('일부러 낸 예외'));

        // 전체 URL 로 요청 — APP_URL 이 하위 경로(/korsafety)여도 라우트가 맞도록
        $this->get('http://localhost/__sw-boom?token=SECRET')->assertStatus(500);

        Http::assertSent(fn ($req) => $req->url() === 'https://example.invalid/errors'
            && $req['exception'] === \RuntimeException::class
            && $req['message'] === '일부러 낸 예외'
            && ! str_contains((string) $req['url'], 'SECRET'));

        $this->get('http://localhost/up')->assertOk();
    }

    public function test_web_report_is_sent_only_after_response(): void
    {
        config(['services.supportworks.error_url' => 'https://example.invalid/errors', 'services.supportworks.error_token' => 'not-a-real-token']);
        Http::fake();

        // 테스트는 콘솔에서 돌므로, 웹 요청처럼 보이게 잠시 바꾼다
        $flag = new \ReflectionProperty($this->app, 'isRunningInConsole');
        $flag->setValue($this->app, false);

        try {
            SupportWorksReporter::report(new \RuntimeException('boom'), Request::create('/checkout'));

            Http::assertNothingSent();   // 응답 전에는 보내지 않는다

            $this->app->terminate();     // 응답을 돌려준 뒤

            Http::assertSentCount(1);
        } finally {
            $flag->setValue($this->app, null);
        }
    }
}
