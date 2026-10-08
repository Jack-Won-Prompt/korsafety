<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agent 작업 대기열 — 운영 오류와 SR 접수를 한 줄씩 담아 두고 순서대로 처리한다.
 * 별도 큐 서버 없이 이 표 하나로 접수·진행·결과를 모두 추적한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_tasks', function (Blueprint $t) {
            $t->id();
            $t->string('type', 20);                 // error | sr
            $t->unsignedBigInteger('ref_id');       // error_logs.id 또는 service_requests.id
            $t->string('dedupe_key', 64)->nullable()->index();  // 같은 원인 중복 접수 방지
            $t->json('payload')->nullable();

            $t->string('status', 20)->default('pending');  // pending | working | done | failed | skipped
            $t->unsignedTinyInteger('attempts')->default(0);
            $t->text('analysis')->nullable();       // Claude 분석 결과 (JSON 문자열)
            $t->text('result')->nullable();         // 사람이 읽을 처리 결과
            $t->string('commit_hash', 40)->nullable();
            $t->unsignedInteger('tokens')->default(0);
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->timestamps();

            $t->index(['status', 'created_at']);
            $t->index(['type', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_tasks');
    }
};
