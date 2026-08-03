<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Mail\ServiceRequestCreatedMail;
use App\Mail\ServiceRequestResolvedMail;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * SR(Service Request) — 관리 콘솔 사용자가 요청/장애를 접수하면
 * 본사 담당자가 답글을 달고 처리 상태를 관리한다.
 */
class ServiceRequestController extends Controller
{
    /** 본사 담당자만 답변·상태변경·전체조회가 가능 */
    private function isStaff(): bool
    {
        return Auth::user()->isHqAdmin();
    }

    /** 본사는 전체, 그 외 역할은 자기가 올린 SR만 */
    private function scoped()
    {
        $q = ServiceRequest::query();
        if (! $this->isStaff()) {
            $q->where('user_id', Auth::id());
        }
        return $q;
    }

    /** 상단 SR 배지 숫자 — 본사는 미처리 전체, 그 외는 내 미종료 건 */
    public static function badgeCount(?User $user): int
    {
        if (! $user) return 0;
        try {
            $q = ServiceRequest::whereIn('status', ServiceRequest::OPEN_STATUSES);
            if (! $user->isHqAdmin()) {
                $q->where('user_id', $user->id);
            }
            return (int) $q->count();
        } catch (\Throwable $e) {
            return 0;   // 마이그레이션 전이어도 화면이 죽지 않도록
        }
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $category = $request->query('category');
        $priority = $request->query('priority');

        $query = $this->scoped()->with(['user', 'assignee'])->latest('id');
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('sr_no', 'like', "%$q%")
                ->orWhere('title', 'like', "%$q%")
                ->orWhere('content', 'like', "%$q%"));
        }
        if ($status && isset(ServiceRequest::STATUSES[$status])) {
            $query->where('status', $status);
        }
        if ($category && isset(ServiceRequest::CATEGORIES[$category])) {
            $query->where('category', $category);
        }
        if ($priority && isset(ServiceRequest::PRIORITIES[$priority])) {
            $query->where('priority', $priority);
        }
        if ($request->boolean('mine') && $this->isStaff()) {
            $query->where('assignee_id', Auth::id());
        }

        $requests = $query->paginate(20)->withQueryString();

        // 상태별 건수 요약 (현재 사용자 범위 기준)
        $counts = $this->scoped()->selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status');

        return view('manage.sr.index', [
            'requests' => $requests,
            'counts' => $counts,
            'total' => $counts->sum(),
            'q' => $q,
            'status' => $status,
            'category' => $category,
            'priority' => $priority,
            'mine' => $request->boolean('mine'),
            'isStaff' => $this->isStaff(),
        ]);
    }

    public function create()
    {
        return view('manage.sr.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:200',
            'category' => 'required|in:'.implode(',', array_keys(ServiceRequest::CATEGORIES)),
            'priority' => 'required|in:'.implode(',', array_keys(ServiceRequest::PRIORITIES)),
            'content' => 'required|string|max:5000',
        ], [], ['title' => '제목', 'content' => '내용', 'category' => '유형', 'priority' => '중요도']);

        $sr = ServiceRequest::create($data + [
            'sr_no' => ServiceRequest::nextNo(),
            'user_id' => Auth::id(),
            'requester_role' => Auth::user()->role,
            'status' => 'open',
        ]);

        $notify = $this->notifyCreated($sr);
        $redirect = redirect()->route('manage.sr.show', $sr)
            ->with('status', 'SR '.$sr->sr_no.' 이(가) 접수되었습니다.'.($notify['ok'] ? ' '.$notify['message'] : ''));

        return $notify['ok'] ? $redirect : $redirect->with('error', $notify['message']);
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $this->authorizeView($serviceRequest);
        $serviceRequest->load(['user', 'assignee', 'replies.user']);

        return view('manage.sr.show', [
            'sr' => $serviceRequest,
            'isStaff' => $this->isStaff(),
        ]);
    }

    /** 답글 — 본사는 담당자 답변, 요청자는 추가 문의 */
    public function reply(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorizeView($serviceRequest);
        if ($serviceRequest->is_closed) {
            return back()->with('error', '종료된 SR에는 답글을 등록할 수 없습니다.');
        }

        $data = $request->validate(['body' => 'required|string|max:5000'], [], ['body' => '내용']);

        ServiceRequestReply::create([
            'service_request_id' => $serviceRequest->id,
            'user_id' => Auth::id(),
            'body' => $data['body'],
            'is_staff' => $this->isStaff(),
        ]);

        $update = ['reply_count' => $serviceRequest->replies()->count()];
        // 담당자가 처음 답변하면 자동으로 담당 지정 + 처리중으로 전환
        if ($this->isStaff()) {
            $update['assignee_id'] = $serviceRequest->assignee_id ?: Auth::id();
            if ($serviceRequest->status === 'open') {
                $update['status'] = 'in_progress';
            }
        }
        $serviceRequest->update($update);

        return back()->with('status', '답글이 등록되었습니다.');
    }

    /** 상태 관리 — 본사 담당자 전용 (요청자는 자기 SR 종료만 가능) */
    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $this->authorizeView($serviceRequest);
        $data = $request->validate([
            'status' => 'required|in:'.implode(',', array_keys(ServiceRequest::STATUSES)),
        ]);

        if (! $this->isStaff() && $data['status'] !== 'closed') {
            abort(403, '상태 변경은 본사 담당자만 가능합니다.');
        }

        $before = $serviceRequest->status;
        $serviceRequest->update([
            'status' => $data['status'],
            'closed_at' => $data['status'] === 'closed' ? now() : null,
            'assignee_id' => $serviceRequest->assignee_id ?: ($this->isStaff() ? Auth::id() : null),
        ]);

        $message = 'SR 상태를 '.$serviceRequest->status_label.'(으)로 변경했습니다.';

        // 처리완료(적용 완료)로 바뀌면 등록자에게 안내 메일 — 재발송은 하지 않는다
        if ($data['status'] === 'resolved' && $before !== 'resolved' && ! $serviceRequest->resolved_notified_at) {
            $mail = $this->notifyResolved($serviceRequest);
            $redirect = back()->with('status', $message.' '.$mail['message']);

            return $mail['ok'] ? $redirect : $redirect->with('error', $mail['message']);
        }

        return back()->with('status', $message);
    }

    /** SR 접수 알림 메일 — 사이트 설정의 수신 주소로 발송 (실패해도 접수는 유지) */
    private function notifyCreated(ServiceRequest $sr): array
    {
        $to = Setting::emails('sr_notify_email');
        if (! $to) {
            return ['ok' => false, 'message' => 'SR 알림 수신 주소가 설정되지 않아 접수 알림 메일은 보내지 못했습니다.'];
        }

        try {
            Mail::to($to)->send(new ServiceRequestCreatedMail($sr));

            return ['ok' => true, 'message' => implode(', ', $to).' 로 접수 알림을 보냈습니다.'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => 'SR 접수 알림 메일 발송에 실패했습니다: '.$e->getMessage()];
        }
    }

    /** 처리완료 안내 메일 발송 (실패해도 상태 변경은 유지) */
    private function notifyResolved(ServiceRequest $sr): array
    {
        $to = $sr->user->email ?? null;
        if (! $to) {
            return ['ok' => false, 'message' => '등록자 이메일이 없어 완료 안내 메일은 보내지 못했습니다.'];
        }

        try {
            Mail::to($to)->send(new ServiceRequestResolvedMail($sr));
            $sr->update(['resolved_notified_at' => now()]);

            return ['ok' => true, 'message' => $to.' 로 완료 안내 메일을 발송했습니다.'];
        } catch (\Throwable $e) {
            report($e);

            return ['ok' => false, 'message' => '완료 안내 메일 발송에 실패했습니다: '.$e->getMessage()];
        }
    }

    /** 담당자 지정 (본인에게) */
    public function assign(ServiceRequest $serviceRequest)
    {
        abort_unless($this->isStaff(), 403);
        $serviceRequest->update([
            'assignee_id' => Auth::id(),
            'status' => $serviceRequest->status === 'open' ? 'in_progress' : $serviceRequest->status,
        ]);

        return back()->with('status', '담당자로 지정되었습니다.');
    }

    private function authorizeView(ServiceRequest $sr): void
    {
        abort_unless($this->isStaff() || $sr->user_id === Auth::id(), 403, '본인이 등록한 SR만 볼 수 있습니다.');
    }
}
