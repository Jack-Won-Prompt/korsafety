<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 재고 관리 사용 여부.
 * 스크랩으로 들어온 기존 상품은 재고를 세지 않으므로 false(기본)로 두고,
 * 관리자가 재고를 쓰기로 한 상품만 부족·품절 경고 대상에 넣는다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->boolean('track_stock')->default(false)->after('safety_stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropColumn('track_stock');
        });
    }
};
