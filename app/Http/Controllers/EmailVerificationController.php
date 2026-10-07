<?php

namespace App\Http\Controllers;

use App\Mail\EmailVerificationCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * 회원가입 이메일 인증 — 가입 전에 이메일로 인증번호를 보내고 확인한다.
 * 인증번호는 캐시에 10분만 보관하고, 확인되면 그 이메일을 세션에 인증된 것으로 기록한다.
 */
class EmailVerificationController extends Controller
{
    /** 인증번호 유효 시간 (분) */
    private const CODE_MINUTES = 10;

    /** 인증 완료 상태 유지 시간 (분) — 가입 폼을 채우는 동안 */
    private const VERIFIED_MINUTES = 30;

    /** 한 인증번호로 시도할 수 있는 횟수 */
    private const MAX_TRIES = 5;

    private function codeKey(string $email): string
    {
        return 'email-verify:'.sha1(mb_strtolower(trim($email)));
    }

    /** 인증번호 발송 */
    public function send(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150|unique:users,email',
        ], [
            'email.unique' => '이미 가입된 이메일입니다. 로그인하거나 비밀번호 찾기를 이용해 주세요.',
        ], ['email' => '이메일']);

        $email = mb_strtolower(trim($data['email']));
        $key = $this->codeKey($email);

        // 1분 안에 다시 요청하면 기존 번호를 다시 보내지 않고 안내만 한다
        // (캐시에는 날짜 객체 대신 숫자로 담는다 — 저장소에 따라 객체 복원이 실패할 수 있다)
        $existing = Cache::get($key);
        $elapsed = $existing && isset($existing['sent_at']) ? now()->timestamp - (int) $existing['sent_at'] : null;
        if ($elapsed !== null && $elapsed < 60) {
            return response()->json([
                'message' => '방금 인증번호를 보냈습니다. 메일함을 확인해 주세요.',
                'retry_after' => 60 - $elapsed,
            ], 429);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::put($key, [
            'code' => $code,
            'tries' => 0,
            'sent_at' => now()->timestamp,
        ], now()->addMinutes(self::CODE_MINUTES));

        try {
            Mail::to($email)->send(new EmailVerificationCodeMail($code, self::CODE_MINUTES));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => '인증 메일을 보내지 못했습니다. 잠시 후 다시 시도해 주세요.'], 500);
        }

        return response()->json([
            'message' => '인증번호를 보냈습니다. 메일함(스팸함 포함)을 확인해 주세요.',
            'expires_in' => self::CODE_MINUTES * 60,
        ]);
    }

    /** 인증번호 확인 */
    public function confirm(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
            'code' => 'required|string|max:10',
        ], [], ['email' => '이메일', 'code' => '인증번호']);

        $email = mb_strtolower(trim($data['email']));
        $key = $this->codeKey($email);
        $saved = Cache::get($key);

        if (! $saved) {
            return response()->json(['message' => '인증번호가 만료되었습니다. 다시 받아 주세요.'], 422);
        }

        if ($saved['tries'] >= self::MAX_TRIES) {
            Cache::forget($key);

            return response()->json(['message' => '인증번호를 너무 여러 번 틀렸습니다. 다시 받아 주세요.'], 429);
        }

        if (! hash_equals($saved['code'], preg_replace('/\D/', '', $data['code']))) {
            $saved['tries']++;
            Cache::put($key, $saved, now()->addMinutes(self::CODE_MINUTES));

            return response()->json([
                'message' => '인증번호가 맞지 않습니다. ('.(self::MAX_TRIES - $saved['tries']).'회 남음)',
            ], 422);
        }

        Cache::forget($key);
        self::markVerified($request, $email);

        return response()->json(['message' => '이메일 인증이 완료되었습니다.']);
    }

    /** 세션에 인증된 이메일로 기록 */
    public static function markVerified(Request $request, string $email): void
    {
        $verified = (array) $request->session()->get('verified_emails', []);
        $verified[mb_strtolower(trim($email))] = now()->timestamp;
        $request->session()->put('verified_emails', $verified);
    }

    /** 가입 처리에서 호출 — 이 이메일이 인증을 마쳤는가 */
    public static function isVerified(Request $request, string $email): bool
    {
        $verified = (array) $request->session()->get('verified_emails', []);
        $at = $verified[mb_strtolower(trim($email))] ?? null;

        return $at !== null && (now()->timestamp - (int) $at) <= self::VERIFIED_MINUTES * 60;
    }
}
