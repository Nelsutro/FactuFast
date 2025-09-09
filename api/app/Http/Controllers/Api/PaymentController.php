<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Construir la consulta base
        $query = Payment::with(['client', 'company', 'invoice']);
        
        // Filtrar según el rol del usuario
        if ($user->isClient()) {
            // Los usuarios client solo ven pagos de su empresa
            $query->where('company_id', $user->company_id);
        }
        // Los admins ven todos los pagos
        
        $payments = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,credit_card,debit_card,bank_transfer,check,other',
            'transaction_id' => 'nullable|string',
            'status' => 'required|in:pending,completed,failed,cancelled',
            'notes' => 'nullable|string'
        ]);

        // Establecer company_id según el usuario
        if ($user->isClient()) {
            $validated['company_id'] = $user->company_id;
        } else {
            $validated['company_id'] = $request->input('company_id');
        }

        $payment = Payment::create($validated);
        $payment->load(['client', 'company', 'invoice']);

        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Pago registrado exitosamente'
        ], 201);
    }

    /**
     * Display the specified payment.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $payment->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver este pago'
            ], 403);
        }

        $payment->load(['client', 'company', 'invoice']);

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $payment->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar este pago'
            ], 403);
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'sometimes|numeric|min:0',
            'payment_date' => 'sometimes|date',
            'payment_method' => 'sometimes|in:cash,credit_card,debit_card,bank_transfer,check,other',
            'transaction_id' => 'nullable|string',
            'status' => 'sometimes|in:pending,completed,failed,cancelled',
            'notes' => 'nullable|string'
        ]);

        $payment->update($validated);
        $payment->load(['client', 'company', 'invoice']);

        return response()->json([
            'success' => true,
            'data' => $payment,
            'message' => 'Pago actualizado exitosamente'
        ]);
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $payment->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar este pago'
            ], 403);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pago eliminado exitosamente'
        ]);
    }
}
