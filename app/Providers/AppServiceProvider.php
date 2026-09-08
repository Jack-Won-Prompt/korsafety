<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Setting;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 주문 상태 변경 → 앱 푸시 알림
        Order::observe(OrderObserver::class);

        // dompdf 폰트 캐시 디렉터리(쓰기 가능) 보장 — 웹서버 사용자가 생성
        $fontDir = storage_path('app/dompdf');
        if (! is_dir($fontDir)) {
            @mkdir($fontDir, 0775, true);
        }

        // Share navigation categories and cart count with every view.
        // 오류 페이지(404·500 등)도 이 컴포저를 거치므로, DB·세션이 죽어도
        // 화면 자체는 뜨도록 각 조회의 실패를 흡수하고 기본값을 쓴다.
        View::composer('*', function ($view) {
            $cats = collect();
            $cartCount = 0;
            $maintenanceOn = false;
            $signupOn = true;

            try {
                // 상단 메뉴는 대분류, 하위 메뉴로 중·소분류를 펼친다
                $cats = Category::active()->roots()
                    ->with(['children' => fn ($q) => $q->where('is_active', true)->with(['children' => fn ($q2) => $q2->where('is_active', true)])])
                    ->orderBy('sort')->get();
            } catch (\Throwable $e) {
                // 카테고리를 못 읽으면 메뉴만 비운다
            }

            try {
                $cartCount = array_sum(session()->get('cart', []));   // 세션 드라이버가 DB라 실패할 수 있다
            } catch (\Throwable $e) {
            }

            try {
                $maintenanceOn = Setting::bool('maintenance_mode');   // 유지보수 모드: 카테고리 링크 비활성 표시
                $signupOn = Setting::bool('signup_enabled');          // 회원가입·신청 링크 노출 여부
            } catch (\Throwable $e) {
            }

            $view->with('navCategories', $cats);
            $view->with('cartCount', $cartCount);
            $view->with('maintenanceOn', $maintenanceOn);
            $view->with('signupOn', $signupOn);
        });
    }
}
