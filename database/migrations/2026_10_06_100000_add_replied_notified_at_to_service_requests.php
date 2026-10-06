<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 담당자 답변 등록 안내 메일을 마지막으로 보낸 시각 — 상세 화면 발송 이력 표시용 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $t) {
            $t->timestamp('replied_notified_at')->nullable()->after('resolved_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $t) {
            $t->dropColumn('replied_notified_at');
        });
    }
};
