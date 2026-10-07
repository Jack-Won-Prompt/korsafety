<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\PartnerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 초대 링크로 들어온 가입.
 * 초대 메일을 받은 사람이므로 이메일 인증은 거치지 않고, 가입 뒤 관리자 승인을 기다린다.
 */
class InviteRegisterController extends Controller
{
    private const LICENSE_DIR = 'partner-licenses';

    private function findInvitation(string $token): Invitation
    {
        $invitation = Invitation::where('token', $token)->first();

        abort_if(! $invitation, 404, '초대 링크를 찾을 수 없습니다.');
        abort_if($invitation->status === 'accepted', 410, '이미 가입을 마친 초대입니다. 로그인해 주세요.');
        abort_if($invitation->status === 'cancelled', 410, '취소된 초대입니다. 담당자에게 문의해 주세요.');
        abort_if(! $invitation->isUsable(), 410, '초대 기간이 지났습니다. 담당자에게 다시 요청해 주세요.');

        return $invitation;
    }

    public function show(string $token)
    {
        $invitation = $this->findInvitation($token);

        if (User::where('email', $invitation->email)->exists()) {
            return redirect()->route('login')->withErrors(['email' => '이미 가입된 이메일입니다. 로그인해 주세요.']);
        }

        return view('auth.invite-register', compact('invitation'));
    }

    public function register(Request $request, string $token)
    {
        $invitation = $this->findInvitation($token);
        $isPartner = $invitation->role === 'partner';

        $rules = [
            'name' => 'required|string|max:50',
            'phone' => 'required|string|max:30',
            'password' => 'required|min:6|confirmed',
            'postcode' => 'nullable|string|max:10',
            'address1' => ($isPartner ? 'required' : 'nullable').'|string|max:200',
            'address2' => 'nullable|string|max:200',
        ];
        if ($isPartner) {
            $rules += [
                'company_name' => 'required|string|max:150',
                'company_phone' => 'required|string|max:30',
                'company_fax' => 'nullable|string|max:30',
                'owner_name' => 'required|string|max:50',
                'license' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:8192',
            ];
        }

        $data = $request->validate($rules, [], [
            'name' => '이름', 'phone' => '휴대전화', 'password' => '비밀번호',
            'postcode' => '우편번호', 'address1' => '주소', 'address2' => '상세주소',
            'company_name' => '회사명', 'company_phone' => '회사 전화번호', 'company_fax' => '회사 팩스',
            'owner_name' => '대표자 이름', 'license' => '사업자등록증',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $invitation->email,          // 초대받은 주소로 고정
            'phone' => $data['phone'],
            'postcode' => $data['postcode'] ?? null,
            'address1' => $data['address1'] ?? null,
            'address2' => $data['address2'] ?? null,
            'role' => $isPartner ? 'partner' : 'customer',
            'password' => Hash::make($data['password']),
            'approval_status' => 'pending',          // 관리자 승인 전에는 로그인할 수 없다
        ]);

        if ($isPartner) {
            $file = $request->file('license');
            $name = date('Ymd_His').'_'.Str::lower(Str::random(8)).'.'.strtolower($file->getClientOriginalExtension());
            $path = $file->storeAs(self::LICENSE_DIR, $name, 'local');

            PartnerProfile::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'company_phone' => $data['company_phone'],
                'company_fax' => $data['company_fax'] ?? null,
                'owner_name' => $data['owner_name'],
                'business_address' => trim(
                    (($data['postcode'] ?? '') ? '('.$data['postcode'].') ' : '').$data['address1'].' '.($data['address2'] ?? '')
                ),
                'license_path' => $path,
                'status' => 'pending',
            ]);
        }

        $invitation->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'user_id' => $user->id,
        ]);

        return view('auth.invite-done', ['user' => $user, 'invitation' => $invitation]);
    }
}
