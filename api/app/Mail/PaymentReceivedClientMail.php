<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceivedClientMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function build()
    {
        $invoice = $this->payment->invoice;
        $subject = 'Comprobante de pago - Factura #' . ($invoice->invoice_number ?? $invoice->id);
        return $this->subject($subject)
            ->view('emails.payment_received_client', [
                'payment' => $this->payment,
                'invoice' => $invoice,
            ]);
    }
}
