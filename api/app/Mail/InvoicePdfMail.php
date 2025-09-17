<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoicePdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public Invoice $invoice;
    public string $bodyMessage;
    public string $subjectLine;

    public function __construct(Invoice $invoice, string $subjectLine, string $bodyMessage)
    {
        $this->invoice = $invoice;
        $this->subjectLine = $subjectLine;
        $this->bodyMessage = $bodyMessage;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.invoice')
            ->with([
                'invoice' => $this->invoice,
                'bodyMessage' => $this->bodyMessage,
            ]);
    }
}
