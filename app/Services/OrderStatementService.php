<?php

namespace App\Services;

use App\Mail\OrderStatementMail;
use App\Models\Order;
use App\Models\OrderStatement;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\HeaderUtils;

class OrderStatementService
{
    /** 거래명세서 PDF 생성 (DomPDF) */
    public function pdf(Order $order, ?int $seq = null)
    {
        $order->loadMissing('items', 'user');

        return Pdf::loadView('print.order-statement', [
            'order' => $order,
            'statementDate' => $order->paid_at ?: $order->created_at,
            'seq' => $seq ?? ($this->lastSeq($order) + 1),
            'amounts' => $this->amounts($order),
        ])->setPaper('a4');
    }

    /** 미리보기 — 브라우저 인라인 PDF, 로그 남기지 않음 */
    public function preview(Order $order)
    {
        return $this->pdf($order)->stream('statement_'.$order->order_no.'.pdf');
    }

    /** HTML 인쇄 페이지 (브라우저 인쇄용) */
    public function htmlView(Order $order)
    {
        $order->loadMissing('items', 'user');

        return view('print.order-statement', [
            'order' => $order,
            'statementDate' => $order->paid_at ?: $order->created_at,
            'seq' => $this->lastSeq($order) + 1,
            'amounts' => $this->amounts($order),
        ]);
    }

    /** 다운로드 — 파일 첨부, 이력 기록 */
    public function download(Order $order, ?int $issuedBy = null)
    {
        $seq = $this->lastSeq($order) + 1;
        $pdf = $this->pdf($order, $seq);
        $name = $this->fileName($order, $seq);
        $this->log($order, $seq, $name, 'download', null, $issuedBy);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT, $name, 'statement_'.$order->order_no.'.pdf'
            ),
        ]);
    }

    /** 이메일 발송 (PDF 첨부) — 이력 기록 */
    public function email(Order $order, ?string $to = null, ?int $issuedBy = null): array
    {
        $to = $to ?: ($order->user?->email);
        if (! $to) {
            return ['ok' => false, 'message' => '수신 이메일이 없습니다.', 'to' => null];
        }

        $seq = $this->lastSeq($order) + 1;
        $name = $this->fileName($order, $seq);

        try {
            $pdfData = $this->pdf($order, $seq)->output();
            Mail::to($to)->send(new OrderStatementMail($order, $pdfData, $name));
            $this->log($order, $seq, $name, 'email', $to, $issuedBy);

            return ['ok' => true, 'message' => $to.' 로 거래명세서를 발송했습니다.', 'to' => $to];
        } catch (\Throwable $e) {
            $this->log($order, $seq, $name, 'email', $to, $issuedBy, $e->getMessage());

            return ['ok' => false, 'message' => '이메일 발송 실패: '.$e->getMessage(), 'to' => $to];
        }
    }

    /** 공급가액/세액 계산 (부가세 포함 단가 기준) */
    public function amounts(Order $order): array
    {
        $total = (int) $order->total;
        $supply = (int) round($total / 1.1);
        $tax = $total - $supply;

        return ['total' => $total, 'supply' => $supply, 'tax' => $tax];
    }

    private function lastSeq(Order $order): int
    {
        return (int) OrderStatement::where('order_id', $order->id)->max('seq');
    }

    private function fileName(Order $order, int $seq): string
    {
        $buyer = $order->customer_name ?: ($order->receiver_name ?: '고객');
        $buyer = preg_replace('/[\/\\\\:*?"<>|]+/', '', $buyer);
        $suffix = $seq > 1 ? '_'.$seq.'회차' : '';

        return '거래명세서_'.$buyer.'_'.$order->order_no.$suffix.'.pdf';
    }

    private function log(Order $order, int $seq, string $name, string $action, ?string $to = null, ?int $issuedBy = null, ?string $error = null): void
    {
        OrderStatement::create([
            'order_id' => $order->id,
            'seq' => $seq,
            'file_name' => $name,
            'statement_date' => optional($order->paid_at ?: $order->created_at)->toDateString(),
            'total_amount' => (int) $order->total,
            'action' => $action,
            'sent_to' => $to,
            'issued_by' => $issuedBy,
            'error_message' => $error,
        ]);
    }
}
