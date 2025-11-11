<?php

namespace App\Http\Controllers;

use App\Services\FlowService;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\FlowCustomer;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class FlowPaymentController extends Controller
{
    protected FlowService $flowService;

    public function __construct(FlowService $flowService)
    {
        $this->flowService = $flowService;
    }

    /**
     * Crear un pago directo
     */
    public function createDirectPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
                'subject' => 'required|string|max:255',
                'email' => 'required|email',
                'optional' => 'nullable|string',
                'urlReturn' => 'required|url',
                'urlConfirmation' => 'required|url',
                'timeout' => 'nullable|integer|min:300|max:3600'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Datos de pago inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company = $request->user()->company;
            
            DB::beginTransaction();

            // Crear el pago en nuestra base de datos
            $payment = Payment::create([
                'company_id' => $company->id,
                'amount' => $request->amount,
                'subject' => $request->subject,
                'email' => $request->email,
                'optional' => $request->optional,
                'url_return' => $request->urlReturn,
                'url_confirmation' => $request->urlConfirmation,
                'timeout' => $request->timeout ?? 1800, // 30 minutos por defecto
                'status' => 'pending',
                'payment_type' => 'direct'
            ]);

            // Crear el pago en Flow
            $flowResponse = $this->flowService->createPayment([
                'commerceOrder' => (string) $payment->id,
                'subject' => $request->subject,
                'amount' => (int) $request->amount,
                'email' => $request->email,
                'paymentMethod' => 9, // Webpay
                'urlConfirmation' => $request->urlConfirmation,
                'urlReturn' => $request->urlReturn,
                'optional' => $request->optional,
                'timeout' => $request->timeout ?? 1800
            ]);

            if (!$flowResponse['success']) {
                DB::rollBack();
                Log::error('Error creando pago en Flow', [
                    'payment_id' => $payment->id,
                    'error' => $flowResponse['error']
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al crear el pago: ' . $flowResponse['error']
                ], 500);
            }

            // Actualizar el pago con los datos de Flow
            $payment->update([
                'flow_order' => $flowResponse['data']['flowOrder'],
                'token' => $flowResponse['data']['token'],
                'flow_response' => $flowResponse['data']
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pago creado exitosamente',
                'data' => [
                    'payment_id' => $payment->id,
                    'flow_order' => $payment->flow_order,
                    'token' => $payment->token,
                    'url' => $flowResponse['data']['url'] . '?token=' . $payment->token
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en createDirectPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear un cliente Flow para pagos recurrentes
     */
    public function createFlowCustomer(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'external_id' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'url_return' => 'required|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Datos del cliente inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company = $request->user()->company;

            // Verificar si el cliente ya existe
            $existingCustomer = FlowCustomer::where('company_id', $company->id)
                ->where('external_id', $request->external_id)
                ->first();

            if ($existingCustomer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El cliente ya existe',
                    'data' => [
                        'customer_id' => $existingCustomer->id,
                        'flow_customer_id' => $existingCustomer->flow_customer_id
                    ]
                ], 409);
            }

            DB::beginTransaction();

            // Crear el cliente en Flow
            $flowResponse = $this->flowService->createCustomer([
                'externalId' => $request->external_id,
                'name' => $request->name,
                'email' => $request->email,
                'urlReturn' => $request->url_return
            ]);

            if (!$flowResponse['success']) {
                DB::rollBack();
                Log::error('Error creando cliente en Flow', [
                    'external_id' => $request->external_id,
                    'error' => $flowResponse['error']
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al crear el cliente: ' . $flowResponse['error']
                ], 500);
            }

            // Crear el cliente en nuestra base de datos
            $customer = FlowCustomer::create([
                'company_id' => $company->id,
                'flow_customer_id' => $flowResponse['data']['customerId'],
                'external_id' => $request->external_id,
                'name' => $request->name,
                'email' => $request->email,
                'status' => 'active',
                'flow_response' => $flowResponse['data']
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Cliente creado exitosamente',
                'data' => [
                    'customer_id' => $customer->id,
                    'flow_customer_id' => $customer->flow_customer_id,
                    'register_url' => $flowResponse['data']['registerUrl']
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en createFlowCustomer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear un pago con cliente registrado
     */
    public function createCustomerPayment(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|exists:flow_customers,id',
                'amount' => 'required|numeric|min:1',
                'subject' => 'required|string|max:255',
                'optional' => 'nullable|string',
                'url_confirmation' => 'required|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Datos de pago inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company = $request->user()->company;
            
            // Verificar que el cliente pertenece a la empresa y tiene tarjeta registrada
            $customer = FlowCustomer::where('id', $request->customer_id)
                ->where('company_id', $company->id)
                ->where('has_registered_card', true)
                ->where('status', 'active')
                ->first();

            if (!$customer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cliente no encontrado o sin tarjeta registrada'
                ], 404);
            }

            DB::beginTransaction();

            // Crear el pago en nuestra base de datos
            $payment = Payment::create([
                'company_id' => $company->id,
                'flow_customer_id' => $customer->id,
                'amount' => $request->amount,
                'subject' => $request->subject,
                'email' => $customer->email,
                'optional' => $request->optional,
                'url_confirmation' => $request->url_confirmation,
                'status' => 'pending',
                'payment_type' => 'customer'
            ]);

            // Crear el pago en Flow usando el método de customer charge
            $flowResponse = $this->flowService->createCustomerPayment([
                'customerId' => $customer->flow_customer_id,
                'commerceOrder' => (string) $payment->id,
                'subject' => $request->subject,
                'amount' => (int) $request->amount,
                'urlConfirmation' => $request->url_confirmation,
                'optional' => $request->optional
            ]);

            if (!$flowResponse['success']) {
                DB::rollBack();
                Log::error('Error creando cargo de cliente en Flow', [
                    'payment_id' => $payment->id,
                    'customer_id' => $customer->flow_customer_id,
                    'error' => $flowResponse['error']
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al procesar el pago: ' . $flowResponse['error']
                ], 500);
            }

            // Actualizar el pago con los datos de Flow
            $payment->update([
                'flow_order' => $flowResponse['data']['flowOrder'],
                'flow_response' => $flowResponse['data']
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Pago procesado exitosamente',
                'data' => [
                    'payment_id' => $payment->id,
                    'flow_order' => $payment->flow_order,
                    'status' => $flowResponse['data']['status'] ?? 'pending'
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en createCustomerPayment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Consultar estado de un pago
     */
    public function getPaymentStatus(Request $request, $paymentId): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $payment = Payment::where('id', $paymentId)
                ->where('company_id', $company->id)
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pago no encontrado'
                ], 404);
            }

            // Si el pago ya está completado o cancelado, devolver estado actual
            if (in_array($payment->status, ['completed', 'cancelled', 'failed'])) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'payment_id' => $payment->id,
                        'status' => $payment->status,
                        'flow_order' => $payment->flow_order,
                        'amount' => $payment->amount,
                        'subject' => $payment->subject
                    ]
                ]);
            }

            // Consultar estado en Flow solo si tenemos token
            if ($payment->token) {
                $flowResponse = $this->flowService->getPaymentStatus($payment->token);

                if ($flowResponse['success']) {
                    $flowData = $flowResponse['data'];
                    
                    // Actualizar estado del pago
                    $status = $this->mapFlowStatus($flowData['status']);
                    $payment->update([
                        'status' => $status,
                        'flow_response' => array_merge($payment->flow_response ?? [], $flowData)
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'payment_id' => $payment->id,
                    'status' => $payment->status,
                    'flow_order' => $payment->flow_order,
                    'amount' => $payment->amount,
                    'subject' => $payment->subject
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getPaymentStatus', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Webhook de confirmación de Flow
     */
    public function flowConfirmation(Request $request): JsonResponse
    {
        try {
            Log::info('Webhook Flow recibido', ['data' => $request->all()]);

            // Validar parámetros requeridos
            if (!$request->has('token')) {
                Log::warning('Webhook Flow sin token', ['data' => $request->all()]);
                return response()->json(['status' => 'error', 'message' => 'Token requerido'], 400);
            }

            // Verificar firma HMAC
            $signature = $request->input('s') ?? $request->header('X-Flow-Signature');
            if (!$signature || !$this->flowService->validateWebhookSignature($request->all(), $signature)) {
                Log::warning('Webhook Flow con firma inválida', ['data' => $request->all()]);
                return response()->json(['status' => 'error', 'message' => 'Firma inválida'], 401);
            }

            $token = $request->input('token');
            
            // Buscar el pago por token
            $payment = Payment::where('token', $token)->first();

            if (!$payment) {
                Log::warning('Webhook Flow para pago no encontrado', ['token' => $token]);
                return response()->json(['status' => 'error', 'message' => 'Pago no encontrado'], 404);
            }

            // Consultar estado actual en Flow
            $flowResponse = $this->flowService->getPaymentStatus($token);

            if (!$flowResponse['success']) {
                Log::error('Error consultando estado en webhook', [
                    'token' => $token,
                    'error' => $flowResponse['error']
                ]);
                return response()->json(['status' => 'error', 'message' => 'Error consultando estado'], 500);
            }

            $flowData = $flowResponse['data'];
            
            // Actualizar estado del pago
            $status = $this->mapFlowStatus($flowData['status']);
            $payment->update([
                'status' => $status,
                'flow_response' => array_merge($payment->flow_response ?? [], $flowData),
                'confirmed_at' => now()
            ]);

            Log::info('Pago actualizado via webhook', [
                'payment_id' => $payment->id,
                'old_status' => $payment->getOriginal('status'),
                'new_status' => $status,
                'flow_status' => $flowData['status']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pago procesado'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en webhook Flow', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno'
            ], 500);
        }
    }

    /**
     * Listar pagos Flow de la empresa
     */
    public function listFlowPayments(Request $request): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $query = Payment::where('company_id', $company->id)
                ->whereNotNull('flow_order') // Solo pagos de Flow
                ->with('flowCustomer:id,name,email')
                ->orderBy('created_at', 'desc');

            // Filtros opcionales
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_type') && $request->payment_type) {
                $query->where('payment_type', $request->payment_type);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $payments = $query->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $payments
            ]);

        } catch (\Exception $e) {
            Log::error('Error en listFlowPayments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Mapear estados de Flow a nuestros estados
     */
    private function mapFlowStatus(int $flowStatus): string
    {
        return match($flowStatus) {
            1 => 'completed',        // PAYMENT_SUCCESS
            2 => 'failed',           // PAYMENT_PENDING
            3 => 'failed',           // PAYMENT_REJECTED  
            4 => 'cancelled',        // PAYMENT_CANCELLED
            default => 'pending'
        };
    }
}