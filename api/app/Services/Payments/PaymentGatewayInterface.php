<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Inicializa un pago en el proveedor y retorna datos normalizados.
     * @param Invoice $invoice
     * @param Payment $payment Entidad Payment creada localmente en estado pending/created.
     * @param array $options callback_url, return_url, cancel_url, metadata
     * @return array [ 'provider_payment_id' => string, 'redirect_url' => string|null, 'status' => string, 'raw' => mixed ]
     */
    public function initiate(Invoice $invoice, Payment $payment, array $options = []): array;

    /**
     * Consulta el estado actual en el gateway.
     * Debe devolver status normalizado y posible marca de pago finalizado.
     * @return array [ 'status' => string, 'paid' => bool, 'raw' => mixed, 'paid_at' => ?\Carbon\CarbonInterface ]
     */
    public function retrieve(string $providerPaymentId): array;

    /**
     * Procesa payload entrante de webhook y retorna info para actualizar Payment.
     * @param array $payload
     * @return array [ 'provider_payment_id' => string, 'status' => string, 'paid' => bool, 'paid_at' => ?\Carbon\CarbonInterface, 'raw' => mixed ]
     */
    public function handleWebhook(array $payload): array;
}
