<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 실시간 채팅 문의 대화방
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('token', 40)->unique();          // 추측 불가한 공개 채널 키
            $table->string('name');                          // 문의자 이름
            $table->string('phone', 30);                     // 문의자 전화번호
            $table->string('status', 20)->default('open');   // open | closed
            $table->unsignedInteger('unread_admin')->default(0);    // 관리자 미확인 수
            $table->unsignedInteger('unread_customer')->default(0); // 고객 미확인 수
            $table->timestamp('last_message_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['status', 'last_message_at']);
        });

        // 대화 메시지
        Schema::create('inquiry_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->string('sender', 10);                    // customer | admin
            $table->text('body');
            $table->timestamps();
            $table->index(['inquiry_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_messages');
        Schema::dropIfExists('inquiries');
    }
};
