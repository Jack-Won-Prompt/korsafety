<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /** 비밀번호 찾기 (이메일 입력) */
    public function showLinkRequest()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email'], [], ['email' => '이메일']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', '비밀번호 재설정 링크를 이메일로 보냈습니다. 메일함을 확인해 주세요.');
        }
        // 계정이 없어도 동일 메시지(계정 존재 여부 노출 방지)
        return back()->with('status', '해당 이메일로 가입된 계정이 있다면 재설정 링크를 보냈습니다.');
    }

    /** 재설정 폼 */
    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:4|confirmed',
        ], [], ['email' => '이메일', 'password' => '비밀번호']);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
                $user->save();
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', '비밀번호가 변경되었습니다. 새 비밀번호로 로그인해 주세요.');
        }
        return back()->withErrors(['email' => '재설정에 실패했습니다. 링크가 만료되었거나 이메일이 올바르지 않습니다.'])->withInput($request->only('email'));
    }
}
