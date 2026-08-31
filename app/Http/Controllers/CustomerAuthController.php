<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CustomerAuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect()->route('home');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [], ['email' => '이메일', 'password' => '비밀번호']);

        if (! Auth::attempt($data, $request->boolean('remember'))) {
            return back()->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.'])->withInput();
        }

        $request->session()->regenerate();
        $user = Auth::user();

        // 관리 계정이면 각 콘솔로, 고객이면 쇼핑몰로
        if ($user->isHqAdmin()) return redirect()->route('admin.index');
        if ($user->isSeller()) return redirect()->route('seller.index');
        if ($user->isAgent()) return redirect()->route('agent.index');
        if ($user->isPurchaser()) return redirect()->route('purchaser.index');

        return redirect()->intended(route('home'));
    }

    /** 회원가입 사용 여부 — 사이트 설정에서 끄면 가입 자체를 막는다 */
    private function signupClosed()
    {
        if (Setting::bool('signup_enabled')) {
            return null;
        }

        return redirect()->route('login')->withErrors(['email' => '현재 회원가입을 받고 있지 않습니다. 문의는 고객센터로 연락해 주세요.']);
    }

    public function showRegister()
    {
        if ($stop = $this->signupClosed()) return $stop;
        if (Auth::check()) return redirect()->route('home');
        return view('auth.register');
    }

    public function register(Request $request)
    {
        if ($stop = $this->signupClosed()) return $stop;

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|min:4|confirmed',
        ], [], ['name' => '이름', 'email' => '이메일', 'password' => '비밀번호']);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'customer',
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('welcome', $user->name.'님, 회원가입을 환영합니다!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
