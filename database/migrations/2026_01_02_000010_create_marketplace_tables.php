<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 판매점(스토어) — 본사 직영도 하나의 스토어(is_hq)로 취급
        Schema::create('sellers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_hq')->default(false);
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->string('business_no')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // 수수료 %
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        // users 역할 확장
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['customer', 'seller', 'hq_admin'])->default('customer')->after('email');
            $table->foreignId('seller_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        // 상품 소유(스토어)
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('seller_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedInteger('external_no')->nullable()->change(); // 판매점 등록 상품은 없음
        });

        // 주문 / 주문상품 (매출 대시보드용)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->unsignedInteger('total')->default(0);
            $table->enum('status', ['pending', 'paid', 'shipped', 'done', 'cancelled'])->default('paid');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('seller_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('price');
            $table->unsignedInteger('qty');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_id');
            $table->dropColumn('role');
        });
        Schema::dropIfExists('sellers');
    }
};
