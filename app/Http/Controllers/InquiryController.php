<?php

namespace App\Http\Controllers;

use App\Events\InquiryActivity;
use App\Models\Inquiry;
use App\Models\InquiryMessage;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    /** 문의 시작: 이름·전화번호 등록 → 대화방 생성 */
    public function start(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:40',
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9()+\-\s]{7,30}$/'],
            'message' => 'nullable|string|max:2000',
        ], [], ['name' => '이름', 'phone' => '전화번호', 'message' => '문의 내용']);

        $inquiry = Inquiry::create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'ip_address' => $request->ip(),
            'last_message_at' => now(),
        ]);

        try {
            InquiryActivity::dispatch($inquiry->id, 'new');
        } catch (\Throwable $e) {
            report($e);
        }

        // 최초 메시지가 함께 오면 등록
        if (! empty($data['message'])) {
            $inquiry->postMessage('customer', $data['message']);
        }

        return response()->json([
            'token' => $inquiry->token,
            'id' => $inquiry->id,
            'name' => $inquiry->name,
        ]);
    }

    /** 고객이 메시지 전송 */
    public function message(Request $request, string $token)
    {
        $inquiry = Inquiry::where('token', $token)->firstOrFail();
        $data = $request->validate([
            'body' => 'required|string|max:2000',
        ], [], ['body' => '메시지']);

        $message = $inquiry->postMessage('customer', $data['body']);

        return response()->json($this->present($message));
    }

    /** 폴링 폴백: after_id 이후의 새 메시지 조회 (+ 고객 미확인 초기화) */
    public function poll(Request $request, string $token)
    {
        $inquiry = Inquiry::where('token', $token)->firstOrFail();
        $after = (int) $request->query('after', 0);

        $messages = $inquiry->messages()->where('id', '>', $after)->get()
            ->map(fn ($m) => $this->present($m));

        // 고객이 조회했으므로 고객 미확인 초기화
        if ($inquiry->unread_customer > 0) {
            $inquiry->update(['unread_customer' => 0]);
        }

        return response()->json([
            'status' => $inquiry->status,
            'messages' => $messages,
        ]);
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
