<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmailVerificationController;
use App\Mail\EmailVerificationCodeMail;
use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** 고객 회원가입 */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(6)],
            'phone' => 'required|string|max:30',
            'postcode' => 'nullable|string|max:10',
            'address1' => 'nullable|string|max:200',
            'address2' => 'nullable|string|max:200',
            'verify_token' => 'required|string',
        ], [], ['name' => '이름', 'email' => '이메일', 'password' => '비밀번호', 'phone' => '휴대전화']);

        if (! EmailVerificationController::consumeVerifyToken($data['email'], $data['verify_token'])) {
            throw ValidationException::withMessages(['email' => ['이메일 인증을 먼저 완료해 주세요.']]);
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'postcode' => $data['postcode'] ?? null,
            'address1' => $data['address1'] ?? null,
            'address2' => $data['address2'] ?? null,
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        return $this->tokenResponse($user, $request);
    }

    /** 앱 회원가입 — 이메일 인증번호 발송 */
    public function sendEmailCode(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150|unique:users,email',
        ], ['email.unique' => '이미 가입된 이메일입니다. 로그인하거나 비밀번호 찾기를 이용해 주세요.'], ['email' => '이메일']);

        $retryAfter = null;
        $code = EmailVerificationController::issueCode($data['email'], $retryAfter);
        if ($code === null) {
            return response()->json([
                'message' => '방금 인증번호를 보냈습니다. 메일함을 확인해 주세요.',
                'retry_after' => $retryAfter,
            ], 429);
        }

        try {
            Mail::to($data['email'])->send(new EmailVerificationCodeMail($code, 10));
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => '인증 메일을 보내지 못했습니다. 잠시 후 다시 시도해 주세요.'], 500);
        }

        return response()->json(['message' => '인증번호를 보냈습니다. 메일함(스팸함 포함)을 확인해 주세요.', 'expires_in' => 600]);
    }

    /** 앱 회원가입 — 인증번호 확인, 가입에 쓸 인증 토큰을 돌려준다 */
    public function verifyEmailCode(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
            'code' => 'required|string|max:10',
        ], [], ['email' => '이메일', 'code' => '인증번호']);

        if ($error = EmailVerificationController::checkCode($data['email'], $data['code'])) {
            return response()->json(['message' => $error], 422);
        }

        return response()->json([
            'message' => '이메일 인증이 완료되었습니다.',
            'verify_token' => EmailVerificationController::issueVerifyToken($data['email']),
        ]);
    }

    /**
     * 앱 협력사 회원가입 — 사업자등록증을 함께 받는다.
     * 본사 승인 전에는 로그인할 수 없으므로 토큰을 발급하지 않는다.
     */
    public function registerPartner(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:50',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(6)],
            'verify_token' => 'required|string',
            'company_name' => 'required|string|max:150',
            'company_phone' => 'required|string|max:30',
            'company_fax' => 'nullable|string|max:30',
            'owner_name' => 'required|string|max:50',
            'business_address' => 'required|string|max:300',
            'license' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
        ], [], [
            'name' => '이름', 'phone' => '휴대전화', 'email' => '이메일', 'password' => '비밀번호',
            'company_name' => '회사명', 'company_phone' => '회사 전화번호', 'owner_name' => '대표자 이름',
            'business_address' => '사업장 주소', 'license' => '사업자등록증',
        ]);

        if (! EmailVerificationController::consumeVerifyToken($data['email'], $data['verify_token'])) {
            throw ValidationException::withMessages(['email' => ['이메일 인증을 먼저 완료해 주세요.']]);
        }

        $file = $request->file('license');
        $name = date('Ymd_His').'_'.Str::lower(Str::random(8)).'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('partner-licenses', $name, 'local');

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'role' => 'partner',
            'password' => Hash::make($data['password']),
        ]);

        PartnerProfile::create([
            'user_id' => $user->id,
            'company_name' => $data['company_name'],
            'company_phone' => $data['company_phone'],
            'company_fax' => $data['company_fax'] ?? null,
            'owner_name' => $data['owner_name'],
            'business_address' => $data['business_address'],
            'license_path' => $path,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => '협력사 가입 신청이 접수되었습니다. 본사 확인 후 승인되면 협력사 할인가로 구매하실 수 있습니다.',
            'status' => 'pending',
        ], 201);
    }

    /** 로그인 (고객 + 판매점 + 협력사 + 본사 공통) */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['이메일 또는 비밀번호가 올바르지 않습니다.'],
            ]);
        }

        if ($reason = $user->loginBlockReason()) {
            throw ValidationException::withMessages(['email' => [$reason]]);
        }

        // 판매점 / 협력사 승인 상태 확인
        if ($user->role === 'seller' && (! $user->seller || $user->seller->status !== 'approved')) {
            throw ValidationException::withMessages(['email' => ['판매점 승인이 완료되지 않았거나 정지된 계정입니다.']]);
        }
        if ($user->role === 'agent' && (! $user->agent || $user->agent->status !== 'approved')) {
            throw ValidationException::withMessages(['email' => ['협력사 승인이 완료되지 않았거나 정지된 계정입니다.']]);
        }
        if ($user->role === 'purchaser' && (! $user->purchaser || $user->purchaser->status !== 'approved')) {
            throw ValidationException::withMessages(['email' => ['구매처 승인이 완료되지 않았거나 정지된 계정입니다.']]);
        }

        return $this->tokenResponse($user, $request);
    }

    /**
     * 비밀번호 찾기 — 가입 이메일로 재설정 링크를 보낸다.
     * 계정이 있는지 알려주지 않도록 결과 메시지는 항상 같다.
     */
    public function forgotPassword(Request $request)
    {
        $data = $request->validate(['email' => 'required|email'], [], ['email' => '이메일']);

        PasswordBroker::sendResetLink(['email' => $data['email']]);

        return response()->json([
            'message' => '가입된 이메일이라면 비밀번호 재설정 링크를 보냈습니다. 메일함을 확인해 주세요.',
        ]);
    }

    /** 현재 사용자 */
    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /** 로그아웃 (현재 토큰 파기) */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => '로그아웃되었습니다.']);
    }

    private function tokenResponse(User $user, Request $request)
    {
        $device = $request->input('device_name', 'mobile');
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'seller' => $user->seller ? ['id' => $user->seller->id, 'name' => $user->seller->name, 'is_hq' => (bool) $user->seller->is_hq] : null,
            'agent' => $user->agent ? ['id' => $user->agent->id, 'name' => $user->agent->name, 'commission_rate' => $user->agent->commission_rate] : null,
            'purchaser' => $user->purchaser ? ['id' => $user->purchaser->id, 'name' => $user->purchaser->name, 'cashback_rate' => $user->purchaser->cashback_rate] : null,
        ];
    }
}
