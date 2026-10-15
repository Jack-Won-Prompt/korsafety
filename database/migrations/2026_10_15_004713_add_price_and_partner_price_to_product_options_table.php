<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 상품 추가 옵션(사이즈·색상 등)에도 일반판매가와 협력사가를 각각 매길 수 있도록
     * product_options 표에 가격 칼럼을 추가한다.
     * 이 마이그레이션은 product_options 표 한 곳만 건드린다.
     */
    public function up(): void
    {
        // price(일반가) 칼럼이 이미 있는 환경과 없는 환경이 섞여 있을 수 있어
        // 각각 따로 존재 여부를 확인한 뒤 없는 것만 추가한다.
        if (! Schema::hasColumn('product_options', 'price')) {
            Schema::table('product_options', function (Blueprint $table) {
                // 이미 쌓여 있는 옵션 줄이 있으므로 nullable 로 둔다.
                // 값이 null 이면 종전처럼 상품 본체 가격을 따르도록 화면/로직에서 처리한다.
                $table->integer('price')->nullable()->comment('옵션별 일반판매가(null 이면 상품 기본가를 따름)');
            });
        }

        if (! Schema::hasColumn('product_options', 'partner_price')) {
            Schema::table('product_options', function (Blueprint $table) {
                // 협력사가 역시 기존 줄에는 값이 없으므로 nullable.
                // 금액은 원 단위 정수로만 쓰이므로 integer 로 잡는다.
                $table->integer('partner_price')->nullable()->comment('옵션별 협력사가(null 이면 상품 기본 협력사가를 따름)');
            });
        }
    }

    /**
     * 되돌리기. 이 마이그레이션이 더한 칼럼만 떨어뜨린다.
     */
    public function down(): void
    {
        if (Schema::hasColumn('product_options', 'partner_price')) {
            Schema::table('product_options', function (Blueprint $table) {
                // partner_price 는 이 마이그레이션에서 처음 생긴 칼럼이므로 그대로 지운다.
                $table->dropColumn('partner_price');
            });
        }

        // price 는 환경에 따라 이 마이그레이션 이전부터 있었을 수 있다.
        // 값이 하나라도 들어 있으면 원래부터 쓰던 칼럼으로 보고 건드리지 않는다.
        // (되돌리다가 운영 자료를 날리는 것을 막기 위한 안전장치)
        if (Schema::hasColumn('product_options', 'price')) {
            $hasData = DB::table('product_options')->whereNotNull('price')->exists();

            if (! $hasData) {
                Schema::table('product_options', function (Blueprint $table) {
                    $table->dropColumn('price');
                });
            }
        }
    }
};
