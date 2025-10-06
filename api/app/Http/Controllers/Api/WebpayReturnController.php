<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Services\Payments\WebpayGateway;
use App\Services\Payments\PaymentService;

class WebpayReturnController extends Controller
{
    public function __construct(private PaymentService $payments)
    {
    }

    public function __invoke(Request $request)
    {
        $token = $request->get('token_ws');
        if (!$token) {
            return response()->json(['success' => false, 'message' => 'Token no recibido'], 400);
        }

        // Buscar payment por provider_payment_id (token)
        $payment = Payment::where('provider_payment_id', $token)
            ->where('payment_provider', 'webpay')
            ->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Pago no encontrado'], 404);
        }

        $invoice = $payment->invoice()->with('company')->first();
        $gateway = WebpayGateway::fromCompany($invoice->company);
        $commitResult = $gateway->commit($token);
        $commitResult['raw'] = array_merge(
            ['commit_timestamp' => now()->toIso8601String()],
            $commitResult['raw'] ?? []
        );

        $payment = $this->payments->applyGatewayResult($payment->load('invoice.payments'), $commitResult);

        $status = $payment->status;
        $intent = $payment->intent_status;
        $isPaid = $payment->status === 'completed';

        // Redirigir a UI portal cliente (si definimos una ruta), por ahora JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $status,
                'intent_status' => $intent,
                'paid' => $isPaid,
            ]);
        }

        $query = http_build_query([
            'paid' => $isPaid ? '1' : '0',
            'status' => $status,
            'intent' => $intent,
            'payment_id' => $payment->id,
            'provider' => 'webpay'
        ]);

        return redirect(url('/client-portal/invoice/' . $invoice->id) . '?' . $query);
    }
}
