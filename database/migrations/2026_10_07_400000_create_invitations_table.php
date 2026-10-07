<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 관리자가 보내는 회원 가입 초대 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $t) {
            $t->id();
            $t->string('email', 150)->index();
            $t->string('name', 50)->nullable();
            $t->string('company_name', 150)->nullable();
            $t->string('role', 20)->default('customer');   // customer | partner
            $t->string('token', 64)->unique();
            $t->string('status', 20)->default('sent');     // sent | accepted | cancelled | expired
            $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('accepted_at')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamps();

            $t->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};
