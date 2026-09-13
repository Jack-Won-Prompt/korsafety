<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 서버 에러 이력 — 보고된 예외를 유형별로 모아 해결/미해결 상태로 관리 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('error_logs', function (Blueprint $t) {
            $t->id();
            $t->char('fingerprint', 40);                 // 같은 에러 묶음 키 (클래스 + 위치 + 정규화한 메시지)
            $t->string('type', 20);                      // database | php | http | external | application
            $t->string('source', 10)->default('web');    // web | api | console
            $t->string('exception_class');
            $t->text('message');
            $t->string('code', 50)->nullable();
            $t->string('file', 500)->nullable();         // 앱 코드 기준 발생 위치 (base_path 제외)
            $t->unsignedInteger('line')->nullable();
            $t->mediumText('trace')->nullable();

            // 마지막 발생 시점의 요청 정보
            $t->string('url', 1000)->nullable();
            $t->string('method', 10)->nullable();
            $t->string('ip_address', 45)->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('user_agent', 512)->nullable();
            $t->text('input')->nullable();               // 민감 정보를 가린 요청 파라미터 (JSON)

            $t->unsignedInteger('occurrences')->default(1);
            $t->timestamp('first_seen_at')->nullable();
            $t->timestamp('last_seen_at')->nullable();

            $t->string('status', 20)->default('unresolved');  // unresolved | resolved
            $t->timestamp('resolved_at')->nullable();
            $t->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('resolution_note')->nullable();
            $t->timestamps();

            $t->index(['status', 'last_seen_at']);
            $t->index(['fingerprint', 'status']);
            $t->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
