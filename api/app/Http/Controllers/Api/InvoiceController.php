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
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'status' => 'sometimes|in:draft,pending,paid,overdue,cancelled',
            'notes' => 'nullable|string'
        ]);

        // Generar número de factura si no viene
        $invoiceNumber = $request->input('invoice_number');
        if (!$invoiceNumber) {
            $invoiceNumber = 'INV-' . now()->format('YmdHis') . '-' . rand(100, 999);
        }

        // Calcular monto total desde items
        $itemsPayload = collect($request->input('items'));
        $amount = $itemsPayload->reduce(function($carry, $it) {
            return $carry + ((float)$it['quantity'] * (float)$it['price']);
        }, 0.0);

        $data = [
            'client_id' => $validated['client_id'],
            'invoice_number' => $invoiceNumber,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'amount' => $amount,
            'status' => $request->input('status', 'draft'),
            'notes' => $request->input('notes')
        ];

        // company_id según usuario autenticado
        if ($user->isClient()) {
            $data['company_id'] = $user->company_id;
        } else {
            $data['company_id'] = $request->input('company_id');
        }

        $invoice = Invoice::create($data);

        // Crear items si existen
        if ($itemsPayload->count()) {
            foreach ($itemsPayload as $row) {
                $invoice->items()->create([
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['price'],
                    'amount' => (float)$row['quantity'] * (float)$row['price']
                ]);
            }
        }

        $invoice->load(['client', 'company', 'items']);

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
            'due_date' => 'sometimes|date|after_or_equal:issue_date',
            'status' => 'sometimes|in:draft,pending,paid,overdue,cancelled',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array|min:1',
            'items.*.description' => 'required_with:items|string',
            'items.*.quantity' => 'required_with:items|numeric|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0'
        ]);

        $invoice->update($validated);

        // Si se envían items, reemplazar los existentes
        if ($request->has('items')) {
            $invoice->items()->delete();
            $itemsPayload = collect($request->input('items'));
            $amount = 0;
            foreach ($itemsPayload as $row) {
                $lineAmount = (float)$row['quantity'] * (float)$row['price'];
                $invoice->items()->create([
                    'description' => $row['description'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['price'],
                    'amount' => $lineAmount
                ]);
                $amount += $lineAmount;
            }
            $invoice->amount = $amount;
            $invoice->save();
        }

        $invoice->load(['client', 'company', 'items']);

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
