<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonInterface;

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
            'flow' => FlowGateway::fromCompany($company),
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
                'webpay', 'mercadopago', 'flow' => 'credit_card',
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

            if (!$payment->provider_payment_id && in_array($payment->intent_status, ['error', 'failed'], true)) {
                $payment->status = 'failed';
            }

            $payment->save();

            $payment = $this->finalizePaymentFromGateway($payment->fresh('invoice'), $gatewayResp);
            $payment->setAttribute('redirect_url', $gatewayResp['redirect_url'] ?? null);

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
        return $this->finalizePaymentFromGateway($payment, $resp);
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

        return $this->finalizePaymentFromGateway($payment, $data);
    }

    public function applyGatewayResult(Payment $payment, array $gatewayData): Payment
    {
        return $this->finalizePaymentFromGateway($payment, $gatewayData);
    }

    protected function finalizePaymentFromGateway(Payment $payment, array $gatewayData): Payment
    {
        Log::info('Finalizando pago desde gateway', [
            'payment_id' => $payment->id,
            'current_status' => $payment->status,
            'gateway_data' => $gatewayData
        ]);

        if (!empty($gatewayData['raw'])) {
            $payment->raw_gateway_response = $gatewayData['raw'];
        }

        if (($gatewayData['paid'] ?? false)) {
            $paidAt = $gatewayData['paid_at'] ?? now();
            if (!$paidAt instanceof CarbonInterface) {
                $paidAt = Carbon::make($paidAt) ?? now();
            }

            $payment->status = 'completed';
            $payment->paid_at = $payment->paid_at ?: $paidAt;
            $payment->intent_status = $gatewayData['status'] ?? 'paid';
            $payment->save();

            Log::info('Pago marcado como completado', [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'paid_at' => $payment->paid_at,
                'is_paid' => $payment->is_paid
            ]);

            $this->markInvoiceStatus($payment);
            return $payment;
        }

        if (!empty($gatewayData['status'])) {
            $payment->intent_status = $gatewayData['status'];
        }

        if (isset($gatewayData['status']) && in_array($gatewayData['status'], ['failed', 'error', 'aborted', 'rejected'], true)) {
            $payment->status = 'failed';
        }

        $payment->save();

        return $payment;
    }

    protected function markInvoiceStatus(Payment $payment): void
    {
        $invoice = $payment->invoice()->with('payments')->first();
        if (!$invoice) {
            return;
        }

        $remaining = $invoice->remaining_amount;
        if ($remaining <= 0) {
            if ($invoice->status !== 'paid') {
                $invoice->status = 'paid';
                $invoice->save();
            }
            return;
        }

        if ($invoice->status === 'paid' && $remaining > 0) {
            $invoice->status = 'pending';
            $invoice->save();
        }
    }
}
