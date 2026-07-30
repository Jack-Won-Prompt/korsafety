<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PolicyController extends Controller
{
    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    /** 계정 삭제 안내 (스토어 심사용 공개 페이지 + 로그인 시 삭제 폼) */
    public function accountDeletion()
    {
        return view('legal.account-deletion');
    }

    /** 실제 계정 삭제 처리 (로그인 고객) */
    public function destroyAccount(Request $request)
    {
        $user = Auth::user();

        // 관리자/판매자 등 업무 계정은 셀프 삭제 불가 (데이터 정합성)
        if (! $user->isCustomer()) {
            return back()->with('error', '업무용 계정은 고객센터를 통해 처리해야 합니다.');
        }

        $request->validate([
            'password' => 'required|string',
            'confirm' => 'accepted',
        ], [
            'confirm.accepted' => '계정 삭제에 동의해 주세요.',
        ], ['password' => '비밀번호']);

        if (! Hash::check($request->password, $user->password)) {
            return back()->with('error', '비밀번호가 일치하지 않습니다.');
        }

        DB::transaction(function () use ($user) {
            // 주문 이력은 보존하되 개인 연결만 해제(익명화)
            Order::where('user_id', $user->id)->update(['user_id' => null]);
            $user->delete();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('welcome', '계정이 삭제되었습니다. 그동안 이용해 주셔서 감사합니다.');
    }
}
