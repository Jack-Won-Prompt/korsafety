<?php

namespace App\Mail;

use App\Models\ServiceRequest;
use App\Models\ServiceRequestReply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** SR 담당자 답변 등록 안내 — 등록자에게 발송 */
class ServiceRequestRepliedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceRequest $sr, public ServiceRequestReply $reply)
    {
    }

    public function build()
    {
        $company = config('company.name', '주식회사 한국안전');
        $this->sr->loadMissing(['user', 'assignee']);
        $this->reply->loadMissing('user');

        return $this->subject('['.$company.'] SR 답변 등록 안내 · '.$this->sr->sr_no)
            ->view('emails.sr-replied');
    }
}
