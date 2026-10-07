<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** 협력사 회원(할인가 거래처) 역할 추가 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'role')) {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer','partner','seller','hq_admin','agent','purchaser') NOT NULL DEFAULT 'customer'");
    }

    public function down(): void
    {
        // 되돌리기 전에 협력사 회원을 일반 회원으로 바꿔 둔다 (값이 남아 있으면 ALTER 가 실패한다)
        DB::table('users')->where('role', 'partner')->update(['role' => 'customer']);
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('customer','seller','hq_admin','agent','purchaser') NOT NULL DEFAULT 'customer'");
    }
};
