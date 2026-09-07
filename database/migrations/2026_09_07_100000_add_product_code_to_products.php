<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 관리자가 직접 입력하는 상품코드.
 * external_no는 스크랩 원본 번호(시더가 매핑 키로 쓴다)라 건드리지 않고 별도 컬럼을 둔다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->string('product_code', 64)->nullable()->after('sku');
            $t->index('product_code');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->dropIndex(['product_code']);
            $t->dropColumn('product_code');
        });
    }
};
