<?php

namespace App\Http\Controllers;

use App\Models\PartnerProfile;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 협력사 회원가입 — 사업자 거래처가 할인가로 구매하기 위해 가입한다.
 * 가입 즉시 로그인은 되지만, 본사가 승인해야 협력사 할인가가 적용된다.
 */
class PartnerRegisterController extends Controller
{
    /** 사업자등록증 보관 위치 — public 밖이라 주소를 알아도 열리지 않는다 */
    private const LICENSE_DIR = 'partner-licenses';

    private function signupClosed()
    {
        if (Setting::bool('signup_enabled')) {
            return null;
        }

        return redirect()->route('login')->withErrors(['email' => '현재 회원가입을 받고 있지 않습니다. 문의는 고객센터로 연락해 주세요.']);
    }

    public function show()
    {
        if ($stop = $this->signupClosed()) {
            return $stop;
        }
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.partner-register');
    }

    public function register(Request $request)
    {
        if ($stop = $this->signupClosed()) {
            return $stop;
        }

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:150|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'company_name' => 'required|string|max:150',
            'company_phone' => 'required|string|max:30',
            'company_fax' => 'nullable|string|max:30',
            'owner_name' => 'required|string|max:50',
            'postcode' => 'nullable|string|max:10',
            'address1' => 'required|string|max:200',
            'address2' => 'nullable|string|max:200',
            'license' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
        ], [], [
            'name' => '가입자 이름', 'phone' => '가입자 휴대전화', 'email' => '이메일', 'password' => '비밀번호',
            'company_name' => '회사명', 'company_phone' => '회사 전화번호', 'company_fax' => '회사 팩스',
            'owner_name' => '대표자 이름', 'postcode' => '우편번호', 'address1' => '사업장 주소',
            'address2' => '상세주소', 'license' => '사업자등록증',
        ]);

        if (! EmailVerificationController::isVerified($request, $data['email'])) {
            return back()->withErrors(['email' => '이메일 인증을 먼저 완료해 주세요.'])->withInput();
        }

        // 사업자등록증은 공개 폴더 밖에 저장하고, 본사 관리자만 내려받을 수 있게 한다
        $file = $request->file('license');
        $name = date('Ymd_His').'_'.Str::lower(Str::random(8)).'.'.strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs(self::LICENSE_DIR, $name, 'local');

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
            'business_address' => trim(
                ($data['postcode'] ? '('.$data['postcode'].') ' : '').$data['address1'].' '.($data['address2'] ?? '')
            ),
            'license_path' => $path,
            'status' => 'pending',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('home')->with('welcome',
            $user->name.'님, 협력사 가입 신청이 접수되었습니다. 본사 확인 후 승인되면 협력사 할인가로 구매하실 수 있습니다.');
    }

    /** 사업자등록증 내려받기 (본사 관리자 전용) */
    public function license(PartnerProfile $profile)
    {
        abort_unless(optional(Auth::user())->isHqAdmin(), 403);
        abort_unless($profile->license_path && Storage::disk('local')->exists($profile->license_path), 404);

        return Storage::disk('local')->download(
            $profile->license_path,
            $profile->company_name.'_사업자등록증.'.pathinfo($profile->license_path, PATHINFO_EXTENSION)
        );
    }
}
