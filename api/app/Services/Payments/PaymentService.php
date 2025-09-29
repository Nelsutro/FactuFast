<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentService
{
    public function __construct()
    {
    }

    public function getGatewayForCompany($company, string $provider): PaymentGatewayInterface
    {
        return match($provider) {
            'webpay' => WebpayGateway::fromCompany($company),
            'mercadopago' => MercadoPagoGateway::fromCompany($company),
            default => throw new \InvalidArgumentException('Proveedor de pago no soportado')
        };
    }

    /**
     * Crea registro Payment y lanza inicialización con gateway.
     */
    public function initiatePayment(Invoice $invoice, string $provider, array $options = []): Payment
    {
        return DB::transaction(function() use ($invoice, $provider, $options) {
            // Validar provider habilitado en la empresa
            $enabled = collect((array) json_decode($invoice->company->payment_providers_enabled ?? '[]', true));
            if ($enabled->isNotEmpty() && !$enabled->contains($provider)) {
                throw new \RuntimeException('Proveedor no habilitado para esta empresa');
            }

            // Evitar iniciar múltiples intentos simultáneos para misma factura y provider si hay uno pendiente
            $existing = Payment::where('invoice_id', $invoice->id)
                ->where('payment_provider', $provider)
                ->whereNull('paid_at')
                ->whereIn('intent_status', ['created','initiated','authorized'])
                ->latest('id')
                ->first();
            if ($existing) {
                return $existing; // reutilizar intento previo
            }
            $payment = new Payment();
            $payment->company_id = $invoice->company_id;
            $payment->client_id = $invoice->client_id;
            $payment->invoice_id = $invoice->id;
            $payment->amount = $invoice->remaining_amount ?? $invoice->amount;
            $payment->payment_date = now();
            $payment->payment_method = match($provider) {
                'webpay', 'mercadopago' => 'credit_card',
                'bank_transfer' => 'bank_transfer',
                default => 'other'
            };
            $payment->status = 'pending';
            $payment->intent_status = 'created';
            $payment->payment_provider = $provider;
            $payment->save();

            $gateway = $this->getGatewayForCompany($invoice->company, $provider);
            $gatewayResp = $gateway->initiate($invoice, $payment, $options);

            $payment->provider_payment_id = $gatewayResp['provider_payment_id'] ?? null;
            $payment->intent_status = $gatewayResp['status'] ?? 'initiated';
            if (!empty($gatewayResp['raw'])) {
                $payment->raw_gateway_response = $gatewayResp['raw'];
            }
            $payment->save();

            // Normalizar redirect
            $payment->redirect_url = $gatewayResp['redirect_url'] ?? null; // atributo virtual (no guardado)
            return $payment;
        });
    }

    public function refreshStatus(Payment $payment): Payment
    {
        if (!$payment->provider_payment_id) {
            return $payment; // nada que consultar
        }
        $gateway = $this->getGatewayForCompany($payment->invoice->company, $payment->payment_provider);
        $resp = $gateway->retrieve($payment->provider_payment_id);
        $payment->intent_status = $resp['status'] ?? $payment->intent_status;
        if (($resp['paid'] ?? false) && !$payment->paid_at) {
            $payment->paid_at = $resp['paid_at'] ?? now();
            $payment->status = 'completed';
            // marcar factura como pagada si corresponde
            $invoice = $payment->invoice;
            if ($invoice && $invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->save();
            }
        }
        if (!empty($resp['raw'])) {
            $payment->raw_gateway_response = $resp['raw'];
        }
        $payment->save();
        return $payment;
    }

    public function applyWebhook(string $provider, array $payload): ?Payment
    {
        // Identificar company -> en un escenario real incluiríamos identificador en metadata
        // Aquí iteramos simple (optimizar luego).
        $providerPaymentId = $payload['provider_payment_id'] ?? $payload['token'] ?? $payload['id'] ?? null;
        if (!$providerPaymentId) {
            return null;
        }
        $payment = Payment::where('payment_provider', $provider)
            ->where('provider_payment_id', $providerPaymentId)
            ->first();
        if (!$payment) {
            return null;
        }
        $gateway = $this->getGatewayForCompany($payment->invoice->company, $provider);
        $data = $gateway->handleWebhook($payload);
        $payment->intent_status = $data['status'] ?? $payment->intent_status;
        if (!empty($data['raw'])) {
            $payment->raw_gateway_response = $data['raw'];
        }
        if (($data['paid'] ?? false) && !$payment->paid_at) {
            $payment->paid_at = $data['paid_at'] ?? now();
            $payment->status = 'completed';
            $invoice = $payment->invoice;
            if ($invoice && $invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->save();
            }
        }
        $payment->save();
        return $payment;
    }
}
