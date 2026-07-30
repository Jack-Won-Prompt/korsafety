<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order,
        public string $pdfData,
        public string $fileName,
    ) {
    }

    public function build()
    {
        $company = config('company.name', '주식회사 한국안전');
        $this->order->loadMissing('items');

        return $this->subject('['.$company.'] 거래명세서 · '.$this->order->order_no)
            ->view('emails.order-statement')
            ->attachData($this->pdfData, $this->fileName, ['mime' => 'application/pdf']);
    }
}
