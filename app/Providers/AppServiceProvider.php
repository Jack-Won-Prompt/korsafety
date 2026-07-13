<?php

namespace App\Providers;

use App\Models\Category;
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
        // Share navigation categories and cart count with every view.
        View::composer('*', function ($view) {
            try {
                $cats = Category::orderBy('sort')->get();
            } catch (\Throwable $e) {
                $cats = collect();
            }
            $view->with('navCategories', $cats);
            $view->with('cartCount', array_sum(session()->get('cart', [])));
        });
    }
}
