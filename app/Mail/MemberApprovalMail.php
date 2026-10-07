<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** 가입 승인 · 반려 안내 */
class MemberApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param string $decision approved | rejected */
    public function __construct(public User $user, public string $decision, public ?string $reason = null)
    {
    }

    public function build()
    {
        $company = config('company.name', '주식회사 한국안전');
        $approved = $this->decision === 'approved';

        return $this->subject('['.$company.'] 회원 가입이 '.($approved ? '승인되었습니다' : '승인되지 않았습니다'))
            ->view('emails.member-approval', [
                'user' => $this->user,
                'approved' => $approved,
                'reason' => $this->reason,
                'loginUrl' => route('login'),
            ]);
    }
}
