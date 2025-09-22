<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function build()
    {
        $subject = 'Nueva factura #' . ($this->invoice->invoice_number ?? $this->invoice->id);
        return $this->subject($subject)
            ->view('emails.invoice_created_client', [
                'invoice' => $this->invoice,
            ]);
    }
}
