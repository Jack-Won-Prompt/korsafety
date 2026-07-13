<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // 모바일 앱 고객 주문
            $table->foreignId('user_id')->nullable()->after('client_id')->constrained()->nullOnDelete();

            // 배송지
            $table->string('receiver_name')->nullable()->after('customer_phone');
            $table->string('postcode', 10)->nullable()->after('receiver_name');
            $table->string('address1')->nullable()->after('postcode');
            $table->string('address2')->nullable()->after('address1');
            $table->string('delivery_memo')->nullable()->after('address2');

            // 결제 (토스페이먼츠 등 PG)
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'cancelled', 'refunded'])
                ->default('pending')->after('status');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->string('payment_key')->nullable()->after('payment_method');
            $table->timestamp('paid_at')->nullable()->after('payment_key');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'receiver_name', 'postcode', 'address1', 'address2', 'delivery_memo',
                'payment_status', 'payment_method', 'payment_key', 'paid_at',
            ]);
        });
    }
};
