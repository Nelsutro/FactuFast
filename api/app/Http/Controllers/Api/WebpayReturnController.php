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

        // Actualizar registro
        $raw = $payment->raw_gateway_response ?? [];
        $raw['commit'] = $commitResult['raw'] ?? [];
        $payment->raw_gateway_response = $raw;
        if ($commitResult['paid']) {
            $payment->status = 'completed';
            $payment->paid_at = now();
            $payment->intent_status = 'authorized';
            // Marcar invoice pagada si el monto cubre
            $invoice->refresh();
            if ($invoice->remaining_amount <= 0) {
                $invoice->status = 'paid';
                $invoice->save();
            }
        } else {
            $payment->intent_status = $commitResult['status'];
        }
        $payment->save();

        // Redirigir a UI portal cliente (si definimos una ruta), por ahora JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'intent_status' => $payment->intent_status
            ]);
        }

        return redirect(url('/client-portal/invoice/' . $invoice->id . '?paid=1'));
    }
}
