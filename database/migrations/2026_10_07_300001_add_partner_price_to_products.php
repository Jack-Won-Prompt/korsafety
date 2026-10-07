<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 협력사 회원 전용 할인가 — 비어 있으면 일반 판매가로 판다 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (! Schema::hasColumn('products', 'partner_price')) {
                $t->unsignedInteger('partner_price')->nullable()->after('sale_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            if (Schema::hasColumn('products', 'partner_price')) {
                $t->dropColumn('partner_price');
            }
        });
    }
};
