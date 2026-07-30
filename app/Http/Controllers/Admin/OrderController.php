<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Picqer\Barcode\BarcodeGeneratorPNG;

class OrderController extends Controller
{
    /** 전체 주문 목록 (HQ) + 검색 필터 */
    public function index(Request $request)
    {
        $orders = $this->filtered($request)->paginate(30)->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'q' => trim((string) $request->query('q', '')),
            'status' => $request->query('status'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ]);
    }

    /** 주문 상세 — 거래명세서 · 세금계산서 액션 */
    public function show(Order $order)
    {
        $order->load('items', 'user', 'statements.issuer', 'taxInvoices');

        return view('admin.orders.show', compact('order'));
    }

    /**
     * 피킹 리스트 — 선택 주문(체크) 기반 통합 출력.
     * format=pdf(다운로드) | print(PDA/브라우저 인쇄).
     * ids 미지정 시 현재 검색 필터 결과 전체.
     */
    public function picking(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $format = $request->input('format', 'print');

        $query = Order::with('items')->orderBy('id');
        if (! empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            $query = $this->filtered($request, $query);   // 선택 없으면 필터 결과 전체
        }
        $orders = $query->get();

        if ($orders->isEmpty()) {
            return back()->with('error', '선택된 주문이 없습니다.');
        }

        // 바코드 생성 (GD·패키지 미비 시에도 500 없이 바코드만 생략)
        try {
            $gen = new BarcodeGeneratorPNG();
        } catch (\Throwable $e) {
            $gen = null;
        }
        $barcode = function (string $value) use ($gen): ?string {
            if (! $gen) {
                return null;
            }
            try {
                return 'data:image/png;base64,'.base64_encode($gen->getBarcode($value, $gen::TYPE_CODE_128, 2, 42));
            } catch (\Throwable $e) {
                return null;
            }
        };

        // 품목별 집계 (product_id 우선, 없으면 상품명)
        $agg = [];
        foreach ($orders as $o) {
            foreach ($o->items as $it) {
                $key = $it->product_id ?: 'n:'.$it->product_name;
                if (! isset($agg[$key])) {
                    $agg[$key] = [
                        'name' => $it->product_name,
                        'code' => $it->product_id ? 'P'.$it->product_id : null,
                        'qty' => 0, 'orders' => 0,
                    ];
                }
                $agg[$key]['qty'] += (int) $it->qty;
                $agg[$key]['orders']++;
            }
        }
        uasort($agg, fn ($a, $b) => $b['qty'] <=> $a['qty']);
        foreach ($agg as &$row) {
            $row['barcode'] = $row['code'] ? $barcode($row['code']) : null;
        }
        unset($row);

        // 주문번호 바코드 (PDA 스캔용)
        $orderBarcodes = [];
        foreach ($orders as $o) {
            $orderBarcodes[$o->id] = $barcode($o->order_no);
        }

        $data = [
            'orders' => $orders,
            'agg' => $agg,
            'orderBarcodes' => $orderBarcodes,
            'printedAt' => now(),
            'totalItems' => array_sum(array_column($agg, 'qty')),
        ];

        if ($format === 'pdf') {
            $name = '피킹리스트_'.now()->format('Ymd_His').'.pdf';

            return Pdf::loadView('admin.orders.picking', $data)->setPaper('a4')
                ->download($name);
        }

        return view('admin.orders.picking', $data + ['print' => true]);
    }

    /** 공통 검색 필터 빌더 */
    private function filtered(Request $request, $query = null)
    {
        $query = $query ?: Order::withCount('items')->with(['statements', 'taxInvoices'])->latest('id');

        // input(): GET(목록) · POST(피킹 폼) 양쪽에서 필터 값을 읽는다
        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('order_no', 'like', "%$q%")
                    ->orWhere('customer_name', 'like', "%$q%")
                    ->orWhere('customer_phone', 'like', "%$q%")
                    ->orWhere('receiver_name', 'like', "%$q%");
            });
        }
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }
}
