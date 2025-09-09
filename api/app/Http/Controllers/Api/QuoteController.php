<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuoteController extends Controller
{
    /**
     * Display a listing of quotes.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Construir la consulta base
        $query = Quote::with(['client', 'company']);
        
        // Filtrar según el rol del usuario
        if ($user->isClient()) {
            // Los usuarios client solo ven cotizaciones de su empresa
            $query->where('company_id', $user->company_id);
        }
        // Los admins ven todas las cotizaciones
        
        $quotes = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $quotes
        ]);
    }

    /**
     * Store a newly created quote.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quote_number' => 'required|string|unique:quotes',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string'
        ]);

        // Establecer company_id según el usuario
        if ($user->isClient()) {
            $validated['company_id'] = $user->company_id;
        } else {
            $validated['company_id'] = $request->input('company_id');
        }

        $quote = Quote::create($validated);
        $quote->load(['client', 'company']);

        return response()->json([
            'success' => true,
            'data' => $quote,
            'message' => 'Cotización creada exitosamente'
        ], 201);
    }

    /**
     * Display the specified quote.
     */
    public function show(Request $request, Quote $quote): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $quote->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver esta cotización'
            ], 403);
        }

        $quote->load(['client', 'company']);

        return response()->json([
            'success' => true,
            'data' => $quote
        ]);
    }

    /**
     * Update the specified quote.
     */
    public function update(Request $request, Quote $quote): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $quote->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar esta cotización'
            ], 403);
        }

        $validated = $request->validate([
            'client_id' => 'sometimes|exists:clients,id',
            'quote_number' => 'sometimes|string|unique:quotes,quote_number,' . $quote->id,
            'issue_date' => 'sometimes|date',
            'expiry_date' => 'sometimes|date|after:issue_date',
            'subtotal' => 'sometimes|numeric|min:0',
            'tax_amount' => 'sometimes|numeric|min:0',
            'total' => 'sometimes|numeric|min:0',
            'status' => 'sometimes|in:draft,sent,accepted,rejected,expired',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string'
        ]);

        $quote->update($validated);
        $quote->load(['client', 'company']);

        return response()->json([
            'success' => true,
            'data' => $quote,
            'message' => 'Cotización actualizada exitosamente'
        ]);
    }

    /**
     * Remove the specified quote.
     */
    public function destroy(Request $request, Quote $quote): JsonResponse
    {
        $user = $request->user();
        
        // Verificar permisos
        if ($user->isClient() && $quote->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar esta cotización'
            ], 403);
        }

        $quote->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cotización eliminada exitosamente'
        ]);
    }
}
