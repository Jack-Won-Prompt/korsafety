<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 가입 승인 — 초대로 가입한 회원은 관리자가 승인해야 로그인할 수 있다 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'approval_status')) {
                // none = 승인 절차 없이 바로 이용 (기존 회원·일반 가입)
                $t->string('approval_status', 20)->default('none')->after('suspended_at')->index();
            }
            if (! Schema::hasColumn('users', 'approved_at')) {
                $t->timestamp('approved_at')->nullable()->after('approval_status');
            }
            if (! Schema::hasColumn('users', 'approved_by')) {
                $t->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'approved_by')) { $t->dropConstrainedForeignId('approved_by'); }
            foreach (['approval_status', 'approved_at'] as $c) {
                if (Schema::hasColumn('users', $c)) { $t->dropColumn($c); }
            }
        });
    }
};
