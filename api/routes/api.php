<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\FlowPaymentController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ClientPortalController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\PublicPaymentLinkController;
use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\WebpayReturnController;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Api\ImportBatchController;
use App\Http\Controllers\Api\ApiTokenController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas (sin autenticación)
Route::get('test', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente',
        'timestamp' => now(),
        'environment' => app()->environment()
    ]);
});

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('check-session', [AuthController::class, 'checkSession']);
    Route::get('oauth/providers', [OAuthController::class, 'providers']);
    Route::get('oauth/{provider}/redirect', [OAuthController::class, 'redirect']);
    Route::match(['GET','POST'],'oauth/{provider}/callback', [OAuthController::class, 'callback']);
});

// Portal de clientes (sin autenticación Sanctum)
Route::prefix('client-portal')->group(function () {
    Route::post('request-access', [ClientPortalController::class, 'requestAccess']);
    Route::post('access', [ClientPortalController::class, 'accessPortal']);
    Route::get('invoices', [ClientPortalController::class, 'getInvoices']);
    Route::get('invoices/{invoice}', [ClientPortalController::class, 'getInvoice']);
    Route::post('invoices/{invoice}/pay', [ClientPortalController::class, 'payInvoice']);
    Route::get('payments/{payment}/status', [ClientPortalController::class, 'paymentStatus']);
});

// Rutas públicas de pago por enlace seguro
Route::prefix('public/pay')->group(function () {
    Route::get('{hash}', [PublicPaymentLinkController::class, 'show']);
    Route::post('{hash}/init', [PublicPaymentLinkController::class, 'initiate']);
    Route::get('{hash}/status', [PublicPaymentLinkController::class, 'status']);
});

// Webhooks de pagos (públicos, añadir verificación futura)
// Webhooks de pagos (validados por firma HMAC)
Route::post('webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle']);

// Webhooks Flow (públicos, validados por firma HMAC)
Route::prefix('webhooks/flow')->group(function () {
    Route::post('payment-confirmation', [FlowPaymentController::class, 'flowConfirmation']);
    Route::post('refund-confirmation', [RefundController::class, 'flowRefundConfirmation']);
});

// Return URL Webpay (commit)
Route::match(['GET','POST'],'payments/webpay/return', WebpayReturnController::class);

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth:sanctum', 'token.policies'])->group(function () {
    
    // Autenticación
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('user', [AuthController::class, 'user']);
        Route::get('me', [AuthController::class, 'me']);
    });

    // Configuración (por empresa del usuario)
    Route::get('settings', [SettingsController::class, 'index']);
    Route::put('settings', [SettingsController::class, 'update']);
    Route::post('settings/logo', [SettingsController::class, 'uploadLogo']);
    Route::prefix('settings')->group(function () {
        Route::get('api-tokens', [ApiTokenController::class, 'index']);
        Route::post('api-tokens', [ApiTokenController::class, 'store']);
        Route::delete('api-tokens/{token}', [ApiTokenController::class, 'destroy']);
        Route::get('api-tokens/{token}/logs', [ApiTokenController::class, 'logs']);
    });

    // Perfil (alias + update)
    Route::get('user', [UserController::class, 'show']);
    Route::put('user', [UserController::class, 'update']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);
        Route::get('revenue', [DashboardController::class, 'revenue']);
    });

    // Facturas (CSV primero para evitar colisión con invoices/{invoice})
    Route::get('invoices/export', [InvoiceController::class, 'export']);
    Route::post('invoices/import', [InvoiceController::class, 'import'])->middleware('abilities:api:import-invoices');
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/payment-link', [PublicPaymentLinkController::class, 'generate']);
    Route::get('invoices-stats', [InvoiceController::class, 'stats']);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid']);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);
    Route::post('invoices/{invoice}/email', [InvoiceController::class, 'sendEmail']);

    Route::get('import-batches', [ImportBatchController::class, 'index']);
    Route::get('import-batches/{batch}', [ImportBatchController::class, 'show']);
    Route::get('import-batches/{batch}/errors', [ImportBatchController::class, 'errors']);
    Route::get('import-batches/{batch}/errors/export', [ImportBatchController::class, 'downloadErrors'])->name('import-batches.errors.export');

    // Clientes
    Route::apiResource('clients', ClientController::class);
    Route::get('clients/export', [ClientController::class, 'export']);
    Route::post('clients/import', [ClientController::class, 'import']);

    // Cotizaciones (CSV primero para evitar colisión con quotes/{quote})
    Route::get('quotes/export', [QuoteController::class, 'export']);
    Route::post('quotes/import', [QuoteController::class, 'import'])->middleware('abilities:api:import-quotes');
    Route::apiResource('quotes', QuoteController::class);
    Route::get('quotes-stats', [QuoteController::class, 'stats']);
    Route::post('quotes/{quote}/convert', [QuoteController::class, 'convertToInvoice']);

    // Pagos
    Route::apiResource('payments', PaymentController::class);
    Route::get('payments-stats', [PaymentController::class, 'stats']);
    Route::get('invoices/{invoice}/payments', [PaymentController::class, 'getInvoicePayments']);
    
    // Pagos Flow - nuevos endpoints
    Route::prefix('flow')->group(function () {
        // Pagos directos
        Route::post('payments/direct', [FlowPaymentController::class, 'createDirectPayment']);
        Route::get('payments/{payment}/status', [FlowPaymentController::class, 'getPaymentStatus']);
        Route::get('payments', [FlowPaymentController::class, 'listFlowPayments']);
        
        // Clientes Flow para pagos recurrentes
        Route::post('customers', [FlowPaymentController::class, 'createFlowCustomer']);
        Route::post('customers/{customer}/charge', [FlowPaymentController::class, 'createCustomerPayment']);
        
        // Reembolsos
        Route::post('refunds', [RefundController::class, 'createRefund']);
        Route::get('refunds/{refund}/status', [RefundController::class, 'getRefundStatus']);
        Route::get('refunds', [RefundController::class, 'listRefunds']);
    });

    // Empresas
    Route::apiResource('companies', CompanyController::class);
    Route::get('companies/{company}/invoices', [CompanyController::class, 'invoices']);
});

