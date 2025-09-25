<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\SignedPaymentLink;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicPaymentLinkController extends Controller
{
    public function show(string $hash): JsonResponse
    {
        $parsed = SignedPaymentLink::parse($hash);
        if (!$parsed) {
            return response()->json(['success' => false, 'message' => 'Link inválido o expirado'], 410);
        }
        $invoice = Invoice::where('id', $parsed['invoice_id'])
            ->where('company_id', $parsed['company_id'])
            ->with('company')
            ->first();
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
        }
        return response()->json([
            'success' => true,
            'data' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->format('Y-m-d'),
                'total' => $invoice->amount, // TODO: usar total calculado si hay items
                'company' => [
                    'name' => $invoice->company->name ?? null,
                    'tax_id' => $invoice->company->tax_id ?? null,
                ],
                'is_paid' => $invoice->status === 'paid',
                'expires_at' => $parsed['expires_at']
            ]
        ]);
    }

    public function initiate(string $hash, Request $request): JsonResponse
    {
        $parsed = SignedPaymentLink::parse($hash);
        if (!$parsed) {
            return response()->json(['success' => false, 'message' => 'Link inválido o expirado'], 410);
        }
        $invoice = Invoice::where('id', $parsed['invoice_id'])
            ->where('company_id', $parsed['company_id'])
            ->with('company')
            ->first();
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Factura no encontrada'], 404);
        }
        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Factura ya pagada'], 409);
        }

        $provider = $request->get('provider', 'webpay');
        /** @var PaymentService $svc */
        $svc = app(PaymentService::class);
        $payment = $svc->initiatePayment($invoice, $provider, [
            'return_url' => $request->get('return_url', url('/portal/pagos/completado')),
            'callback_url' => url("/api/webhooks/payments/{$provider}"),
            'metadata' => [ 'public_link' => true ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Intento de pago iniciado',
            'data' => [
                'payment_id' => $payment->id,
                'provider_payment_id' => $payment->provider_payment_id,
                'intent_status' => $payment->intent_status,
                'redirect_url' => $payment->redirect_url
            ]
        ], 201);
    }

    public function generate(Request $request, Invoice $invoice): JsonResponse
    {
        // Autenticado (protegido por middleware en rutas), verificar multi-tenant en caso necesario luego.
        $ttl = (int) $request->get('ttl', 86400); // 24h
        $linkHash = SignedPaymentLink::generate($invoice->id, $invoice->company_id, $ttl);
        return response()->json([
            'success' => true,
            'data' => [
                'hash' => $linkHash,
                'public_url' => url("/api/public/pay/{$linkHash}"),
                'expires_at' => now()->addSeconds($ttl)->timestamp
            ]
        ]);
    }
}
