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
        View::composer('*', function ($view) {
            try {
                // 상단 메뉴는 대분류, 하위 메뉴로 중·소분류를 펼친다
                $cats = Category::active()->roots()
                    ->with(['children' => fn ($q) => $q->where('is_active', true)->with(['children' => fn ($q2) => $q2->where('is_active', true)])])
                    ->orderBy('sort')->get();
            } catch (\Throwable $e) {
                $cats = collect();
            }
            $view->with('navCategories', $cats);
            $view->with('cartCount', array_sum(session()->get('cart', [])));
            // 유지보수 모드: 카테고리 링크 등을 비활성 표시하는 데 사용
            $view->with('maintenanceOn', Setting::bool('maintenance_mode'));
            // 회원가입 노출 여부 (헤더·모바일 메뉴·로그인 화면의 가입 링크)
            $view->with('signupOn', Setting::bool('signup_enabled'));
        });
    }
}
