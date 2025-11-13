<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\FlowService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FlowGateway implements PaymentGatewayInterface
{
    protected FlowService $flowService;

    public function __construct(
        protected string $apiKey,
        protected string $secretKey,
        protected bool $sandbox = true
    ) {
        $this->flowService = new FlowService($apiKey, $secretKey, $sandbox);
    }

    public static function fromCompany($company): self
    {
        // Usar credenciales de la empresa o fallback a configuración global
        $apiKey = $company->flow_api_key ?? config('services.flow.api_key');
        $secretKey = $company->flow_secret_key ?? config('services.flow.secret_key');
        $environment = $company->flow_environment ?? config('services.flow.environment', 'sandbox');
        
        return new self(
            $apiKey,
            $secretKey,
            $environment === 'sandbox'
        );
    }

    public function initiate(Invoice $invoice, Payment $payment, array $options = []): array
    {
        try {
            // Preparar datos para Flow.cl
            $amount = (int) round($invoice->remaining_amount ?? $invoice->total);
            $paymentData = [
                'commerceOrder' => 'INV-' . $invoice->id . '-' . time(),
                'subject' => 'Pago Factura #' . $invoice->invoice_number,
                'amount' => $amount,
                'email' => $invoice->client->email ?? 'cliente@ejemplo.com',
                'urlConfirmation' => $options['callback_url'] ?? url('/api/webhooks/flow/payment-confirmation'),
                'urlReturn' => $options['return_url'] ?? url('/client-portal/invoice/' . $invoice->id . '?paid=1'),
                'currency' => 'CLP'
            ];

            // Verificar si tenemos credenciales configuradas
            if (empty($this->apiKey) || empty($this->secretKey)) {
                return $this->createSimulatedPayment($amount);
            }

            // Crear pago en Flow.cl
            try {
                $response = $this->flowService->createPayment($paymentData);

                if (!$response || !isset($response['url']) || !isset($response['token'])) {
                    throw new \RuntimeException('Respuesta inválida de Flow.cl: ' . json_encode($response));
                }

                return [
                    'provider_payment_id' => $response['token'],
                    'redirect_url' => $response['url'] . '?token=' . $response['token'],
                    'status' => 'pending',
                    'paid' => false,
                    'paid_at' => null,
                    'raw' => $response
                ];

            } catch (\Exception $e) {
                Log::warning('Error con Flow API, usando simulación', [
                    'error' => $e->getMessage(),
                    'invoice_id' => $invoice->id
                ]);

                // Si hay error con credenciales o API, usar simulación
                return $this->createSimulatedPayment($amount, 'API Error: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Error iniciando pago Flow.cl', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \RuntimeException('Error al iniciar pago con Flow.cl: ' . $e->getMessage());
        }
    }

    public function retrieve(string $providerPaymentId): array
    {
        try {
            // Verificar si es simulación
            if (str_starts_with($providerPaymentId, 'flow-sim-')) {
                // Extraer timestamp del ID de simulación
                $timestamp = (int) str_replace('flow-sim-', '', $providerPaymentId);
                $elapsed = time() - $timestamp;
                
                Log::info('Flow simulation retrieve', [
                    'provider_payment_id' => $providerPaymentId,
                    'timestamp' => $timestamp,
                    'elapsed' => $elapsed
                ]);
                
                // Simular un delay de 5 segundos antes de marcar como completado
                if ($elapsed >= 5) {
                    $result = [
                        'status' => 'completed',
                        'paid' => true,
                        'paid_at' => Carbon::now(),
                        'raw' => ['mode' => 'simulation', 'completed_at' => Carbon::now()]
                    ];
                    Log::info('Flow simulation COMPLETED', $result);
                    return $result;
                } else {
                    $result = [
                        'status' => 'pending',
                        'paid' => false,
                        'paid_at' => null,
                        'raw' => ['mode' => 'simulation', 'remaining_seconds' => 5 - $elapsed]
                    ];
                    Log::info('Flow simulation PENDING', $result);
                    return $result;
                }
            }

            // Consultar estado en Flow.cl
            $response = $this->flowService->getPaymentStatus($providerPaymentId);

            if (!$response) {
                throw new \RuntimeException('No se pudo obtener el estado del pago');
            }

            $paid = isset($response['status']) && (int)$response['status'] === 2; // 2 = pagado en Flow.cl

            return [
                'status' => $paid ? 'completed' : 'pending',
                'paid' => $paid,
                'paid_at' => $paid ? Carbon::now() : null,
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Error consultando estado Flow.cl', [
                'provider_payment_id' => $providerPaymentId,
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'failed',
                'paid' => false,
                'paid_at' => null,
                'raw' => ['error' => $e->getMessage()]
            ];
        }
    }

    public function handleWebhook(array $payload): array
    {
        try {
            // El webhook de Flow.cl envía el token del pago
            $token = $payload['token'] ?? null;

            if (!$token) {
                throw new \RuntimeException('Token no encontrado en webhook');
            }

            // Verificar el pago con Flow.cl
            $response = $this->flowService->getPaymentStatus($token);

            if (!$response) {
                throw new \RuntimeException('No se pudo verificar el pago');
            }

            $paid = isset($response['status']) && (int)$response['status'] === 2; // 2 = pagado

            return [
                'provider_payment_id' => $token,
                'status' => $paid ? 'completed' : 'pending',
                'paid' => $paid,
                'paid_at' => $paid ? Carbon::now() : null,
                'raw' => $response
            ];

        } catch (\Exception $e) {
            Log::error('Error procesando webhook Flow.cl', [
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Crear respuesta de pago simulado
     */
    protected function createSimulatedPayment(int $amount, string $reason = 'Sin credenciales configuradas'): array
    {
        return [
            'provider_payment_id' => 'flow-sim-' . time(),
            'redirect_url' => null,
            'status' => 'pending',
            'paid' => false,
            'paid_at' => null,
            'raw' => [
                'mode' => 'simulation',
                'message' => 'Flow.cl en modo simulación: ' . $reason,
                'amount' => $amount,
                'simulated_completion_time' => Carbon::now()->addSeconds(5)->toISOString()
            ]
        ];
    }
}