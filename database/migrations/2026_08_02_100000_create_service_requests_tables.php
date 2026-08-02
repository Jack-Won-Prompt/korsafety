<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** SR(Service Request) — 관리 콘솔 사용자가 올리는 요청/장애 접수와 담당자 답변 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_requests', function (Blueprint $t) {
            $t->id();
            $t->string('sr_no', 24)->unique();                 // SR260802ABCD
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();  // 요청자
            $t->string('requester_role', 20)->nullable();      // 접수 시점의 역할 스냅샷
            $t->string('title');
            $t->string('category', 20)->default('etc');        // system|product|order|settlement|account|etc
            $t->string('priority', 10)->default('normal');     // low|normal|high|urgent
            $t->string('status', 20)->default('open');         // open|in_progress|resolved|closed
            $t->text('content');
            $t->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $t->unsignedInteger('reply_count')->default(0);
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();

            $t->index(['status', 'created_at']);
        });

        Schema::create('service_request_replies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('service_request_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->boolean('is_staff')->default(false);           // 본사 담당자 답변 여부
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_request_replies');
        Schema::dropIfExists('service_requests');
    }
};