// Rutas de prueba
Route::get('test', function () {
    return response()->json([
        'message' => 'API FactuFast funcionando correctamente',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

// Ruta temporal para probar datos sin autenticación
Route::get('test-data', function () {
    $companies = \App\Models\Company::count();
    $users = \App\Models\User::count();
    $clients = \App\Models\Client::count();
    $invoices = \App\Models\Invoice::count();
    $quotes = \App\Models\Quote::count();
    $payments = \App\Models\Payment::count();
    
    return response()->json([
        'message' => 'Datos de prueba',
        'counts' => [
            'companies' => $companies,
            'users' => $users,
            'clients' => $clients,
            'invoices' => $invoices,
            'quotes' => $quotes,
            'payments' => $payments
        ]
    ]);
});

// Ruta temporal para obtener facturas sin autenticación (solo para pruebas)
Route::get('test-invoices', function () {
    // Permitir especificar company_id desde el query parameter
    $companyId = request()->get('company_id', 1);
    $invoices = \App\Models\Invoice::with(['client', 'items'])
        ->where('company_id', $companyId)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    return response()->json([
        'success' => true,
        'data' => $invoices,
        'company_id' => $companyId,
        'total' => $invoices->count()
    ]);
});

// Ruta temporal para obtener cotizaciones sin autenticación (solo para pruebas)
Route::get('test-quotes', function () {
    // Permitir especificar company_id desde el query parameter
    $companyId = request()->get('company_id', 1);
    $quotes = \App\Models\Quote::with(['client', 'items'])
        ->where('company_id', $companyId)
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    return response()->json([
        'success' => true,
        'data' => $quotes,
        'company_id' => $companyId,
        'total' => $quotes->count()
    ]);
});

// Ruta temporal para obtener pagos sin autenticación (solo para pruebas)
Route::get('test-payments', function () {
    // Permitir especificar company_id desde el query parameter
    $companyId = request()->get('company_id', 1);
    $payments = \App\Models\Payment::with(['invoice', 'invoice.client'])
        ->whereHas('invoice', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
    
    return response()->json([
        'success' => true,
        'data' => $payments,
        'company_id' => $companyId,
        'total' => $payments->count()
    ]);
});

// Ruta temporal para obtener lista de empresas disponibles
Route::get('test-companies', function () {
    $companies = \App\Models\Company::select('id', 'name')->get();
    
    return response()->json([
        'success' => true,
        'data' => $companies
    ]);
});

// Ruta temporal para obtener usuarios de prueba (solo para desarrollo)
Route::get('test-users', function () {
    $users = \App\Models\User::with('company:id,name')->select('id', 'name', 'email', 'company_id')->get();
    
    return response()->json([
        'success' => true,
        'data' => $users
    ]);
});
