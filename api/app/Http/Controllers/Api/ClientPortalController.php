<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payments\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ClientPortalController extends Controller
{
    /**
     * Solicitar acceso al portal con email
     */
    public function requestAccess(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'company_tax_id' => 'sometimes|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email requerido',
                'errors' => $validator->errors()
            ], 400);
        }

        $clientQuery = Client::where('email', $request->email);
        if ($request->filled('company_tax_id')) {
            $clientQuery->whereHas('company', function($q) use ($request) {
                $q->where('tax_id', $request->company_tax_id);
            });
        }
        $client = $clientQuery->first();
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró un cliente con este email'
            ], 404);
        }

        // Reutilizar token vigente si existe para no invalidar enlaces anteriores
        if (!$client->access_token || !$client->access_token_expires_at || $client->access_token_expires_at->isPast()) {
            $token = $client->generateAccessToken();
        } else {
            $token = $client->access_token; // reutilizar
        }

        // TODO: Enviar email con el enlace de acceso
        // Mail::to($client->email)->send(new ClientAccessMail($client, $token));

        return response()->json([
            'success' => true,
            'message' => 'Se ha enviado (simulado) un enlace de acceso. Token incluido para entorno demo.',
            'access_link' => url("/cliente/portal?token={$token}&email={$client->email}"),
            'token' => $token,
            'expires_at' => $client->access_token_expires_at?->toDateTimeString()
        ]);
    }

    /**
     * Acceder al portal con token
     */
    public function accessPortal(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'company_tax_id' => 'sometimes|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email y token son requeridos',
                'errors' => $validator->errors()
            ], 400);
        }

        // Buscar directamente el registro cuyo token coincida para evitar tomar clientes antiguos duplicados
        $clientQuery = Client::where('email', $request->email)
            ->where('access_token', $request->token)
            ->whereNotNull('access_token_expires_at');
        if ($request->filled('company_tax_id')) {
            $clientQuery->whereHas('company', function($q) use ($request) {
                $q->where('tax_id', $request->company_tax_id);
            });
        }
        $client = $clientQuery->first();

        if (!$client || !$client->access_token_expires_at->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'Token inválido o expirado'
            ], 401);
        }

        // Actualizar último login
        $client->last_login_at = now();
        $client->save();

        return response()->json([
            'success' => true,
            'message' => 'Acceso autorizado',
            'data' => [
                'client' => $client->load('company'),
                'token' => $request->token
            ]
        ]);
    }

    /**
     * Obtener facturas del cliente
     */
    public function getInvoices(Request $request): JsonResponse
    {
        $client = $this->getClientFromToken($request);
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ], 401);
        }

        $invoices = $client->invoices()
                          ->with(['payments'])
                          ->orderBy('issue_date', 'desc')
                          ->get()
                          ->map(function ($invoice) {
                              return [
                                  'id' => $invoice->id,
                                  'invoice_number' => $invoice->invoice_number,
                                  'issue_date' => optional($invoice->issue_date)->format('Y-m-d'),
                                  'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                                  'total' => $invoice->total ?? 0,
                                  'status' => $invoice->status,
                                  'remaining_amount' => $invoice->remaining_amount ?? 0,
                                  'is_overdue' => $invoice->is_overdue,
                                  'payments' => $invoice->payments->where('status', 'completed')
                              ];
                          });

        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }

    /**
     * Obtener detalle de una factura específica
     */
    public function getInvoice(Request $request, $invoiceId): JsonResponse
    {
        $client = $this->getClientFromToken($request);
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ], 401);
        }

    $invoice = $client->invoices()
             ->with(['items', 'payments', 'company'])
                         ->find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'issue_date' => optional($invoice->issue_date)->format('Y-m-d'),
                'due_date' => optional($invoice->due_date)->format('Y-m-d'),
                'subtotal' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'notes' => $invoice->notes,
                'remaining_amount' => $invoice->remaining_amount,
                'is_overdue' => $invoice->is_overdue,
                'company' => $invoice->company,
                // Usamos la relación correcta definida en el modelo Invoice: items()
                'items' => $invoice->items,
                'payments' => $invoice->payments->where('status', 'completed')
            ]
        ]);
    }

    /**
     * Registrar un pago de factura
     */
    public function payInvoice(Request $request, $invoiceId): JsonResponse
    {
        $client = $this->getClientFromToken($request);
        
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ], 401);
        }

        $invoice = $client->invoices()->find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        }

        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Esta factura ya está pagada'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'provider' => 'sometimes|in:webpay,mercadopago',
            'return_url' => 'sometimes|url'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        $provider = $request->get('provider', 'webpay');
        $returnUrl = $request->get('return_url', url("/portal/pagos/completado"));

        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->initiatePayment($invoice, $provider, [
            'return_url' => $returnUrl,
            'callback_url' => url("/api/webhooks/payments/{$provider}"),
            'metadata' => [ 'client_id' => $client->id, 'invoice_id' => $invoice->id ]
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Intento de pago iniciado',
            'data' => [
                'payment_id' => $payment->id,
                'provider_payment_id' => $payment->provider_payment_id,
                'intent_status' => $payment->intent_status,
                'redirect_url' => $payment->redirect_url ?? null
            ]
        ], 201);
    }

    /**
     * Estado de un pago iniciado (polling desde portal)
     */
    public function paymentStatus(Request $request, Payment $payment): JsonResponse
    {
        $client = $this->getClientFromToken($request);
        if (!$client || $payment->client_id !== $client->id) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso no autorizado'
            ], 401);
        }
        /** @var PaymentService $paymentService */
        $paymentService = app(PaymentService::class);
        $paymentService->refreshStatus($payment->load('invoice.company'));
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $payment->id,
                'status' => $payment->status,
                'intent_status' => $payment->intent_status,
                'paid_at' => $payment->paid_at,
                'is_paid' => $payment->is_paid,
            ]
        ]);
    }

    /**
     * Obtener cliente desde el token en la request
     */
    private function getClientFromToken(Request $request): ?Client
    {
        $email = $request->header('X-Client-Email') ?? $request->get('email');
        $token = $request->header('X-Client-Token') ?? $request->get('token');

        if (!$email || !$token) {
            return null;
        }

        return Client::where('email', $email)
            ->where('access_token', $token)
            ->where('access_token_expires_at', '>', now())
            ->first();
    }
}
