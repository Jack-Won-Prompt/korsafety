<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\MemberInviteMail;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** 회원 초대 — 이메일 한 건 또는 엑셀 명단으로 가입 안내를 보낸다 (본사 전용) */
class InvitationController extends Controller
{
    /** 엑셀 명단 머리글 */
    private const CSV_HEADER = ['이메일', '이름', '회사명', '구분(일반/협력사)'];

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $query = Invitation::with(['inviter', 'user']);
        if ($status !== 'all' && isset(Invitation::STATUSES[$status])) {
            $query->where('status', $status);
        }
        if ($q !== '') {
            $query->where(fn ($w) => $w->where('email', 'like', "%$q%")
                ->orWhere('name', 'like', "%$q%")
                ->orWhere('company_name', 'like', "%$q%"));
        }
        $invitations = $query->latest('id')->paginate(30)->withQueryString();

        $byStatus = Invitation::selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');
        $stats = [
            'all' => (int) $byStatus->sum(),
            'sent' => (int) ($byStatus['sent'] ?? 0),
            'accepted' => (int) ($byStatus['accepted'] ?? 0),
            'cancelled' => (int) ($byStatus['cancelled'] ?? 0),
            'waiting' => User::where('approval_status', 'pending')->count(),
        ];

        return view('admin.invitations.index', compact('invitations', 'stats', 'status', 'q'));
    }

    /** 한 건 초대 */
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email|max:150',
            'name' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:150',
            'role' => 'required|in:customer,partner',
        ], [], ['email' => '이메일', 'name' => '이름', 'company_name' => '회사명', 'role' => '구분']);

        $result = $this->invite($data);

        return back()->with($result['ok'] ? 'status' : 'error', $result['message']);
    }

    /** 엑셀(CSV) 명단으로 일괄 초대 */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|max:4096'], [], ['file' => '파일']);

        $upload = $request->file('file');
        if (! in_array(strtolower($upload->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            return back()->with('error', 'CSV(.csv) 파일만 올릴 수 있습니다. 양식을 내려받아 사용해 주세요.');
        }

        $fh = fopen($upload->getRealPath(), 'r');
        if ($fh === false) {
            return back()->with('error', '파일을 열 수 없습니다.');
        }

        $sent = 0; $skipped = 0; $line = 0; $problems = [];
        while (($row = fgetcsv($fh)) !== false) {
            $line++;
            if (isset($row[0])) {
                $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
            }
            $row = array_map(fn ($v) => $this->toUtf8($v), $row);

            // 머리글 · 빈 줄 건너뛰기
            if ($line === 1 && str_contains((string) ($row[0] ?? ''), '이메일')) {
                continue;
            }
            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            [$email, $name, $company, $roleRaw] = array_pad($row, 4, null);
            $email = trim((string) $email);
            $role = str_contains((string) $roleRaw, '협력') ? 'partner' : 'customer';

            $result = $this->invite([
                'email' => $email,
                'name' => trim((string) $name) ?: null,
                'company_name' => trim((string) $company) ?: null,
                'role' => $role,
            ]);

            if ($result['ok']) {
                $sent++;
            } else {
                $skipped++;
                if (count($problems) < 5) {
                    $problems[] = ($email ?: $line.'번째 줄').' — '.$result['message'];
                }
            }
        }
        fclose($fh);

        $msg = "초대 메일 {$sent}건을 보냈습니다.".($skipped ? " 건너뛴 {$skipped}건: ".implode(' / ', $problems) : '');

        return back()->with($sent ? 'status' : 'error', $msg);
    }

    /** 초대 다시 보내기 */
    public function resend(Invitation $invitation)
    {
        if ($invitation->status === 'accepted') {
            return back()->with('error', '이미 가입을 마친 초대입니다.');
        }

        $invitation->update([
            'token' => Invitation::newToken(),
            'status' => 'sent',
            'sent_at' => now(),
            'expires_at' => now()->addDays(Invitation::VALID_DAYS),
        ]);

        return $this->deliver($invitation)
            ? back()->with('status', $invitation->email.' 로 초대 메일을 다시 보냈습니다.')
            : back()->with('error', '메일을 보내지 못했습니다. 메일 설정을 확인해 주세요.');
    }

    public function cancel(Invitation $invitation)
    {
        if ($invitation->status === 'accepted') {
            return back()->with('error', '이미 가입을 마친 초대는 취소할 수 없습니다.');
        }

        $invitation->update(['status' => 'cancelled']);

        return back()->with('status', $invitation->email.' 초대를 취소했습니다. 링크가 더 이상 열리지 않습니다.');
    }

    /** 엑셀 양식 내려받기 */
    public function template()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, self::CSV_HEADER);
            fputcsv($out, ['hong@example.com', '홍길동', '(주)예시산업', '협력사']);
            fputcsv($out, ['kim@example.com', '김고객', '', '일반']);
            fclose($out);
        }, 'invite_template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** 초대 한 건 만들고 메일 발송 */
    private function invite(array $data): array
    {
        $email = mb_strtolower(trim($data['email']));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => '이메일 형식이 올바르지 않습니다.'];
        }
        if (User::where('email', $email)->exists()) {
            return ['ok' => false, 'message' => '이미 가입된 이메일입니다.'];
        }

        // 같은 주소로 보낸 적이 있으면 새 링크로 바꿔 다시 보낸다
        $invitation = Invitation::where('email', $email)->where('status', '!=', 'accepted')->latest('id')->first()
            ?? new Invitation(['email' => $email]);

        $invitation->fill([
            'email' => $email,
            'name' => $data['name'] ?? null,
            'company_name' => $data['company_name'] ?? null,
            'role' => $data['role'],
            'token' => Invitation::newToken(),
            'status' => 'sent',
            'invited_by' => auth()->id(),
            'sent_at' => now(),
            'expires_at' => now()->addDays(Invitation::VALID_DAYS),
        ])->save();

        if (! $this->deliver($invitation)) {
            return ['ok' => false, 'message' => '메일 발송에 실패했습니다.'];
        }

        return ['ok' => true, 'message' => $email.' 로 초대 메일을 보냈습니다.'];
    }

    private function deliver(Invitation $invitation): bool
    {
        try {
            Mail::to($invitation->email)->send(new MemberInviteMail($invitation));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
        }
    }

    /** 엑셀이 CP949로 저장한 한글을 UTF-8로 */
    private function toUtf8($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;

        return mb_check_encoding($value, 'UTF-8') ? $value : mb_convert_encoding($value, 'UTF-8', 'CP949');
    }
}
