<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 회원 이용 정지 — 값이 있으면 웹·앱 모두 로그인할 수 없다 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'suspended_at')) {
                $t->timestamp('suspended_at')->nullable()->after('role')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            if (Schema::hasColumn('users', 'suspended_at')) {
                $t->dropColumn('suspended_at');
            }
        });
    }
};
