<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 상품 옵션 — 사이즈·색상 등 선택지와 추가 금액 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_options', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('group_name', 60)->nullable();   // 옵션 구분 (예: 사이즈)
            $t->string('name', 120);                    // 선택지 (예: 260mm)
            $t->integer('extra_price')->default(0);     // 기본가 대비 추가 금액 (음수 가능)
            $t->unsignedInteger('stock')->default(0);
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();

            $t->index(['product_id', 'sort']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_options');
    }
};
