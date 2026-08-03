<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 웹 방문 이력 — 제품 검색어와 제품 상세 진입 기록 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_logs', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20);                     // search | product
            $t->string('keyword')->nullable();          // 검색어
            $t->unsignedBigInteger('product_id')->nullable();
            $t->string('product_name')->nullable();     // 상품이 삭제돼도 남도록 스냅샷
            $t->unsignedInteger('result_count')->nullable();  // 검색 결과 건수
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('session_id', 64)->nullable();   // 비회원 방문자 구분
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent', 512)->nullable();
            $t->string('referer', 512)->nullable();
            $t->timestamp('created_at')->nullable();

            $t->index(['type', 'created_at']);
            $t->index('product_id');
            $t->index('keyword');
            $t->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_logs');
    }
};
