<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Construir la consulta base
        $query = Invoice::with(['client', 'company']);
        
        // Filtrar según el rol del usuario
        if ($user->isClient()) {
            // Los usuarios client solo ven facturas de su empresa
            $query->where('company_id', $user->company_id);
        }
        // Los admins ven todas las facturas
        
        $invoices = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $invoices
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => 'required|string|unique:invoices',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after:issue_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string'
        ]);

        // Establecer company_id según el usuario
        if ($user->isClient()) {
            $validated['company_id'] = $user->company_id;
        } else {
            $validated['company_id'] = $request->input('company_id');
        }

        $invoice = Invoice::create($validated);
        $invoice->load(['client', 'company']);

        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Factura creada exitosamente'
        ], 201);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $invoice->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver esta factura'
            ], 403);
        }

        $invoice->load(['client', 'company', 'payments']);

        return response()->json([
            'success' => true,
            'data' => $invoice
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $invoice->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar esta factura'
            ], 403);
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'invoice_number' => 'sometimes|string|unique:invoices,invoice_number,' . $invoice->id,
            'issue_date' => 'sometimes|date',
            'due_date' => 'sometimes|date|after:issue_date',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax_amount' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'payment_terms' => 'nullable|string'
        ]);

        $invoice->update($validated);
        $invoice->load(['client', 'company']);

        return response()->json([
            'success' => true,
            'data' => $invoice,
            'message' => 'Factura actualizada exitosamente'
        ]);
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Request $request, Invoice $invoice): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $invoice->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar esta factura'
            ], 403);
        }

        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Factura eliminada exitosamente'
        ]);
    }
}
