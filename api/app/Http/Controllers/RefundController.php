<?php

namespace App\Http\Controllers;

use App\Services\FlowService;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    protected FlowService $flowService;

    public function __construct(FlowService $flowService)
    {
        $this->flowService = $flowService;
    }

    /**
     * Crear un reembolso
     */
    public function createRefund(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|exists:payments,id',
                'amount' => 'nullable|numeric|min:1',
                'reason' => 'required|string|max:255',
                'url_callback' => 'nullable|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Datos de reembolso inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company = $request->user()->company;
            
            // Buscar el pago
            $payment = Payment::where('id', $request->payment_id)
                ->where('company_id', $company->id)
                ->where('status', 'completed')
                ->first();

            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pago no encontrado o no está completado'
                ], 404);
            }

            // Verificar que el pago tenga flow_order
            if (!$payment->flow_order) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El pago no fue procesado por Flow'
                ], 400);
            }

            // Verificar monto del reembolso
            $refundAmount = $request->amount ?? $payment->amount;
            $totalRefunded = $payment->refunds()->where('status', 'completed')->sum('amount');
            $availableAmount = $payment->amount - $totalRefunded;

            if ($refundAmount > $availableAmount) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El monto del reembolso excede el disponible',
                    'data' => [
                        'available_amount' => $availableAmount,
                        'requested_amount' => $refundAmount
                    ]
                ], 400);
            }

            DB::beginTransaction();

            // Crear el reembolso en nuestra base de datos
            $refund = Refund::create([
                'payment_id' => $payment->id,
                'company_id' => $company->id,
                'amount' => $refundAmount,
                'reason' => $request->reason,
                'url_callback' => $request->url_callback,
                'status' => 'pending'
            ]);

            // Crear el reembolso en Flow
            $flowResponse = $this->flowService->createRefund([
                'flowOrder' => $payment->flow_order,
                'refundCommerceOrder' => (string) $refund->id,
                'amount' => (int) $refundAmount,
                'urlCallback' => $request->url_callback
            ]);

            if (!$flowResponse['success']) {
                DB::rollBack();
                Log::error('Error creando reembolso en Flow', [
                    'refund_id' => $refund->id,
                    'payment_id' => $payment->id,
                    'error' => $flowResponse['error']
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al crear el reembolso: ' . $flowResponse['error']
                ], 500);
            }

            // Actualizar el reembolso con los datos de Flow
            $refund->update([
                'flow_refund_order' => $flowResponse['data']['flowRefundOrder'],
                'flow_response' => $flowResponse['data']
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Reembolso creado exitosamente',
                'data' => [
                    'refund_id' => $refund->id,
                    'flow_refund_order' => $refund->flow_refund_order,
                    'amount' => $refund->amount,
                    'status' => $refund->status
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en createRefund', [
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
     * Consultar estado de un reembolso
     */
    public function getRefundStatus(Request $request, $refundId): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $refund = Refund::where('id', $refundId)
                ->where('company_id', $company->id)
                ->with('payment')
                ->first();

            if (!$refund) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reembolso no encontrado'
                ], 404);
            }

            // Si el reembolso ya está completado o cancelado, devolver estado actual
            if (in_array($refund->status, ['completed', 'cancelled', 'failed'])) {
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'refund_id' => $refund->id,
                        'status' => $refund->status,
                        'flow_refund_order' => $refund->flow_refund_order,
                        'amount' => $refund->amount,
                        'reason' => $refund->reason,
                        'payment' => [
                            'id' => $refund->payment->id,
                            'flow_order' => $refund->payment->flow_order,
                            'subject' => $refund->payment->subject
                        ]
                    ]
                ]);
            }

            // Consultar estado en Flow
            $flowResponse = $this->flowService->getRefundStatus($refund->flow_refund_order);

            if (!$flowResponse['success']) {
                Log::error('Error consultando estado de reembolso en Flow', [
                    'refund_id' => $refund->id,
                    'flow_refund_order' => $refund->flow_refund_order,
                    'error' => $flowResponse['error']
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error al consultar el estado del reembolso'
                ], 500);
            }

            $flowData = $flowResponse['data'];
            
            // Actualizar estado del reembolso
            $status = $this->mapFlowRefundStatus($flowData['status']);
            $refund->update([
                'status' => $status,
                'flow_response' => array_merge($refund->flow_response ?? [], $flowData)
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'refund_id' => $refund->id,
                    'status' => $refund->status,
                    'flow_refund_order' => $refund->flow_refund_order,
                    'amount' => $refund->amount,
                    'reason' => $refund->reason,
                    'flow_status' => $flowData['status'],
                    'payment' => [
                        'id' => $refund->payment->id,
                        'flow_order' => $refund->payment->flow_order,
                        'subject' => $refund->payment->subject
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error en getRefundStatus', [
                'refund_id' => $refundId,
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
     * Webhook de confirmación de reembolso de Flow
     */
    public function flowRefundConfirmation(Request $request): JsonResponse
    {
        try {
            Log::info('Webhook reembolso Flow recibido', ['data' => $request->all()]);

            // Validar parámetros requeridos
            if (!$request->has('flowRefundOrder')) {
                Log::warning('Webhook reembolso Flow sin flowRefundOrder', ['data' => $request->all()]);
                return response()->json(['status' => 'error', 'message' => 'flowRefundOrder requerido'], 400);
            }

            // Verificar firma HMAC
            $signature = $request->input('s') ?? $request->header('X-Flow-Signature');
            if (!$signature || !$this->flowService->validateWebhookSignature($request->all(), $signature)) {
                Log::warning('Webhook reembolso Flow con firma inválida', ['data' => $request->all()]);
                return response()->json(['status' => 'error', 'message' => 'Firma inválida'], 401);
            }

            $flowRefundOrder = $request->input('flowRefundOrder');
            
            // Buscar el reembolso por flow_refund_order
            $refund = Refund::where('flow_refund_order', $flowRefundOrder)->first();

            if (!$refund) {
                Log::warning('Webhook reembolso Flow para reembolso no encontrado', ['flowRefundOrder' => $flowRefundOrder]);
                return response()->json(['status' => 'error', 'message' => 'Reembolso no encontrado'], 404);
            }

            // Consultar estado actual en Flow
            $flowResponse = $this->flowService->getRefundStatus($flowRefundOrder);

            if (!$flowResponse['success']) {
                Log::error('Error consultando estado de reembolso en webhook', [
                    'flowRefundOrder' => $flowRefundOrder,
                    'error' => $flowResponse['error']
                ]);
                return response()->json(['status' => 'error', 'message' => 'Error consultando estado'], 500);
            }

            $flowData = $flowResponse['data'];
            
            // Actualizar estado del reembolso
            $status = $this->mapFlowRefundStatus($flowData['status']);
            $refund->update([
                'status' => $status,
                'flow_response' => array_merge($refund->flow_response ?? [], $flowData),
                'confirmed_at' => now()
            ]);

            Log::info('Reembolso actualizado via webhook', [
                'refund_id' => $refund->id,
                'old_status' => $refund->getOriginal('status'),
                'new_status' => $status,
                'flow_status' => $flowData['status']
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Reembolso procesado'
            ]);

        } catch (\Exception $e) {
            Log::error('Error en webhook reembolso Flow', [
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
     * Listar reembolsos de la empresa
     */
    public function listRefunds(Request $request): JsonResponse
    {
        try {
            $company = $request->user()->company;
            
            $query = Refund::where('company_id', $company->id)
                ->with(['payment:id,flow_order,subject,amount'])
                ->orderBy('created_at', 'desc');

            // Filtros opcionales
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            if ($request->has('payment_id') && $request->payment_id) {
                $query->where('payment_id', $request->payment_id);
            }

            if ($request->has('date_from') && $request->date_from) {
                $query->where('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->where('created_at', '<=', $request->date_to);
            }

            $refunds = $query->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $refunds
            ]);

        } catch (\Exception $e) {
            Log::error('Error en listRefunds', [
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
     * Mapear estados de reembolso de Flow a nuestros estados
     */
    private function mapFlowRefundStatus(int $flowStatus): string
    {
        return match($flowStatus) {
            1 => 'completed',        // REFUND_SUCCESS
            2 => 'pending',          // REFUND_PENDING
            3 => 'failed',           // REFUND_REJECTED
            4 => 'cancelled',        // REFUND_CANCELLED
            default => 'pending'
        };
    }
}