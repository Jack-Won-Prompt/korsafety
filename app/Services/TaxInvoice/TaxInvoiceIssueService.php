<?php

namespace App\Services\TaxInvoice;

use App\Models\Order;
use App\Models\TaxInvoice;
use App\Services\Popbill\PopbillTaxinvoiceService;
use Illuminate\Support\Str;
use RuntimeException;

class TaxInvoiceIssueService
{
    public function __construct(private PopbillTaxinvoiceService $popbill)
    {
    }

    /**
     * 주문 기반 세금계산서 발행.
     *
     * @param  array  $receiver  ['corp_num','corp_name','ceo','email']
     */
    public function issueForOrder(Order $order, array $receiver, string $kind = 'tax', ?int $issuedBy = null, string $memo = ''): TaxInvoice
    {
        // 중복 발행 방지
        if ($order->taxInvoices()->whereIn('status', ['issued', 'simulated'])->exists()) {
            throw new RuntimeException('이미 발행된 세금계산서가 있습니다. 취소 후 재발행하세요.');
        }

        $amounts = $this->amounts($order, $kind);
        $mgtKey = $this->mgtKey($order);

        $snapshot = [
            'order_id' => $order->id,
            'mgt_key' => $mgtKey,
            'invoice_kind' => $kind,
            'supply_amount' => $amounts['supply'],
            'tax_amount' => $amounts['tax'],
            'total_amount' => $amounts['total'],
            'receiver_corp_num' => preg_replace('/[^0-9]/', '', $receiver['corp_num'] ?? ''),
            'receiver_corp_name' => $receiver['corp_name'] ?? ($order->customer_name ?: null),
            'receiver_ceo' => $receiver['ceo'] ?? null,
            'receiver_email' => $receiver['email'] ?? null,
            'issued_by' => $issuedBy,
        ];

        // ── 시뮬레이트 모드: 팝빌 미호출, 내부 발행 처리 ──
        if (config('popbill.simulate', true)) {
            return TaxInvoice::create($snapshot + [
                'status' => 'simulated',
                'popbill_state' => '시뮬레이트(실발행 아님)',
                'nts_confirm_num' => 'SIM-'.strtoupper(Str::random(12)),
                'issued_at' => now(),
            ]);
        }

        // ── 실제 발행 ──
        try {
            $corpNum = preg_replace('/[^0-9]/', '', config('popbill.corp_num'));
            $invoice = $this->buildInvoice($order, $mgtKey, $corpNum, $amounts, $kind, $snapshot);
            $result = $this->popbill->registIssue($corpNum, $invoice, config('popbill.user_id') ?: null, false, $memo);
            $info = $this->popbill->getInfo($corpNum, $mgtKey);

            return TaxInvoice::create($snapshot + [
                'status' => 'issued',
                'popbill_state' => $info->stateMemo ?? ($result->message ?? '발행완료'),
                'nts_confirm_num' => $info->ntsconfirmNum ?? null,
                'issued_at' => now(),
            ]);
        } catch (\Throwable $e) {
            TaxInvoice::create($snapshot + [
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** 세금계산서 취소 */
    public function cancel(TaxInvoice $invoice, string $memo = '발행 취소', ?int $userId = null): TaxInvoice
    {
        if ($invoice->status === 'simulated') {
            $invoice->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return $invoice;
        }
        if ($invoice->status !== 'issued') {
            throw new RuntimeException('발행완료 상태만 취소할 수 있습니다.');
        }

        $corpNum = preg_replace('/[^0-9]/', '', config('popbill.corp_num'));
        $this->popbill->cancelIssue($corpNum, $invoice->mgt_key, $memo, config('popbill.user_id') ?: null);
        $invoice->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return $invoice;
    }

    /** 팝빌 문서함 팝업 URL (실발행만) */
    public function popupUrl(TaxInvoice $invoice): ?string
    {
        if ($invoice->status === 'simulated') {
            return null;
        }
        $corpNum = preg_replace('/[^0-9]/', '', config('popbill.corp_num'));

        return $this->popbill->getPopUpUrl($corpNum, $invoice->mgt_key, config('popbill.user_id') ?: null);
    }

    /** 공급가액/세액 (부가세 포함 단가 기준) */
    public function amounts(Order $order, string $kind): array
    {
        $total = (int) $order->total;
        if ($kind === 'plain') {              // 면세
            return ['total' => $total, 'supply' => $total, 'tax' => 0];
        }
        $supply = (int) round($total / 1.1);  // 과세

        return ['total' => $total, 'supply' => $supply, 'tax' => $total - $supply];
    }

    private function mgtKey(Order $order): string
    {
        $base = Str::limit(preg_replace('/[^A-Za-z0-9]/', '', $order->order_no), 20, '');
        $n = $order->taxInvoices()->count() + 1;

        return $base.'-'.$n;
    }

    private function buildInvoice(Order $order, string $mgtKey, string $corpNum, array $amounts, string $kind, array $snapshot)
    {
        $s = config('popbill.supplier');
        $inv = $this->popbill->newInvoice();
        $inv->writeDate = now()->format('Ymd');
        $inv->chargeDirection = '정과금';
        $inv->issueType = '정발행';
        $inv->purposeType = '영수';
        $inv->taxType = $kind === 'plain' ? '면세' : '과세';

        // 공급자
        $inv->invoicerCorpNum = $corpNum;
        $inv->invoicerMgtKey = $mgtKey;
        $inv->invoicerCorpName = $s['corp_name'];
        $inv->invoicerCEOName = $s['ceo_name'];
        $inv->invoicerAddr = $s['addr'];
        $inv->invoicerBizClass = $s['biz_class'];
        $inv->invoicerBizType = $s['biz_type'];
        $inv->invoicerTEL = $s['tel'];
        $inv->invoicerEmail = $s['email'];

        // 공급받는자
        $inv->invoiceeType = '사업자';
        $inv->invoiceeCorpNum = $snapshot['receiver_corp_num'];
        $inv->invoiceeCorpName = $snapshot['receiver_corp_name'];
        $inv->invoiceeCEOName = $snapshot['receiver_ceo'] ?: $snapshot['receiver_corp_name'];
        $inv->invoiceeEmail1 = $snapshot['receiver_email'];

        // 금액
        $inv->supplyCostTotal = (string) $amounts['supply'];
        $inv->taxTotal = (string) $amounts['tax'];
        $inv->totalAmount = (string) $amounts['total'];

        // 품목 (대표 1줄 집계)
        $first = $order->items->first();
        $name = $first->product_name ?? '상품';
        if ($order->items->count() > 1) {
            $name = Str::limit($name, 40, '').' 외 '.($order->items->count() - 1).'건';
        }
        $d = $this->popbill->newDetail();
        $d->serialNum = 1;
        $d->purchaseDT = now()->format('Ymd');
        $d->itemName = Str::limit($name, 90, '');
        $d->qty = '1';
        $d->supplyCost = (string) $amounts['supply'];
        $d->tax = (string) $amounts['tax'];
        $inv->detailList = [$d];

        return $inv;
    }
}
