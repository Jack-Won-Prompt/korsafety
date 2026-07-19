<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 구매 대행자(Purchasing Agent) — 구매자(소매처)를 대신해 구매, 캐쉬백 수령
        Schema::create('purchasers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->decimal('cashback_rate', 5, 2)->default(5.00); // 총 주문금액 대비 %
            $table->string('owner_name')->nullable();
            $table->string('business_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        // users 역할에 purchaser 추가 + purchaser_id
        DB::statement("ALTER TABLE users MODIFY role ENUM('customer','seller','hq_admin','agent','purchaser') NOT NULL DEFAULT 'customer'");
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('purchaser_id')->nullable()->after('agent_id')->constrained()->nullOnDelete();
        });

        // 구매자(소매처) — 구매 대행자 소유 (1:N)
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchaser_id')->constrained()->cascadeOnDelete();
            $table->string('shop_name');                  // 소매처명
            $table->string('name');                       // 구매자 이름
            $table->string('business_no')->nullable();    // 구매자 사업자번호
            $table->string('phone')->nullable();          // 구매자 전화번호
            $table->string('address')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        // orders 에 구매대행자/구매자/캐쉬백 연동
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('purchaser_id')->nullable()->after('client_id')->constrained()->nullOnDelete();
            $table->foreignId('buyer_id')->nullable()->after('purchaser_id')->constrained()->nullOnDelete();
            $table->decimal('cashback_rate', 5, 2)->nullable()->after('commission_paid_at');
            $table->unsignedInteger('cashback_amount')->default(0)->after('cashback_rate');
            $table->timestamp('cashback_paid_at')->nullable()->after('cashback_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchaser_id');
            $table->dropConstrainedForeignId('buyer_id');
            $table->dropColumn(['cashback_rate', 'cashback_amount', 'cashback_paid_at']);
        });
        Schema::dropIfExists('buyers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchaser_id');
        });
        DB::statement("ALTER TABLE users MODIFY role ENUM('customer','seller','hq_admin','agent') NOT NULL DEFAULT 'customer'");
        Schema::dropIfExists('purchasers');
    }
};
