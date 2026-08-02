<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 상세 설명이 리치 에디터 HTML이 되면서 TEXT(64KB)로는 부족할 수 있어 MEDIUMTEXT로 확장 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->mediumText('description')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $t) {
            $t->text('description')->nullable()->change();
        });
    }
};
