<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 협력사(Agent) — 기업/병원 영업대행
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('status', ['pending', 'approved', 'suspended'])->default('pending');
            $table->decimal('commission_rate', 5, 2)->default(10.00); // 판매대금 대비 %
            $table->string('owner_name')->nullable();
            $table->string('business_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        // users 역할에 agent 추가 + agent_id
        DB::statement("ALTER TABLE users MODIFY role ENUM('customer','seller','hq_admin','agent') NOT NULL DEFAULT 'customer'");
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('seller_id')->constrained()->nullOnDelete();
        });

        // 거래처(기업/병원) — 협력사 소유
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained()->cascadeOnDelete();
            $table->string('name');                       // 기업/병원명
            $table->enum('type', ['company', 'hospital', 'etc'])->default('company');
            $table->string('contact_name')->nullable();   // 담당자
            $table->string('phone')->nullable();
            $table->string('business_no')->nullable();
            $table->string('address')->nullable();
            $table->text('memo')->nullable();
            $table->timestamps();
        });

        // orders 에 협력사/거래처/커미션 연동
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->after('agent_id')->constrained()->nullOnDelete();
            $table->decimal('commission_rate', 5, 2)->nullable()->after('total');
            $table->unsignedInteger('commission_amount')->default(0)->after('commission_rate');
            $table->timestamp('commission_paid_at')->nullable()->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
            $table->dropConstrainedForeignId('client_id');
            $table->dropColumn(['commission_rate', 'commission_amount', 'commission_paid_at']);
        });
        Schema::dropIfExists('clients');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
        });
        DB::statement("ALTER TABLE users MODIFY role ENUM('customer','seller','hq_admin') NOT NULL DEFAULT 'customer'");
        Schema::dropIfExists('agents');
    }
};
