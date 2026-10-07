<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 에러 처리 상태 관리 — 담당자와 상태 변경 시각을 남긴다 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('error_logs', function (Blueprint $t) {
            if (! Schema::hasColumn('error_logs', 'assigned_to')) {
                $t->foreignId('assigned_to')->nullable()->after('resolved_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('error_logs', 'status_changed_at')) {
                $t->timestamp('status_changed_at')->nullable()->after('assigned_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('error_logs', function (Blueprint $t) {
            if (Schema::hasColumn('error_logs', 'assigned_to')) {
                $t->dropConstrainedForeignId('assigned_to');
            }
            if (Schema::hasColumn('error_logs', 'status_changed_at')) {
                $t->dropColumn('status_changed_at');
            }
        });
    }
};
