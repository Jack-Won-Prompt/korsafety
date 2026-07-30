<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStatementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderStatementController extends Controller
{
    public function __construct(private OrderStatementService $service)
    {
    }

    /** PDF 미리보기 (브라우저 인라인) */
    public function preview(Order $order)
    {
        return $this->service->preview($order);
    }

    /** HTML 인쇄 페이지 */
    public function print(Order $order)
    {
        return $this->service->htmlView($order);
    }

    /** PDF 다운로드 */
    public function download(Order $order)
    {
        return $this->service->download($order, Auth::id());
    }

    /** 이메일 발송 */
    public function send(Request $request, Order $order)
    {
        $data = $request->validate([
            'email' => 'nullable|email',
        ], [], ['email' => '이메일']);

        $result = $this->service->email($order, $data['email'] ?? null, Auth::id());

        return back()->with($result['ok'] ? 'status' : 'error', $result['message']);
    }
}
