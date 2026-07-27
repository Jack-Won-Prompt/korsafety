<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\InquiryMessage;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /** 문의 목록 (미확인·최근순) */
    public function index()
    {
        $inquiries = Inquiry::query()
            ->withCount('messages')
            ->orderByRaw('unread_admin > 0 desc')
            ->orderByDesc('last_message_at')
            ->paginate(20);

        $openCount = Inquiry::where('status', 'open')->count();
        $unreadTotal = Inquiry::sum('unread_admin');

        return view('admin.inquiries.index', compact('inquiries', 'openCount', 'unreadTotal'));
    }

    /** 대화 화면 (관리자 미확인 초기화) */
    public function show(Inquiry $inquiry)
    {
        $inquiry->load('messages');
        if ($inquiry->unread_admin > 0) {
            $inquiry->update(['unread_admin' => 0]);
        }

        return view('admin.inquiries.show', compact('inquiry'));
    }

    /** 관리자 답장 */
    public function reply(Request $request, Inquiry $inquiry)
    {
        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ], [], ['body' => '메시지']);

        $message = $inquiry->postMessage('admin', $data['body']);

        return response()->json($this->present($message));
    }

    /** 폴링 폴백: after_id 이후 새 메시지 (+ 관리자 미확인 초기화) */
    public function poll(Request $request, Inquiry $inquiry)
    {
        $after = (int) $request->query('after', 0);
        $messages = $inquiry->messages()->where('id', '>', $after)->get()
            ->map(fn ($m) => $this->present($m));

        if ($inquiry->unread_admin > 0) {
            $inquiry->update(['unread_admin' => 0]);
        }

        return response()->json([
            'status' => $inquiry->status,
            'messages' => $messages,
        ]);
    }

    /** 실시간 핑 수신 후 목록 갱신용 요약 데이터 */
    public function listData()
    {
        $rows = Inquiry::query()
            ->orderByRaw('unread_admin > 0 desc')
            ->orderByDesc('last_message_at')
            ->limit(50)->get()
            ->map(fn (Inquiry $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'phone' => $i->phone,
                'status' => $i->status,
                'unread' => $i->unread_admin,
                'last_message_at' => optional($i->last_message_at)->toIso8601String(),
            ]);

        return response()->json([
            'inquiries' => $rows,
            'unread_total' => (int) Inquiry::sum('unread_admin'),
        ]);
    }

    /** 상태 토글 (종료/재개) */
    public function toggle(Inquiry $inquiry)
    {
        $inquiry->update(['status' => $inquiry->status === 'open' ? 'closed' : 'open']);

        return back();
    }

    private function present(InquiryMessage $m): array
    {
        return [
            'id' => $m->id,
            'sender' => $m->sender,
            'body' => $m->body,
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }
}
