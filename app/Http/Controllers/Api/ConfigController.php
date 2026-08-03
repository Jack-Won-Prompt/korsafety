<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class ConfigController extends Controller
{
    /** 앱이 시작 시 읽어가는 사이트 설정 (관리자 사이트 설정과 동일한 값) */
    public function index()
    {
        return response()->json([
            // 'ask' = 가격 문의(가격 숨김) / 'price' = 판매가 노출
            'price_display_mode' => Setting::get('price_display_mode') === 'price' ? 'price' : 'ask',
            'contact_banner' => [
                'enabled' => Setting::bool('contact_banner_enabled'),
                'text' => (string) Setting::get('contact_banner_text'),
                'phone' => (string) Setting::get('contact_banner_phone'),
            ],
        ]);
    }
}
