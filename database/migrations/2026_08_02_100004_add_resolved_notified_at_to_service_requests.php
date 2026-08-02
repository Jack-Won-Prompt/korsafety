<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 처리완료 안내 메일 발송 시각 — 상태를 오가도 메일이 중복 발송되지 않도록 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_requests', function (Blueprint $t) {
            $t->timestamp('resolved_notified_at')->nullable()->after('closed_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $t) {
            $t->dropColumn('resolved_notified_at');
        });
    }
};
