<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Services\Payments\PaymentService;
use App\Models\WebhookEvent;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $provider)
    {
        $rawBody = $request->getContent();
        $signatureHeader = $request->header('X-Signature') ?? $request->header('X-Hmac-Signature');
        $timestamp = $request->header('X-Signature-Timestamp');

        $secret = Config::get('services.payment_webhooks.secret');
        if (!$secret) {
            Log::warning('Webhook secret no configurado');
            return response()->json(['success' => false, 'message' => 'Misconfiguration'], 500);
        }

        // Protección básica anti-replay (5 minutos)
        if ($timestamp && abs(time() - (int)$timestamp) > 300) {
            return response()->json(['success' => false, 'message' => 'Timestamp fuera de rango'], 400);
        }

        if (!$this->isValidSignature($rawBody, $signatureHeader, $secret, $timestamp)) {
            Log::warning('Firma webhook inválida', ['provider' => $provider]);
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 401);
        }

        $payload = $request->all();

        // Guardar evento crudo
        $event = WebhookEvent::create([
            'provider' => $provider,
            'event_type' => $payload['type'] ?? $payload['event'] ?? 'unknown',
            'payload' => $payload,
            'signature' => $signatureHeader,
            'received_at' => now(),
        ]);

        /** @var PaymentService $service */
        $service = app(PaymentService::class);
        $payment = $service->applyWebhook($provider, $payload);

        if ($payment) {
            $event->processed_at = now();
            $event->related_id = $payment->id;
            $event->status = 'processed';
            $event->save();
        }

        return response()->json([
            'success' => (bool)$payment,
            'payment_id' => $payment?->id,
            'status' => $payment?->status,
            'event_id' => $event->id
        ]);
    }

    private function isValidSignature(string $rawBody, ?string $signatureHeader, string $secret, ?string $timestamp): bool
    {
        if (!$signatureHeader) {
            return false;
        }
        // Formato esperado: sha256=hexfirma  (o solo hexfirma)
        $provided = str_contains($signatureHeader, '=') ? explode('=', $signatureHeader, 2)[1] : $signatureHeader;
        $base = $timestamp ? ($timestamp . '.' . $rawBody) : $rawBody;
        $calc = hash_hmac('sha256', $base, $secret);
        return hash_equals($calc, $provided);
    }
}
