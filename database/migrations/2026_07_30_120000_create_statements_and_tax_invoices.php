<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 거래명세서 발행 이력 (PDF는 매번 생성 — 이 표는 감사 로그)
        Schema::create('order_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->unsignedInteger('seq')->default(1);            // 재발행 회차
            $table->string('file_name');
            $table->date('statement_date')->nullable();
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->string('action', 20)->default('download');    // download | email
            $table->string('sent_to')->nullable();                // 이메일 수신자
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'seq']);
        });

        // 세금계산서 (팝빌 연동, 시뮬레이트 기본)
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('mgt_key')->unique();                  // 문서관리번호
            $table->string('invoice_kind', 10)->default('tax');   // tax(과세) | plain(면세)
            $table->unsignedBigInteger('supply_amount')->default(0); // 공급가액
            $table->unsignedBigInteger('tax_amount')->default(0);    // 세액
            $table->unsignedBigInteger('total_amount')->default(0);  // 합계
            // 공급받는자(구매자) 스냅샷
            $table->string('receiver_corp_num')->nullable();     // 사업자번호
            $table->string('receiver_corp_name')->nullable();
            $table->string('receiver_ceo')->nullable();
            $table->string('receiver_email')->nullable();
            // 발행 상태
            $table->string('status', 15)->default('issued');     // issued | simulated | cancelled | failed
            $table->string('popbill_state')->nullable();
            $table->string('nts_confirm_num')->nullable();       // 국세청 승인번호
            $table->text('error_message')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
        Schema::dropIfExists('order_statements');
    }
};
