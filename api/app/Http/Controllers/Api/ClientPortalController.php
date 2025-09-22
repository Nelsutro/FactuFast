<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
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

        // Generar token de acceso
        $token = $client->generateAccessToken();

        // TODO: Enviar email con el enlace de acceso
        // Mail::to($client->email)->send(new ClientAccessMail($client, $token));

        return response()->json([
            'success' => true,
            'message' => 'Se ha enviado un enlace de acceso a tu email',
            'access_link' => url("/cliente/portal?token={$token}&email={$client->email}")
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

        $clientQuery = Client::where('email', $request->email);
        if ($request->filled('company_tax_id')) {
            $clientQuery->whereHas('company', function($q) use ($request) {
                $q->where('tax_id', $request->company_tax_id);
            });
        }
        $client = $clientQuery->first();
        
        if (!$client || !$client->isTokenValid($request->token)) {
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
                                  'issue_date' => $invoice->issue_date->format('Y-m-d'),
                                  'due_date' => $invoice->due_date->format('Y-m-d'),
                                  'total' => $invoice->total,
                                  'status' => $invoice->status,
                                  'remaining_amount' => $invoice->remaining_amount,
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
                         ->with(['invoiceItems', 'payments', 'company'])
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
                'issue_date' => $invoice->issue_date->format('Y-m-d'),
                'due_date' => $invoice->due_date->format('Y-m-d'),
                'subtotal' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'notes' => $invoice->notes,
                'remaining_amount' => $invoice->remaining_amount,
                'is_overdue' => $invoice->is_overdue,
                'company' => $invoice->company,
                'items' => $invoice->invoiceItems,
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
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->remaining_amount,
            'payment_method' => 'required|in:credit_card,debit_card,bank_transfer,other',
            'transaction_id' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de pago inválidos',
                'errors' => $validator->errors()
            ], 400);
        }

        // Crear el registro de pago
        $payment = Payment::create([
            'company_id' => $invoice->company_id,
            'client_id' => $client->id,
            'invoice_id' => $invoice->id,
            'amount' => $request->amount,
            'payment_date' => now(),
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'status' => 'completed', // En un sistema real, esto sería 'pending' hasta confirmar el pago
            'notes' => 'Pago realizado desde el portal del cliente'
        ]);

        // Verificar si la factura está completamente pagada
        $totalPaid = $invoice->payments()->where('status', 'completed')->sum('amount');
        if ($totalPaid >= $invoice->total) {
            $invoice->update(['status' => 'paid']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pago registrado exitosamente',
            'data' => [
                'payment' => $payment,
                'invoice_status' => $invoice->fresh()->status,
                'remaining_amount' => $invoice->fresh()->remaining_amount
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

        $client = Client::where('email', $email)->first();
        
        if (!$client || !$client->isTokenValid($token)) {
            return null;
        }

        return $client;
    }
}
