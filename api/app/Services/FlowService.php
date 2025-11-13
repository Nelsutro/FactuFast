<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class FlowService
{
    private string $apiKey;
    private string $secretKey;
    private string $apiUrl;
    private string $environment;

    public function __construct(?string $apiKey = null, ?string $secretKey = null, ?bool $sandbox = null)
    {
        // Usar parámetros proporcionados o fallback a configuración
        $this->apiKey = $apiKey ?? Config::get('services.flow.api_key');
        $this->secretKey = $secretKey ?? Config::get('services.flow.secret_key');
        $this->environment = $sandbox !== null 
            ? ($sandbox ? 'sandbox' : 'production') 
            : Config::get('services.flow.environment', 'sandbox');
        
        // Configurar URL según el ambiente
        if ($this->environment === 'production') {
            $this->apiUrl = 'https://www.flow.cl/api';
        } else {
            $this->apiUrl = Config::get('services.flow.api_url', 'https://developers.sandbox.flow.cl/api');
        }

        if (!$this->apiKey || !$this->secretKey) {
            Log::warning('Flow API credentials not configured, operating in simulation mode');
        }
    }

    /**
     * Crear una orden de pago
     */
    public function createPayment(array $paymentData): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'commerceOrder' => $paymentData['commerceOrder'],
            'subject' => $paymentData['subject'],
            'currency' => $paymentData['currency'] ?? 'CLP',
            'amount' => $paymentData['amount'],
            'email' => $paymentData['email'],
            'urlConfirmation' => $paymentData['urlConfirmation'],
            'urlReturn' => $paymentData['urlReturn'],
        ];

        // Parámetros opcionales
        if (isset($paymentData['paymentMethod'])) {
            $params['paymentMethod'] = $paymentData['paymentMethod'];
        }
        if (isset($paymentData['optional'])) {
            $params['optional'] = $paymentData['optional'];
        }
        if (isset($paymentData['timeout'])) {
            $params['timeout'] = $paymentData['timeout'];
        }
        if (isset($paymentData['merchantId'])) {
            $params['merchantId'] = $paymentData['merchantId'];
        }

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('POST', '/payment/create', $params);
    }

    /**
     * Crear cobro por email
     */
    public function createEmailPayment(array $paymentData): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'commerceOrder' => $paymentData['commerceOrder'],
            'subject' => $paymentData['subject'],
            'currency' => $paymentData['currency'] ?? 'CLP',
            'amount' => $paymentData['amount'],
            'email' => $paymentData['email'],
            'urlConfirmation' => $paymentData['urlConfirmation'],
        ];

        // Parámetros opcionales
        if (isset($paymentData['optional'])) {
            $params['optional'] = $paymentData['optional'];
        }

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('POST', '/payment/createEmail', $params);
    }

    /**
     * Obtener estado de pago por token
     */
    public function getPaymentStatus(string $token): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'token' => $token,
        ];

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('GET', '/payment/getStatus', $params);
    }

    /**
     * Obtener estado de pago por commerceOrder
     */
    public function getPaymentStatusByCommerceOrder(string $commerceOrder): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'commerceOrder' => $commerceOrder,
        ];

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('GET', '/payment/getStatusByCommerceId', $params);
    }

    /**
     * Obtener estado extendido de pago
     */
    public function getPaymentStatusExtended(string $token): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'token' => $token,
        ];

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('GET', '/payment/getStatusExtended', $params);
    }

    /**
     * Crear reembolso
     */
    public function createRefund(array $refundData): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'refundCommerceOrder' => $refundData['refundCommerceOrder'],
            'receiverEmail' => $refundData['receiverEmail'],
            'amount' => $refundData['amount'],
            'urlCallBack' => $refundData['urlCallBack'],
        ];

        if (isset($refundData['commerceTrxId'])) {
            $params['commerceTrxId'] = $refundData['commerceTrxId'];
        }
        if (isset($refundData['flowTrxId'])) {
            $params['flowTrxId'] = $refundData['flowTrxId'];
        }

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('POST', '/refund/create', $params);
    }

    /**
     * Obtener estado de reembolso
     */
    public function getRefundStatus(string $token): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'token' => $token,
        ];

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('GET', '/refund/getStatus', $params);
    }

    /**
     * Crear cliente
     */
    public function createCustomer(array $customerData): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'name' => $customerData['name'],
            'email' => $customerData['email'],
            'externalId' => $customerData['externalId'],
        ];

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('POST', '/customer/create', $params);
    }

    /**
     * Crear pago con cliente registrado
     */
    public function createCustomerPayment(array $paymentData): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'customerId' => $paymentData['customerId'],
            'commerceOrder' => $paymentData['commerceOrder'],
            'subject' => $paymentData['subject'],
            'currency' => $paymentData['currency'] ?? 'CLP',
            'amount' => $paymentData['amount'],
            'urlConfirmation' => $paymentData['urlConfirmation'],
        ];

        // Parámetros opcionales
        if (isset($paymentData['optional'])) {
            $params['optional'] = $paymentData['optional'];
        }

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('POST', '/customer/charge', $params);
    }

    /**
     * Obtener pagos de un día específico
     */
    public function getPaymentsByDate(string $date, array $pagination = []): array
    {
        $params = [
            'apiKey' => $this->apiKey,
            'date' => $date,
            'start' => $pagination['start'] ?? 0,
            'limit' => $pagination['limit'] ?? 100,
        ];

        $params['s'] = $this->signParams($params);

        return $this->makeRequest('GET', '/payment/getPayments', $params);
    }

    /**
     * Firmar parámetros con HMAC SHA256
     */
    private function signParams(array $params): string
    {
        // Excluir 's' del firmado
        unset($params['s']);
        
        // Ordenar parámetros alfabéticamente por las claves
        ksort($params);
        
        // Concatenar parámetros: nombre_parametro + valor (sin separadores)
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . $value;
        }
        
        Log::debug('Flow HMAC signing', [
            'sorted_params' => $params,
            'string_to_sign' => $toSign,
            'secret_key_prefix' => substr($this->secretKey, 0, 8) . '...'
        ]);
        
        // Firmar con HMAC SHA256
        return hash_hmac('sha256', $toSign, $this->secretKey);
    }

    /**
     * Realizar petición HTTP a Flow
     */
    private function makeRequest(string $method, string $endpoint, array $params): array
    {
        try {
            $url = $this->apiUrl . $endpoint;
            
            Log::info('Flow API Request', [
                'method' => $method,
                'url' => $url,
                'params' => $this->sanitizeLogParams($params)
            ]);

            if ($method === 'GET') {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->get($url, $params);
            } else {
                $response = Http::timeout(30)
                    ->acceptJson()
                    ->asForm()
                    ->post($url, $params);
            }

            $statusCode = $response->status();
            $responseData = $response->json();
            
            Log::info('Flow API Response', [
                'status' => $statusCode,
                'response' => $responseData,
                'headers' => $response->headers()
            ]);

            if (!$response->successful()) {
                Log::error('Flow API Error Response', [
                    'status' => $statusCode,
                    'response' => $responseData,
                    'request_url' => $url,
                    'request_params' => $this->sanitizeLogParams($params)
                ]);
                
                // Verificar si es error de autenticación
                if ($statusCode === 403) {
                    throw new \Exception('Flow API: Credenciales inválidas o firma HMAC incorrecta');
                }
                
                throw new \Exception('Flow API request failed: HTTP ' . $statusCode . ' - ' . ($responseData['message'] ?? 'Unknown error'));
            }

            return $responseData ?? [];
            
        } catch (\Exception $e) {
            Log::error('Flow API Exception', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'method' => $method,
                'params' => $this->sanitizeLogParams($params)
            ]);
            
            throw $e;
        }
    }

    /**
     * Sanitizar parámetros para logs (ocultar datos sensibles)
     */
    private function sanitizeLogParams(array $params): array
    {
        $sanitized = $params;
        
        // Ocultar signature y API key en logs
        if (isset($sanitized['s'])) {
            $sanitized['s'] = '***';
        }
        if (isset($sanitized['apiKey'])) {
            $sanitized['apiKey'] = substr($sanitized['apiKey'], 0, 8) . '***';
        }
        
        return $sanitized;
    }

    /**
     * Validar webhook signature
     */
    public function validateWebhookSignature(array $params, string $receivedSignature): bool
    {
        $expectedSignature = $this->signParams($params);
        return hash_equals($expectedSignature, $receivedSignature);
    }
}