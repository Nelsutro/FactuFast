<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Payment::with(['invoice', 'invoice.client']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->whereHas('invoice', function($invoiceQuery) use ($user) {
                    $invoiceQuery->where('company_id', $user->company_id);
                });
            }

            // Aplicar filtros si se proporcionan
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('invoice_id')) {
                $query->where('invoice_id', $request->invoice_id);
            }

            if ($request->has('date_from')) {
                $query->whereDate('payment_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('payment_date', '<=', $request->date_to);
            }

            if ($request->has('amount_from')) {
                $query->where('amount', '>=', $request->amount_from);
            }

            if ($request->has('amount_to')) {
                $query->where('amount', '<=', $request->amount_to);
            }

            // Búsqueda por número de factura o nombre de cliente
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhereHas('invoice', function($invoiceQuery) use ($search) {
                          $invoiceQuery->where('invoice_number', 'like', "%{$search}%")
                                      ->orWhereHas('client', function($clientQuery) use ($search) {
                                          $clientQuery->where('name', 'like', "%{$search}%")
                                                     ->orWhere('email', 'like', "%{$search}%");
                                      });
                      });
                });
            }

            // Ordenamiento
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $payments = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $payments->items(),
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los pagos',
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
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => ['required', Rule::in(['cash', 'card', 'transfer', 'check', 'other'])],
                'payment_date' => 'required|date',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);

            // Verificar que la factura pertenezca a la empresa del usuario
            $invoice = Invoice::where('id', $validated['invoice_id']);
            if ($user->role !== 'admin') {
                $invoice->where('company_id', $user->company_id);
            }
            $invoice = $invoice->firstOrFail();

            // Verificar que la factura no esté cancelada
            if ($invoice->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede realizar un pago a una factura cancelada'
                ], 422);
            }

            // Calcular el monto total ya pagado
            $totalPaid = $invoice->payments()->sum('amount');
            $remainingAmount = $invoice->amount - $totalPaid;

            // Verificar que el pago no exceda el monto pendiente
            if ($validated['amount'] > $remainingAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto del pago excede el monto pendiente de {$remainingAmount}"
                ], 422);
            }

            DB::beginTransaction();

            // Crear el pago
            $payment = Payment::create([
                'invoice_id' => $validated['invoice_id'],
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'payment_date' => $validated['payment_date'],
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null
            ]);

            // Actualizar el estado de la factura si está completamente pagada
            $newTotalPaid = $totalPaid + $validated['amount'];
            if ($newTotalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } else if ($invoice->status === 'overdue' || $invoice->status === 'pending') {
                $invoice->update(['status' => 'pending']);
            }

            DB::commit();

            // Cargar el pago con sus relaciones
            $payment->load(['invoice', 'invoice.client']);

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Pago registrado exitosamente'
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
                'message' => 'Error al registrar el pago',
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
            $query = Payment::with(['invoice', 'invoice.client']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->whereHas('invoice', function($invoiceQuery) use ($user) {
                    $invoiceQuery->where('company_id', $user->company_id);
                });
            }

            $payment = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $payment
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el pago',
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
            $query = Payment::with(['invoice']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->whereHas('invoice', function($invoiceQuery) use ($user) {
                    $invoiceQuery->where('company_id', $user->company_id);
                });
            }

            $payment = $query->findOrFail($id);

            $validated = $request->validate([
                'amount' => 'sometimes|numeric|min:0.01',
                'payment_method' => ['sometimes', Rule::in(['cash', 'card', 'transfer', 'check', 'other'])],
                'payment_date' => 'sometimes|date',
                'reference' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);

            // Si se está actualizando el monto, verificar los límites
            if (isset($validated['amount']) && $validated['amount'] != $payment->amount) {
                $invoice = $payment->invoice;
                $otherPayments = $invoice->payments()->where('id', '!=', $payment->id)->sum('amount');
                $remainingAmount = $invoice->amount - $otherPayments;

                if ($validated['amount'] > $remainingAmount) {
                    return response()->json([
                        'success' => false,
                        'message' => "El monto del pago excede el monto pendiente de {$remainingAmount}"
                    ], 422);
                }
            }

            DB::beginTransaction();

            // Actualizar el pago
            $payment->update($validated);

            // Recalcular el estado de la factura
            $invoice = $payment->invoice;
            $totalPaid = $invoice->payments()->sum('amount');
            
            if ($totalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } else if ($invoice->status === 'paid') {
                $invoice->update(['status' => 'pending']);
            }

            DB::commit();

            // Cargar el pago actualizado con sus relaciones
            $payment->load(['invoice', 'invoice.client']);

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Pago actualizado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado'
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
                'message' => 'Error al actualizar el pago',
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
            $query = Payment::with(['invoice']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->whereHas('invoice', function($invoiceQuery) use ($user) {
                    $invoiceQuery->where('company_id', $user->company_id);
                });
            }

            $payment = $query->findOrFail($id);

            DB::beginTransaction();

            $invoice = $payment->invoice;
            
            // Eliminar el pago
            $payment->delete();

            // Recalcular el estado de la factura
            $totalPaid = $invoice->payments()->sum('amount');
            
            if ($totalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } else if ($totalPaid > 0) {
                $invoice->update(['status' => 'pending']);
            } else {
                // Si no hay pagos, determinar el estado basado en la fecha de vencimiento
                $today = now()->toDateString();
                if ($invoice->due_date < $today) {
                    $invoice->update(['status' => 'overdue']);
                } else {
                    $invoice->update(['status' => 'pending']);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment statistics for the current user's company
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Payment::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->whereHas('invoice', function($invoiceQuery) use ($user) {
                    $invoiceQuery->where('company_id', $user->company_id);
                });
            }

            $stats = [
                'total_payments' => $query->count(),
                'total_amount' => $query->sum('amount'),
                'cash_amount' => $query->where('payment_method', 'cash')->sum('amount'),
                'card_amount' => $query->where('payment_method', 'card')->sum('amount'),
                'transfer_amount' => $query->where('payment_method', 'transfer')->sum('amount'),
                'check_amount' => $query->where('payment_method', 'check')->sum('amount'),
                'other_amount' => $query->where('payment_method', 'other')->sum('amount'),
                'today_amount' => $query->whereDate('payment_date', today())->sum('amount'),
                'month_amount' => $query->whereYear('payment_date', now()->year)
                                      ->whereMonth('payment_date', now()->month)
                                      ->sum('amount'),
                'year_amount' => $query->whereYear('payment_date', now()->year)->sum('amount')
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

    /**
     * Get payments for a specific invoice
     */
    public function getInvoicePayments(string $invoiceId): JsonResponse
    {
        try {
            $user = Auth::user();
            
            // Verificar que la factura existe y pertenece a la empresa del usuario
            $invoiceQuery = Invoice::where('id', $invoiceId);
            if ($user->role !== 'admin') {
                $invoiceQuery->where('company_id', $user->company_id);
            }
            $invoice = $invoiceQuery->firstOrFail();

            $payments = Payment::where('invoice_id', $invoiceId)
                              ->orderBy('payment_date', 'desc')
                              ->get();

            $totalPaid = $payments->sum('amount');
            $remainingAmount = $invoice->amount - $totalPaid;

            return response()->json([
                'success' => true,
                'data' => [
                    'payments' => $payments,
                    'invoice_amount' => $invoice->amount,
                    'total_paid' => $totalPaid,
                    'remaining_amount' => $remainingAmount,
                    'is_fully_paid' => $remainingAmount <= 0
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los pagos de la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
