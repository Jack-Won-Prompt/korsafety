<?php

namespace Tests\Feature;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Tests\TestCase;

/** 오류 로그에 저장되는 URL — 민감한 값만 가리고 화면은 알아볼 수 있어야 한다 */
class ErrorLogUrlMaskTest extends TestCase
{
    private function mask(string $uri, string $method = 'GET'): string
    {
        return ErrorLog::maskUrl(Request::create($uri, $method));
    }

    public function test_password_reset_token_in_path_is_masked(): void
    {
        $this->assertSame(
            'http://localhost/reset-password/[숨김]?email=user%40example.com',
            $this->mask('http://localhost/reset-password/9f86d081884c7d659a2feaa0c55ad015?email=user%40example.com'),
        );
    }

    public function test_inquiry_token_in_path_is_masked(): void
    {
        $this->assertSame('http://localhost/inquiry/[숨김]/poll', $this->mask('/inquiry/Ab12Cd34Ef56Gh78/poll'));
        $this->assertSame('http://localhost/inquiry/[숨김]/message', $this->mask('/inquiry/Ab12Cd34Ef56Gh78/message', 'POST'));
    }

    public function test_path_is_masked_even_when_method_does_not_match(): void
    {
        // 라우팅 전 · 405 상황에서도 경로 토큰은 가린다
        $this->assertSame('http://localhost/inquiry/[숨김]/message', $this->mask('/inquiry/Ab12Cd34Ef56Gh78/message'));
    }

    public function test_sensitive_query_values_are_masked_but_keys_stay(): void
    {
        $this->assertSame(
            'http://localhost/search?q=shoes&token=[숨김]&api_key=[숨김]&user[password]=[숨김]',
            $this->mask('/search?q=shoes&token=T0KEN&api_key=K3Y&user[password]=pw'),
        );
    }

    public function test_plain_urls_are_kept_as_is(): void
    {
        $this->assertSame('http://localhost/product/123?page=2', $this->mask('/product/123?page=2'));
        $this->assertSame('http://localhost/cart/update/12:34', $this->mask('/cart/update/12:34', 'PATCH'));
        $this->assertSame('http://localhost/no-such-page/abc', $this->mask('/no-such-page/abc'));
        $this->assertSame('http://localhost/', $this->mask('/'));
    }

    public function test_trace_does_not_keep_argument_values(): void
    {
        // 라우트가 {token} 값을 컨트롤러 인자로 넘기므로, 트레이스에 인자 값이 찍히면 토큰이 남는다
        $fail = function (string $token) {
            throw new \RuntimeException('x');
        };
        try {
            $fail('Ab12Cd34Ef56Gh78Ij90Kl');
        } catch (\RuntimeException $e) {
        }

        $trace = (new \ReflectionMethod(ErrorLog::class, 'trace'))->invoke(null, $e);

        $this->assertStringNotContainsString('Ab12Cd34', $trace);
        $this->assertStringContainsString('ErrorLogUrlMaskTest->{closure', $trace);
    }
}
