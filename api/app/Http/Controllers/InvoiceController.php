<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Invoice::with(['client', 'items']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            // Aplicar filtros si se proporcionan
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('date_from')) {
                $query->whereDate('issue_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('issue_date', '<=', $request->date_to);
            }

            // Búsqueda por número de factura o nombre de cliente
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhereHas('client', function($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Ordenamiento
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las facturas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'client_id' => 'required|exists:clients,id',
                'invoice_number' => 'required|string|unique:invoices,invoice_number',
                'amount' => 'required|numeric|min:0',
                'status' => ['required', Rule::in(['draft', 'pending', 'paid', 'overdue', 'cancelled'])],
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.amount' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            // Crear la factura
            $invoice = Invoice::create([
                'company_id' => $user->company_id,
                'client_id' => $validated['client_id'],
                'invoice_number' => $validated['invoice_number'],
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null
            ]);

            // Crear los items de la factura
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount']
                ]);
            }

            DB::commit();

            // Cargar la factura con sus relaciones
            $invoice->load(['client', 'items']);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Factura creada exitosamente'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Invoice::with(['client', 'items', 'payments']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $invoice = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Invoice::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $invoice = $query->findOrFail($id);

            $validated = $request->validate([
                'client_id' => 'sometimes|exists:clients,id',
                'amount' => 'sometimes|numeric|min:0',
                'status' => ['sometimes', Rule::in(['draft', 'pending', 'paid', 'overdue', 'cancelled'])],
                'issue_date' => 'sometimes|date',
                'due_date' => 'sometimes|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'items' => 'sometimes|array|min:1',
                'items.*.description' => 'required_with:items|string',
                'items.*.quantity' => 'required_with:items|numeric|min:0.01',
                'items.*.unit_price' => 'required_with:items|numeric|min:0',
                'items.*.amount' => 'required_with:items|numeric|min:0'
            ]);

            DB::beginTransaction();

            // Actualizar la factura
            $invoice->update($validated);

            // Si se proporcionan items, reemplazar los existentes
            if (isset($validated['items'])) {
                // Eliminar items existentes
                $invoice->items()->delete();

                // Crear nuevos items
                foreach ($validated['items'] as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $item['amount']
                    ]);
                }
            }

            DB::commit();

            // Cargar la factura actualizada con sus relaciones
            $invoice->load(['client', 'items']);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Factura actualizada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Datos de entrada inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Invoice::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $invoice = $query->findOrFail($id);

            // Verificar si la factura puede ser eliminada
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una factura pagada'
                ], 422);
            }

            DB::beginTransaction();

            // Eliminar items de la factura (cascade delete)
            $invoice->items()->delete();
            
            // Eliminar la factura
            $invoice->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Factura eliminada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice statistics for the current user's company
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Invoice::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $stats = [
                'total' => $query->count(),
                'pending' => $query->where('status', 'pending')->count(),
                'paid' => $query->where('status', 'paid')->count(),
                'overdue' => $query->where('status', 'overdue')->count(),
                'total_amount' => $query->sum('amount'),
                'pending_amount' => $query->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'paid_amount' => $query->where('status', 'paid')->sum('amount')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
