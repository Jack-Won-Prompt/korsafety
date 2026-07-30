<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillTaxinvoice;
use Linkhub\Popbill\Taxinvoice;
use Linkhub\Popbill\TaxinvoiceDetail;
use RuntimeException;

/**
 * 팝빌 전자세금계산서 SDK 얇은 래퍼.
 * 실제 발행(시뮬레이트 아님)일 때만 SDK를 지연 생성한다.
 */
class PopbillTaxinvoiceService
{
    private ?PopbillTaxinvoice $client = null;

    private function client(): PopbillTaxinvoice
    {
        if ($this->client) {
            return $this->client;
        }
        if (! defined('LINKHUB_COMM_MODE')) {
            define('LINKHUB_COMM_MODE', config('popbill.CommMode', 'CURL'));
        }

        $c = new PopbillTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));
        $c->IsTest(config('popbill.IsTest', true));
        $c->IPRestrictOnOff(config('popbill.IPRestrictOnOff', true));
        $c->UseStaticIP(config('popbill.UseStaticIP', false));
        $c->UseLocalTimeYN(config('popbill.UseLocalTimeYN', true));

        return $this->client = $c;
    }

    public function newInvoice(): Taxinvoice
    {
        return new Taxinvoice();
    }

    public function newDetail(): TaxinvoiceDetail
    {
        return new TaxinvoiceDetail();
    }

    /** 즉시발행(등록+발행) */
    public function registIssue(string $corpNum, Taxinvoice $invoice, ?string $userId, bool $writeSpecification, string $memo = '')
    {
        try {
            return $this->client()->RegistIssue($corpNum, $invoice, $userId, $writeSpecification, false, $memo);
        } catch (PopbillException $e) {
            throw new RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getInfo(string $corpNum, string $mgtKey)
    {
        try {
            return $this->client()->GetInfo($corpNum, 'SELL', $mgtKey);
        } catch (PopbillException $e) {
            throw new RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function cancelIssue(string $corpNum, string $mgtKey, string $memo, ?string $userId)
    {
        try {
            return $this->client()->CancelIssue($corpNum, 'SELL', $mgtKey, $memo, $userId);
        } catch (PopbillException $e) {
            throw new RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getPopUpUrl(string $corpNum, string $mgtKey, ?string $userId): string
    {
        try {
            return $this->client()->GetPopUpURL($corpNum, 'SELL', $mgtKey, $userId);
        } catch (PopbillException $e) {
            throw new RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), $e->getCode(), $e);
        }
    }
}
