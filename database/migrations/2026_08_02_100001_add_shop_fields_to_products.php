<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 쇼핑몰 수준 상품 관리에 필요한 필드 (SKU · 재고 · 원가 · 노출 · 진열순서 · 상세설명) */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->string('sku', 64)->nullable()->after('slug');
            $t->unsignedInteger('cost_price')->nullable()->after('price');   // 매입가 (마진 계산용)
            $t->unsignedInteger('stock')->default(0)->after('sale_price');
            $t->unsignedInteger('safety_stock')->default(0)->after('stock'); // 재고 부족 경고 기준
            $t->boolean('is_active')->default(true)->after('is_soldout');    // 쇼핑몰 노출 여부
            $t->unsignedInteger('sort')->default(0)->after('is_active');     // 진열 순서 (작을수록 앞)
            $t->text('description')->nullable()->after('main_image');
            $t->index('sku');
            $t->index(['is_active', 'is_soldout']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropIndex(['is_active', 'is_soldout']);
            $t->dropIndex(['sku']);
            $t->dropColumn(['sku', 'cost_price', 'stock', 'safety_stock', 'is_active', 'sort', 'description']);
        });
    }
};
