<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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
            'password' => ['required', 'confirmed', Password::min(8)],
            'phone' => 'nullable|string|max:30',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);

        return $this->tokenResponse($user, $request);
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
