<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /** 앱 실행/로그인 시 FCM 토큰 등록 (기존 토큰이면 소유자만 갱신) */
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:255',
            'platform' => 'nullable|in:android,ios',
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? 'android',
                'last_used_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /** 로그아웃 시 토큰 해제 */
    public function destroy(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:255']);

        DeviceToken::where('token', $data['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(['ok' => true]);
    }
}
