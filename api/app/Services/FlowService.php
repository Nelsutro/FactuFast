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

    public function __construct()
    {
        $this->apiKey = Config::get('services.flow.api_key');
        $this->secretKey = Config::get('services.flow.secret_key');
        $this->apiUrl = Config::get('services.flow.api_url');
        $this->environment = Config::get('services.flow.environment');

        if (!$this->apiKey || !$this->secretKey) {
            throw new \Exception('Flow API credentials not configured');
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
        
        // Ordenar parámetros alfabéticamente
        ksort($params);
        
        // Concatenar parámetros: nombre_parametro + valor
        $toSign = '';
        foreach ($params as $key => $value) {
            $toSign .= $key . $value;
        }
        
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
                $response = Http::timeout(30)->get($url, $params);
            } else {
                $response = Http::timeout(30)->asForm()->post($url, $params);
            }

            $responseData = $response->json();
            
            Log::info('Flow API Response', [
                'status' => $response->status(),
                'response' => $responseData
            ]);

            if (!$response->successful()) {
                throw new \Exception('Flow API request failed: ' . $response->status());
            }

            return $responseData ?? [];
            
        } catch (\Exception $e) {
            Log::error('Flow API Error', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'method' => $method
            ]);
            
            throw new \Exception('Flow API Error: ' . $e->getMessage());
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