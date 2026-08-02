<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 카테고리 2단계 트리(대분류 > 소분류)와 노출 토글 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            $t->foreignId('parent_id')->nullable()->after('id')->constrained('categories')->nullOnDelete();
            $t->boolean('is_active')->default(true)->after('sort');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $t) {
            $t->dropForeign(['parent_id']);
            $t->dropColumn(['parent_id', 'is_active']);
        });
    }
};
