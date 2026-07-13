<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartnerController extends Controller
{
    public function showApply()
    {
        return view('partner.apply');
    }

    public function apply(Request $request)
    {
        $data = $request->validate([
            'store_name' => 'required|string|max:100',
            'owner_name' => 'required|string|max:50',
            'business_no' => 'required|string|max:30',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ], [], [
            'store_name' => '상호명', 'owner_name' => '대표자명', 'business_no' => '사업자번호',
            'phone' => '연락처', 'email' => '이메일', 'password' => '비밀번호',
        ]);

        $slug = Str::slug($data['store_name']);
        if ($slug === '' || Seller::where('slug', $slug)->exists()) {
            $slug = 'store-'.Str::lower(Str::random(6));
        }

        $seller = Seller::create([
            'name' => $data['store_name'],
            'slug' => $slug,
            'status' => 'pending',
            'owner_name' => $data['owner_name'],
            'business_no' => $data['business_no'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        User::create([
            'name' => $data['store_name'],
            'email' => $data['email'],
            'role' => 'seller',
            'seller_id' => $seller->id,
            'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('manage.login')
            ->with('status', '입점 신청이 완료되었습니다. 본사 승인 후 로그인하실 수 있습니다.');
    }

    // ---- 협력사(Agent) 신청 ----
    public function showAgentApply()
    {
        return view('partner.agent-apply');
    }

    public function agentApply(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:100',
            'owner_name' => 'required|string|max:50',
            'business_no' => 'required|string|max:30',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
        ], [], [
            'company_name' => '협력사명', 'owner_name' => '대표자명', 'business_no' => '사업자번호',
            'phone' => '연락처', 'email' => '이메일', 'password' => '비밀번호',
        ]);

        $slug = Str::slug($data['company_name']);
        if ($slug === '' || Agent::where('slug', $slug)->exists()) {
            $slug = 'agent-'.Str::lower(Str::random(6));
        }

        $agent = Agent::create([
            'name' => $data['company_name'], 'slug' => $slug, 'status' => 'pending',
            'commission_rate' => 10, 'owner_name' => $data['owner_name'],
            'business_no' => $data['business_no'], 'phone' => $data['phone'], 'email' => $data['email'],
        ]);

        User::create([
            'name' => $data['company_name'], 'email' => $data['email'],
            'role' => 'agent', 'agent_id' => $agent->id, 'password' => Hash::make($data['password']),
        ]);

        return redirect()->route('manage.login')
            ->with('status', '협력사 신청이 완료되었습니다. 본사 승인 후 로그인하실 수 있습니다.');
    }
}
