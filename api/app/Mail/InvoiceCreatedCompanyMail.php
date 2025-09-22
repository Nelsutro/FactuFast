<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceCreatedCompanyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice) {}

    public function build()
    {
        $subject = 'Factura creada #' . ($this->invoice->invoice_number ?? $this->invoice->id);
        return $this->subject($subject)
            ->view('emails.invoice_created_company', [
                'invoice' => $this->invoice,
            ]);
    }
}
