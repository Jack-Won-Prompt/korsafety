<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TaxInvoice;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxInvoiceController extends Controller
{
    public function __construct(private TaxInvoiceIssueService $service)
    {
    }

    /** 세금계산서 발행 (주문 기반) */
    public function issue(Request $request, Order $order)
    {
        $data = $request->validate([
            'receiver_corp_num' => 'required|string|max:20',
            'receiver_corp_name' => 'required|string|max:120',
            'receiver_ceo' => 'nullable|string|max:60',
            'receiver_email' => 'nullable|email|max:120',
            'invoice_kind' => 'required|in:tax,plain',
        ], [], [
            'receiver_corp_num' => '사업자등록번호',
            'receiver_corp_name' => '상호',
            'invoice_kind' => '과세유형',
        ]);

        try {
            $inv = $this->service->issueForOrder($order, [
                'corp_num' => $data['receiver_corp_num'],
                'corp_name' => $data['receiver_corp_name'],
                'ceo' => $data['receiver_ceo'] ?? null,
                'email' => $data['receiver_email'] ?? null,
            ], $data['invoice_kind'], Auth::id());

            $msg = $inv->status === 'simulated'
                ? '세금계산서가 발행되었습니다. (시뮬레이트 모드 — 실제 국세청 발행 아님)'
                : '세금계산서가 발행되었습니다. 승인번호 '.$inv->nts_confirm_num;

            return back()->with('status', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', '발행 실패: '.$e->getMessage());
        }
    }

    /** 세금계산서 이력 */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $query = TaxInvoice::with('order')->latest('id');
        if ($status) {
            $query->where('status', $status);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('receiver_corp_name', 'like', "%$q%")
                    ->orWhere('receiver_corp_num', 'like', "%$q%")
                    ->orWhere('mgt_key', 'like', "%$q%")
                    ->orWhere('nts_confirm_num', 'like', "%$q%")
                    ->orWhereHas('order', fn ($o) => $o->where('order_no', 'like', "%$q%"));
            });
        }
        $invoices = $query->paginate(20)->withQueryString();

        $stats = [
            'issued' => TaxInvoice::whereIn('status', ['issued', 'simulated'])->count(),
            'cancelled' => TaxInvoice::where('status', 'cancelled')->count(),
            'failed' => TaxInvoice::where('status', 'failed')->count(),
            'amount' => (int) TaxInvoice::whereIn('status', ['issued', 'simulated'])->sum('total_amount'),
        ];

        return view('admin.tax-invoices.index', compact('invoices', 'stats', 'status', 'q'));
    }

    /** 세금계산서 취소 */
    public function cancel(TaxInvoice $taxInvoice)
    {
        try {
            $this->service->cancel($taxInvoice, '발행 취소', Auth::id());

            return back()->with('status', '세금계산서를 취소했습니다.');
        } catch (\Throwable $e) {
            return back()->with('error', '취소 실패: '.$e->getMessage());
        }
    }

    /** 팝빌 문서함 팝업 (실발행만) */
    public function popup(TaxInvoice $taxInvoice)
    {
        try {
            $url = $this->service->popupUrl($taxInvoice);
            if (! $url) {
                return back()->with('error', '시뮬레이트 발행 건은 팝빌 원본이 없습니다.');
            }

            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', '팝업 조회 실패: '.$e->getMessage());
        }
    }
}
