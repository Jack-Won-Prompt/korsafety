<?php

namespace App\Services\Push;

use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FCM HTTP v1 발송기.
 *
 * 서비스 계정 JSON(비공개 키)으로 직접 서명한 JWT를 구글 OAuth 토큰으로 교환해 사용한다.
 * (google/apiclient 등 추가 패키지 불필요 — openssl_sign 만 사용)
 *
 * 설정이 없으면 모든 발송은 조용히 무시된다(fail-open). 알림 실패가 주문 처리를 막지 않는다.
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_CACHE_KEY = 'fcm.access_token';

    /** 서비스 계정 JSON 내용 (없으면 null) */
    private ?array $credentials = null;

    public function __construct()
    {
        $path = config('services.fcm.credentials');
        if ($path && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (is_array($json) && isset($json['private_key'], $json['client_email'])) {
                $this->credentials = $json;
            }
        }
    }

    /** 푸시를 보낼 수 있는 상태인가 */
    public function enabled(): bool
    {
        return $this->credentials !== null && $this->projectId() !== null;
    }

    private function projectId(): ?string
    {
        return config('services.fcm.project_id') ?: ($this->credentials['project_id'] ?? null);
    }

    /**
     * 사용자들에게 알림 발송.
     *
     * @param  array<int>  $userIds
     * @param  array<string,string>  $data  앱이 탭 처리에 쓰는 부가 데이터(문자열만 허용)
     * @return int 발송 성공한 기기 수
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): int
    {
        $userIds = array_values(array_unique(array_filter($userIds)));
        if (! $userIds || ! $this->enabled()) {
            return 0;
        }

        $tokens = DeviceToken::whereIn('user_id', $userIds)->get();
        $sent = 0;
        foreach ($tokens as $row) {
            if ($this->sendToToken($row, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /** 단일 기기 발송. 만료 토큰은 정리한다. */
    private function sendToToken(DeviceToken $row, string $title, string $body, array $data): bool
    {
        $accessToken = $this->accessToken();
        if (! $accessToken) {
            return false;
        }

        $payload = [
            'message' => [
                'token' => $row->token,
                'notification' => ['title' => $title, 'body' => $body],
                'data' => array_map('strval', $data),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'orders',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
                'apns' => [
                    'payload' => ['aps' => ['sound' => 'default']],
                ],
            ],
        ];

        try {
            $res = Http::withToken($accessToken)
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId()}/messages:send", $payload);
        } catch (\Throwable $e) {
            Log::warning('FCM 발송 실패(네트워크): '.$e->getMessage());

            return false;
        }

        if ($res->successful()) {
            $row->forceFill(['last_used_at' => now()])->save();

            return true;
        }

        // 등록 해제/무효 토큰은 삭제해 다음부터 시도하지 않는다.
        $status = $res->json('error.status');
        if ($res->status() === 404 || $status === 'UNREGISTERED' || $status === 'INVALID_ARGUMENT') {
            $row->delete();

            return false;
        }

        Log::warning('FCM 발송 실패: '.$res->status().' '.$res->body());

        return false;
    }

    /** 액세스 토큰 (55분 캐시) */
    private function accessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3300, function () {
            $jwt = $this->signedJwt();
            if (! $jwt) {
                return null;
            }

            try {
                $res = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
            } catch (\Throwable $e) {
                Log::warning('FCM 토큰 발급 실패(네트워크): '.$e->getMessage());

                return null;
            }

            if (! $res->successful()) {
                Log::warning('FCM 토큰 발급 실패: '.$res->body());

                return null;
            }

            return $res->json('access_token');
        });
    }

    /** 서비스 계정 비공개 키로 RS256 서명한 JWT 생성 */
    private function signedJwt(): ?string
    {
        $c = $this->credentials;
        if (! $c) {
            return null;
        }

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss' => $c['client_email'],
            'scope' => self::SCOPE,
            'aud' => $c['token_uri'] ?? 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $input = $this->b64(json_encode($header)).'.'.$this->b64(json_encode($claims));

        $key = openssl_pkey_get_private($c['private_key']);
        if (! $key) {
            Log::warning('FCM 서비스 계정 비공개 키를 읽을 수 없습니다.');

            return null;
        }

        $signature = '';
        if (! openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256)) {
            return null;
        }

        return $input.'.'.$this->b64($signature);
    }

    private function b64(string $raw): string
    {
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
