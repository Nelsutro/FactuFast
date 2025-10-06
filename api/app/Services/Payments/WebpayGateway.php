<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WebpayGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected string $environment,
        protected string $commerceCode,
        protected string $apiKey
    ) {}

    public static function fromCompany($company): self
    {
        return new self(
            $company->webpay_environment ?? 'integration',
            (string) $company->webpay_commerce_code,
            (string) $company->webpay_api_key
        );
    }

    public function initiate(Invoice $invoice, Payment $payment, array $options = []): array
    {
        // Flujo Webpay Plus: crear transacción (initTransaction) -> token + URL
        $returnUrl = $options['return_url'] ?? url('/api/payments/webpay/return');
        $amount = (int) round($invoice->remaining_amount ?? $invoice->total);
        $buyOrder = 'INV-' . $invoice->id . '-' . Str::random(6);
        $sessionId = 'client-' . $invoice->client_id;

        if ($this->isSimulation()) {
            $token = 'sim-webpay-' . Str::random(20);

            return [
                'provider_payment_id' => $token,
                'redirect_url' => null,
                'status' => 'authorized',
                'paid' => true,
                'paid_at' => Carbon::now(),
                'raw' => [
                    'mode' => 'simulation',
                    'message' => 'Webpay completado automáticamente (sin credenciales configuradas).',
                    'amount' => $amount,
                    'return_url' => $returnUrl,
                ],
            ];
        }

        $endpointBase = $this->environment === 'production'
            ? 'https://webpay3g.transbank.cl'
            : 'https://webpay3gint.transbank.cl';

        try {
            $resp = Http::withHeaders([
                'Tbk-Api-Key-Id' => $this->commerceCode,
                'Tbk-Api-Key-Secret' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post($endpointBase . '/rswebpaytransaction/api/webpay/v1.2/transactions', [
                'buy_order' => $buyOrder,
                'session_id' => $sessionId,
                'amount' => $amount,
                'return_url' => $returnUrl
            ]);

            if (!$resp->successful()) {
                Log::error('Webpay init fallo', ['body' => $resp->body()]);
                throw new \RuntimeException('Error iniciando transacción Webpay');
            }
            $data = $resp->json();
            $token = $data['token'] ?? null;
            if (!$token) {
                throw new \RuntimeException('Respuesta Webpay sin token');
            }

            $redirectUrl = $data['url'] ?? ($endpointBase . '/webpayserver/initTransaction');
            $providerPaymentId = $token; // Usaremos token como provider_payment_id

            return [
                'provider_payment_id' => $providerPaymentId,
                'redirect_url' => $redirectUrl . '?token_ws=' . $token,
                'status' => 'created',
                'paid' => false,
                'paid_at' => null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Excepción iniciando Webpay', [ 'msg' => $e->getMessage() ]);
            return [
                'provider_payment_id' => null,
                'redirect_url' => null,
                'status' => 'error',
                'paid' => false,
                'paid_at' => null,
                'raw' => ['error' => $e->getMessage()]
            ];
        }
    }

    public function retrieve(string $providerPaymentId): array
    {
        if ($this->isSimulation()) {
            return [
                'status' => 'paid',
                'paid' => true,
                'paid_at' => Carbon::now(),
                'raw' => [
                    'mode' => 'simulation',
                    'provider_payment_id' => $providerPaymentId,
                ]
            ];
        }

        // Webpay no expone un endpoint "get" simple; se confirma vía commit con token.
        // Aquí devolvemos estado neutro; la confirmación real ocurre en return handler.
        return [
            'status' => 'pending',
            'paid' => false,
            'paid_at' => null,
            'raw' => [ 'provider_payment_id' => $providerPaymentId ]
        ];
    }

    public function handleWebhook(array $payload): array
    {
        // Webpay Plus clásico no usa webhook estándar; se puede ignorar o retornar passthrough.
        return [
            'provider_payment_id' => $payload['token'] ?? $payload['provider_payment_id'] ?? 'unknown',
            'status' => $payload['status'] ?? 'pending',
            'paid' => false,
            'paid_at' => null,
            'raw' => $payload
        ];
    }

    // Método auxiliar para confirmar (commit) usando token de regreso
    public function commit(string $token): array
    {
        if ($this->isSimulation()) {
            return [
                'status' => 'paid',
                'paid' => true,
                'paid_at' => Carbon::now(),
                'raw' => [
                    'mode' => 'simulation',
                    'token' => $token,
                ]
            ];
        }

        $endpointBase = $this->environment === 'production'
            ? 'https://webpay3g.transbank.cl'
            : 'https://webpay3gint.transbank.cl';

        try {
            $resp = Http::withHeaders([
                'Tbk-Api-Key-Id' => $this->commerceCode,
                'Tbk-Api-Key-Secret' => $this->apiKey,
                'Content-Type' => 'application/json'
            ])->put($endpointBase . '/rswebpaytransaction/api/webpay/v1.2/transactions/' . $token, []);

            if (!$resp->successful()) {
                Log::warning('Commit Webpay falló', ['token' => $token, 'body' => $resp->body()]);
                return [
                    'status' => 'failed',
                    'paid' => false,
                    'raw' => $resp->json(),
                ];
            }
            $data = $resp->json();
            $status = $data['status'] ?? 'failed';
            $isPaid = in_array($status, ['AUTHORIZED','authorised','authorized']);
            return [
                'status' => $isPaid ? 'paid' : 'failed',
                'paid' => $isPaid,
                'paid_at' => $isPaid ? Carbon::now() : null,
                'raw' => $data,
            ];
        } catch (\Throwable $e) {
            Log::error('Excepción commit Webpay', ['token' => $token, 'msg' => $e->getMessage()]);
            return [
                'status' => 'error',
                'paid' => false,
                'raw' => ['error' => $e->getMessage()]
            ];
        }
    }

    protected function isSimulation(): bool
    {
        return empty($this->commerceCode) || empty($this->apiKey);
    }
}
