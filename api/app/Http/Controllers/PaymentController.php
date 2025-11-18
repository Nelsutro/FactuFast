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
            Log::info('PaymentController::index - Iniciando');
            
            $user = Auth::user();
            
            if (!$user) {
                Log::warning('PaymentController::index - Usuario no autenticado');
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            Log::info('PaymentController::index - Usuario autenticado: ' . $user->id);

            // Debugging: Obtener información detallada
            $totalPayments = Payment::count();
            $userCompanies = \App\Models\Company::where('user_id', $user->id)->get();
            $userCompanyIds = $userCompanies->pluck('id');
            
            Log::info('PaymentController::index - Total pagos en sistema: ' . $totalPayments);
            Log::info('PaymentController::index - Empresas del usuario: ' . $userCompanies->count());
            Log::info('PaymentController::index - IDs empresas: ' . $userCompanyIds->toJson());

            // Si es modo debug, retornar información adicional
            if ($request->get('debug') === '1') {
                $paymentsWithoutFilter = Payment::with(['invoice.client'])->take(5)->get();
                $companiesInfo = $userCompanies->map(function($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'payments_count' => Payment::where('company_id', $company->id)->count()
                    ];
                });
                
                return response()->json([
                    'debug_info' => [
                        'user_id' => $user->id,
                        'total_payments' => $totalPayments,
                        'user_companies' => $companiesInfo,
                        'sample_payments' => $paymentsWithoutFilter,
                        'all_companies' => \App\Models\Company::select('id', 'name', 'user_id')->get()
                    ]
                ]);
            }

            // Query base de pagos
            $query = Payment::select([
                'id', 'company_id', 'client_id', 'invoice_id', 'amount', 
                'payment_date', 'payment_method', 'status', 'reference',
                'subject', 'notes', 'created_at', 'updated_at'
            ]);

            // Filtrar por empresas del usuario
            if ($userCompanyIds->isNotEmpty()) {
                $query->whereIn('company_id', $userCompanyIds);
                $paymentsForUser = $query->count();
                Log::info('PaymentController::index - Pagos del usuario: ' . $paymentsForUser);
            } else {
                Log::info('PaymentController::index - Usuario sin empresas, devolviendo vacío');
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 15,
                        'total' => 0,
                        'from' => null,
                        'to' => null
                    ],
                    'message' => 'No hay empresas asociadas al usuario',
                    'debug' => [
                        'user_id' => $user->id,
                        'total_payments' => $totalPayments,
                        'user_companies_count' => 0
                    ]
                ]);
            }

            // Aplicar filtros
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
                Log::info('PaymentController::index - Filtro por status: ' . $request->status);
            }

            if ($request->has('payment_method') && !empty($request->payment_method)) {
                $query->where('payment_method', $request->payment_method);
                Log::info('PaymentController::index - Filtro por método: ' . $request->payment_method);
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSortFields = ['created_at', 'amount', 'status', 'payment_method', 'payment_date'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'created_at';
            }
            
            if (!in_array($sortOrder, ['asc', 'desc'])) {
                $sortOrder = 'desc';
            }
            
            $query->orderBy($sortBy, $sortOrder);
            Log::info("PaymentController::index - Ordenamiento: {$sortBy} {$sortOrder}");

            // Paginación
            $perPage = $request->get('per_page', 15);
            $perPage = max(1, min($perPage, 100));
            
            $payments = $query->paginate($perPage);
            Log::info('PaymentController::index - Pagos obtenidos: ' . $payments->total());

            // Cargar relaciones manualmente para cada pago
            $paymentsData = $payments->items();
            foreach ($paymentsData as $payment) {
                // Cargar invoice con client
                $invoice = \App\Models\Invoice::with('client')
                    ->find($payment->invoice_id);
                
                $payment->invoice = $invoice ? [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->amount,
                    'client' => $invoice->client ? [
                        'id' => $invoice->client->id,
                        'name' => $invoice->client->name,
                        'email' => $invoice->client->email
                    ] : [
                        'name' => 'Cliente no disponible',
                        'email' => ''
                    ]
                ] : [
                    'invoice_number' => 'N/A',
                    'amount' => 0,
                    'client' => [
                        'name' => 'Factura no encontrada',
                        'email' => ''
                    ]
                ];
                
                // Cargar company
                $company = \App\Models\Company::find($payment->company_id);
                $payment->company = $company ? [
                    'id' => $company->id,
                    'name' => $company->name
                ] : [
                    'name' => 'Empresa no disponible'
                ];
            }

            Log::info('PaymentController::index - Respuesta exitosa');

            return response()->json([
                'success' => true,
                'data' => $paymentsData,
                'pagination' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                    'from' => $payments->firstItem(),
                    'to' => $payments->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('PaymentController::index - Error: ' . $e->getMessage());
            Log::error('PaymentController::index - Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar los pagos',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile())
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