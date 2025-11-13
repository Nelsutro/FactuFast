<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class DebugInvoiceRequest
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo debuggear requests POST a invoices
        if ($request->isMethod('POST') && $request->is('api/invoices')) {
            Log::info('=== DEBUG INVOICE REQUEST ===');
            Log::info('URL: ' . $request->fullUrl());
            Log::info('Method: ' . $request->method());
            Log::info('Headers: ' . json_encode($request->headers->all()));
            Log::info('Body: ' . $request->getContent());
            Log::info('Parsed Input: ' . json_encode($request->all()));
            Log::info('Auth User: ' . ($request->user() ? $request->user()->id : 'null'));
            Log::info('=== END DEBUG ===');
        }

        return $next($request);
    }
}