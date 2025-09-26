<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Client;
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
            'client_id' => 'nullable|exists:clients,id',
            'client_name' => 'nullable|string',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'status' => 'sometimes|in:draft,pending,paid,overdue,cancelled',
            'notes' => 'nullable|string'
        ]);
        if (!$request->filled('client_id') && !$request->filled('client_name')) {
            return response()->json([
                'success' => false,
                'message' => 'Validación: debe indicar cliente',
                'errors' => ['client_name' => ['Debe proporcionar client_id o client_name']]
            ], 422);
        }

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

        // Resolver cliente (por id o por nombre)
        $client = null;
        if ($request->filled('client_id')) {
            $client = Client::find($request->input('client_id'));
        } elseif ($request->filled('client_name')) {
            $clientQuery = Client::query()->where('name', $request->input('client_name'));
            if ($user->isClient()) {
                $clientQuery->where('company_id', $user->company_id);
            } elseif ($request->filled('company_id')) {
                $clientQuery->where('company_id', $request->input('company_id'));
            }
            $matches = $clientQuery->get();
            if ($matches->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación: cliente no encontrado',
                    'errors' => [ 'client_name' => ['No se encontró un cliente con ese nombre'] ]
                ], 422);
            }
            if ($matches->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación: nombre ambiguo',
                    'errors' => [ 'client_name' => ['Nombre de cliente ambiguo, especifique client_id'] ]
                ], 422);
            }
            $client = $matches->first();
        }
        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Validación: cliente no encontrado',
                'errors' => [ 'client_name' => ['Cliente no encontrado'] ]
            ], 422);
        }

        $data = [
            'client_id' => $client->id,
            'invoice_number' => $invoiceNumber,
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'amount' => $amount,
            'status' => $request->input('status', 'draft'),
            'notes' => $request->input('notes')
        ];

        // Obtener el cliente para validar pertenencia y derivar company si aplica
        if ($user->isClient()) {
            // El usuario (empresa emisora) sólo puede facturar a clientes de su misma empresa
            if ($client->company_id !== $user->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso denegado',
                    'errors' => [ 'client_name' => ['El cliente seleccionado no pertenece a tu empresa'] ]
                ], 403);
            }
            $data['company_id'] = $user->company_id;
        } else {
            // Admin: si no envía company_id o envía uno distinto al del cliente, usar el del cliente
            $requestedCompany = $request->input('company_id');
            $data['company_id'] = $requestedCompany && $requestedCompany == $client->company_id
                ? $requestedCompany
                : $client->company_id;
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
            'client_id' => 'sometimes|nullable|exists:clients,id',
            'client_name' => 'sometimes|nullable|string',
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
        if (!$request->filled('client_id') && !$request->filled('client_name')) {
            return response()->json([
                'success' => false,
                'message' => 'Validación: debe indicar cliente',
                'errors' => ['client_name' => ['Debe proporcionar client_id o client_name']]
            ], 422);
        }

        if ($request->filled('client_name') && !$request->filled('client_id')) {
            $clientQuery = Client::query()->where('name', $request->input('client_name'));
            if ($user->isClient()) {
                $clientQuery->where('company_id', $user->company_id);
            } elseif ($request->filled('company_id')) {
                $clientQuery->where('company_id', $request->input('company_id'));
            }
            $matches = $clientQuery->get();
            if ($matches->count() === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró un cliente con ese nombre'
                ], 422);
            }
            if ($matches->count() > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nombre de cliente ambiguo, especifique client_id'
                ], 422);
            }
            $validated['client_id'] = $matches->first()->id;
        }

        // Si cambia el client_id validar pertenencia
        if (array_key_exists('client_id', $validated)) {
            $client = Client::find($validated['client_id']);
            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validación: cliente no encontrado',
                    'errors' => [ 'client_name' => ['Cliente no encontrado'] ]
                ], 422);
            }
            if ($user->isClient()) {
                if ($client->company_id !== $user->company_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Permiso denegado',
                        'errors' => [ 'client_name' => ['El cliente seleccionado no pertenece a tu empresa'] ]
                    ], 403);
                }
                // Forzar company_id a la empresa del usuario
                $validated['company_id'] = $user->company_id;
            } else {
                // Admin: si envía company_id inconsistente, sobreescribir
                $validated['company_id'] = $client->company_id;
            }
        }

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
