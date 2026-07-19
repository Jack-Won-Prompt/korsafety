<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($u = Auth::user()) {
            return redirect($this->homeFor($u));
        }
        return view('manage.login');
    }

    private function homeFor($u): string
    {
        if ($u->isHqAdmin()) return route('admin.index');
        if ($u->isAgent()) return route('agent.index');
        if ($u->isPurchaser()) return route('purchaser.index');
        return route('seller.index');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.'])->withInput();
        }

        $user = Auth::user();

        if ($user->isHqAdmin()) {
            $request->session()->regenerate();
            return redirect()->intended(route('admin.index'));
        }

        if ($user->isSeller()) {
            if (! $user->seller || $user->seller->status !== 'approved') {
                $msg = ($user->seller && $user->seller->status === 'pending')
                    ? '입점 승인 대기중입니다. 본사 승인 후 이용할 수 있습니다.'
                    : '정지되었거나 승인되지 않은 판매점 계정입니다.';
                return $this->fail($request, $msg);
            }
            $request->session()->regenerate();
            return redirect()->intended(route('seller.index'));
        }

        if ($user->isAgent()) {
            if (! $user->agent || $user->agent->status !== 'approved') {
                $msg = ($user->agent && $user->agent->status === 'pending')
                    ? '협력사 승인 대기중입니다. 본사 승인 후 이용할 수 있습니다.'
                    : '정지되었거나 승인되지 않은 협력사 계정입니다.';
                return $this->fail($request, $msg);
            }
            $request->session()->regenerate();
            return redirect()->intended(route('agent.index'));
        }

        if ($user->isPurchaser()) {
            if (! $user->purchaser || $user->purchaser->status !== 'approved') {
                $msg = ($user->purchaser && $user->purchaser->status === 'pending')
                    ? '구매 대행자 승인 대기중입니다. 본사 승인 후 이용할 수 있습니다.'
                    : '정지되었거나 승인되지 않은 구매 대행자 계정입니다.';
                return $this->fail($request, $msg);
            }
            $request->session()->regenerate();
            return redirect()->intended(route('purchaser.index'));
        }

        // 일반 고객 계정은 관리자 로그인 불가
        return $this->fail($request, '관리자/판매자/협력사/구매대행 계정이 아닙니다.');
    }

    private function fail(Request $request, string $msg)
    {
        Auth::logout();
        $request->session()->invalidate();
        return back()->withErrors(['email' => $msg])->withInput();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('manage.login');
    }
}
