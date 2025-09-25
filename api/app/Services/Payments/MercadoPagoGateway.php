<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected string $publicKey,
        protected string $accessToken
    ) {}

    public static function fromCompany($company): self
    {
        return new self(
            (string) $company->mp_public_key,
            (string) $company->mp_access_token,
        );
    }

    public function initiate(Invoice $invoice, Payment $payment, array $options = []): array
    {
        $providerId = 'MP-' . $payment->id . '-' . uniqid();
        $redirect = $options['return_url'] ?? null;
        return [
            'provider_payment_id' => $providerId,
            'redirect_url' => $redirect,
            'status' => 'initiated',
            'raw' => [ 'simulated' => true ]
        ];
    }

    public function retrieve(string $providerPaymentId): array
    {
        return [
            'status' => 'initiated',
            'paid' => false,
            'paid_at' => null,
            'raw' => [ 'simulated' => true, 'provider_payment_id' => $providerPaymentId ]
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $status = $payload['status'] ?? 'paid';
        $paid = in_array($status, ['approved','paid']);
        return [
            'provider_payment_id' => $payload['provider_payment_id'] ?? $payload['id'] ?? 'unknown',
            'status' => $status,
            'paid' => $paid,
            'paid_at' => $paid ? Carbon::parse($payload['paid_at'] ?? now()) : null,
            'raw' => $payload
        ];
    }
}
