<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceivedClientMail;
use App\Mail\PaymentReceivedCompanyMail;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            $query = Payment::with(['invoice.client', 'company'])
                ->whereHas('company', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });

            if ($request->has('status') && !empty($request->status)) {
                $status = $request->status;
                if ($status === 'paid') {
                    $query->completed();
                } elseif ($status === 'pending') {
                    $query->pending();
                } elseif ($status === 'unpaid') {
                    $query->unpaid();
                } elseif ($status === 'failed') {
                    $query->failed();
                } else {
                    $query->where('status', $status);
                }
            }

            if ($request->has('invoice_id') && !empty($request->invoice_id)) {
                $query->where('invoice_id', $request->invoice_id);
            }

            if ($request->has('payment_method') && !empty($request->payment_method)) {
                $query->where('payment_method', $request->payment_method);
            }

            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
            }

            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhere('subject', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhereHas('invoice', function($invoiceQuery) use ($search) {
                          $invoiceQuery->where('invoice_number', 'like', "%{$search}%")
                                      ->orWhereHas('client', function($clientQuery) use ($search) {
                                          $clientQuery->where('name', 'like', "%{$search}%")
                                                     ->orWhere('email', 'like', "%{$search}%");
                                      });
                      });
                });
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['created_at', 'amount', 'status', 'payment_method', 'reference'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $perPage = max(1, min($perPage, 100));
            
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
            Log::error('Error fetching payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pagos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => 'required|numeric|min:0.01',
                'payment_method' => 'required|string|max:255',
                'reference' => 'nullable|string|max:255',
                'subject' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            $invoice = Invoice::with(['company', 'client'])
                ->whereHas('company', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($validated['invoice_id']);

            if ($invoice->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede realizar un pago a una factura cancelada'
                ], 422);
            }

            $totalPaid = $invoice->payments()->sum('amount');
            $remainingAmount = $invoice->amount - $totalPaid;

            if ($validated['amount'] > $remainingAmount) {
                return response()->json([
                    'success' => false,
                    'message' => "El monto del pago excede el monto pendiente de {$remainingAmount}"
                ], 422);
            }

            DB::beginTransaction();

            $payment = Payment::create([
                'invoice_id' => $validated['invoice_id'],
                'company_id' => $invoice->company_id,
                'amount' => $validated['amount'],
                'payment_method' => $validated['payment_method'],
                'reference' => $validated['reference'],
                'subject' => $validated['subject'],
                'notes' => $validated['notes'] ?? null,
                'status' => 'completed'
            ]);

            $newTotalPaid = $totalPaid + $validated['amount'];
            if ($newTotalPaid >= $invoice->amount) {
                $invoice->update(['status' => 'paid']);
            } elseif ($newTotalPaid > 0 && $invoice->status === 'pending') {
                $invoice->update(['status' => 'partially_paid']);
            }

            DB::commit();

            $payment->load(['invoice.client', 'company']);

            $invoice = $payment->invoice;
            $sendEnabled = $invoice->company?->send_email_on_payment ?? true;

            if ($sendEnabled) {
                if (!empty($invoice->client?->email)) {
                    Mail::to($invoice->client->email)->send(new PaymentReceivedClientMail($payment));
                }

                if (!empty($invoice->company?->email)) {
                    Mail::to($invoice->company->email)->send(new PaymentReceivedCompanyMail($payment));
                }
            }

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Pago registrado exitosamente'
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $payment = Payment::with(['invoice.client', 'company'])
                ->whereHas('company', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($id);

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
            Log::error('Error fetching payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $payment = Payment::with(['invoice', 'company'])
                ->whereHas('company', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($id);

            $validator = Validator::make($request->all(), [
                'reference' => 'nullable|string|max:255',
                'subject' => 'nullable|string|max:255',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $payment->update($validated);
            $payment->load(['invoice.client', 'company']);

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Pago actualizado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Error updating payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = Auth::user();

            $payment = Payment::with(['invoice'])
                ->whereHas('company', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->findOrFail($id);

            if (!empty($payment->flow_transaction_id) || !empty($payment->flow_token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pueden eliminar pagos procesados por Flow'
                ], 422);
            }

            DB::beginTransaction();

            $invoice = $payment->invoice;
            $payment->delete();

            $totalPaid = $invoice->payments()->sum('amount');
            
            if ($totalPaid == 0) {
                $invoice->update(['status' => 'pending']);
            } elseif ($totalPaid < $invoice->amount) {
                $invoice->update(['status' => 'partially_paid']);
            } else {
                $invoice->update(['status' => 'paid']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pago eliminado exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Pago no encontrado'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting payment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el pago',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}