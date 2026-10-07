<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 회원가입에서 받는 연락처·주소 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            foreach ([
                'phone' => fn () => $t->string('phone', 30)->nullable()->after('email'),
                'postcode' => fn () => $t->string('postcode', 10)->nullable()->after('phone'),
                'address1' => fn () => $t->string('address1', 200)->nullable()->after('postcode'),
                'address2' => fn () => $t->string('address2', 200)->nullable()->after('address1'),
            ] as $column => $add) {
                if (! Schema::hasColumn('users', $column)) {
                    $add();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            foreach (['phone', 'postcode', 'address1', 'address2'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $t->dropColumn($column);
                }
            }
        });
    }
};
