<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Setting;
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
        // dompdf 폰트 캐시 디렉터리(쓰기 가능) 보장 — 웹서버 사용자가 생성
        $fontDir = storage_path('app/dompdf');
        if (! is_dir($fontDir)) {
            @mkdir($fontDir, 0775, true);
        }

        // Share navigation categories and cart count with every view.
        View::composer('*', function ($view) {
            try {
                $cats = Category::active()->orderBy('sort')->get();
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
