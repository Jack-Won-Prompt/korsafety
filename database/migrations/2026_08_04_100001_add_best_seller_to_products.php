<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 홈 '베스트 셀러' 진열을 관리자가 직접 고르고 순서를 정할 수 있도록 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->boolean('is_best')->default(false)->after('is_active');
            $t->unsignedInteger('best_sort')->default(0)->after('is_best');
            $t->index(['is_best', 'best_sort']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropIndex(['is_best', 'best_sort']);
            $t->dropColumn(['is_best', 'best_sort']);
        });
    }
};
