<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedCompanyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function build()
    {
        $invoice = $this->payment->invoice;
        $subject = 'Pago recibido - Factura #' . ($invoice->invoice_number ?? $invoice->id);
        return $this->subject($subject)
            ->view('emails.payment_received_company', [
                'payment' => $this->payment,
                'invoice' => $invoice,
            ]);
    }
}
