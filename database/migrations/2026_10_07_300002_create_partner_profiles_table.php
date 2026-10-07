<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 협력사 회원(할인가 거래처) 가입 정보 — 본사 승인 후 할인가가 적용된다 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_profiles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('company_name', 150);           // 회사명
            $t->string('company_phone', 30);           // 회사 전화번호
            $t->string('company_fax', 30)->nullable(); // 회사 팩스 (선택)
            $t->string('owner_name', 50);              // 대표자 이름
            $t->string('business_address', 300);       // 사업장 주소
            $t->string('license_path', 300);           // 사업자등록증 파일 (웹에서 직접 열 수 없는 위치)
            $t->string('status', 20)->default('pending');   // pending | approved | rejected
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('reject_reason', 300)->nullable();
            $t->timestamps();

            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_profiles');
    }
};
