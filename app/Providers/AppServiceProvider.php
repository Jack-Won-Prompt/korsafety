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
                // 상단 메뉴는 대분류만, 소분류는 하위 메뉴로 펼친다
                $cats = Category::active()->roots()
                    ->with(['children' => fn ($q) => $q->where('is_active', true)])
                    ->orderBy('sort')->get();
            } catch (\Throwable $e) {
                $cats = collect();
            }
            $view->with('navCategories', $cats);
            $view->with('cartCount', array_sum(session()->get('cart', [])));
            // 유지보수 모드: 카테고리 링크 등을 비활성 표시하는 데 사용
            $view->with('maintenanceOn', Setting::bool('maintenance_mode'));
        });
    }
}
