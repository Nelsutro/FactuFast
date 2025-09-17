<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Client;

class QuoteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Quote::with(['client', 'items']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            // Aplicar filtros si se proporcionan
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            if ($request->has('date_from')) {
                $query->whereDate('quote_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('quote_date', '<=', $request->date_to);
            }

            // Búsqueda por número de cotización o nombre de cliente
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('quote_number', 'like', "%{$search}%")
                      ->orWhereHas('client', function($clientQuery) use ($search) {
                          $clientQuery->where('name', 'like', "%{$search}%")
                                     ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Ordenamiento
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $quotes = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $quotes->items(),
                'pagination' => [
                    'current_page' => $quotes->currentPage(),
                    'last_page' => $quotes->lastPage(),
                    'per_page' => $quotes->perPage(),
                    'total' => $quotes->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las cotizaciones',
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
                'client_id' => 'required|exists:clients,id',
                'quote_number' => 'required|string|unique:quotes,quote_number',
                'amount' => 'required|numeric|min:0',
                'status' => ['required', Rule::in(['draft', 'pending', 'approved', 'rejected', 'expired'])],
                'quote_date' => 'required|date',
                'expiry_date' => 'required|date|after:quote_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.amount' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            // Crear la cotización
            $quote = Quote::create([
                'company_id' => $user->company_id,
                'client_id' => $validated['client_id'],
                'quote_number' => $validated['quote_number'],
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'quote_date' => $validated['quote_date'],
                'expiry_date' => $validated['expiry_date'],
                'notes' => $validated['notes'] ?? null
            ]);

            // Crear los items de la cotización
            foreach ($validated['items'] as $item) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount']
                ]);
            }

            DB::commit();

            // Cargar la cotización con sus relaciones
            $quote->load(['client', 'items']);

            return response()->json([
                'success' => true,
                'data' => $quote,
                'message' => 'Cotización creada exitosamente'
            ], 201);

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
                'message' => 'Error al crear la cotización',
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
            $query = Quote::with(['client', 'items']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $quote = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $quote
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la cotización',
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
            $query = Quote::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $quote = $query->findOrFail($id);

            $validated = $request->validate([
                'client_id' => 'sometimes|exists:clients,id',
                'amount' => 'sometimes|numeric|min:0',
                'status' => ['sometimes', Rule::in(['draft', 'pending', 'approved', 'rejected', 'expired'])],
                'quote_date' => 'sometimes|date',
                'expiry_date' => 'sometimes|date|after:quote_date',
                'notes' => 'nullable|string',
                'items' => 'sometimes|array|min:1',
                'items.*.description' => 'required_with:items|string',
                'items.*.quantity' => 'required_with:items|numeric|min:0.01',
                'items.*.unit_price' => 'required_with:items|numeric|min:0',
                'items.*.amount' => 'required_with:items|numeric|min:0'
            ]);

            DB::beginTransaction();

            // Actualizar la cotización
            $quote->update($validated);

            // Si se proporcionan items, reemplazar los existentes
            if (isset($validated['items'])) {
                // Eliminar items existentes
                $quote->items()->delete();

                // Crear nuevos items
                foreach ($validated['items'] as $item) {
                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $item['amount']
                    ]);
                }
            }

            DB::commit();

            // Cargar la cotización actualizada con sus relaciones
            $quote->load(['client', 'items']);

            return response()->json([
                'success' => true,
                'data' => $quote,
                'message' => 'Cotización actualizada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
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
                'message' => 'Error al actualizar la cotización',
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
            $query = Quote::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $quote = $query->findOrFail($id);

            // Verificar si la cotización puede ser eliminada
            if ($quote->status === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una cotización aprobada'
                ], 422);
            }

            DB::beginTransaction();

            // Eliminar items de la cotización (cascade delete)
            $quote->items()->delete();
            
            // Eliminar la cotización
            $quote->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cotización eliminada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get quote statistics for the current user's company
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Quote::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $stats = [
                'total' => $query->count(),
                'pending' => $query->where('status', 'pending')->count(),
                'approved' => $query->where('status', 'approved')->count(),
                'rejected' => $query->where('status', 'rejected')->count(),
                'expired' => $query->where('status', 'expired')->count(),
                'total_amount' => $query->sum('amount'),
                'pending_amount' => $query->where('status', 'pending')->sum('amount'),
                'approved_amount' => $query->where('status', 'approved')->sum('amount')
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
     * Convert quote to invoice
     */
    public function convertToInvoice(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Quote::with(['items']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $quote = $query->findOrFail($id);

            // Verificar que la cotización esté aprobada
            if ($quote->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden convertir cotizaciones aprobadas'
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'quote' => $quote,
                    'invoice_template' => [
                        'client_id' => $quote->client_id,
                        'amount' => $quote->amount,
                        'items' => $quote->items->toArray()
                    ]
                ],
                'message' => 'Datos preparados para crear la factura'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cotización no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la cotización',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export quotes to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        }
        if (!in_array($user->role, ['admin','client'])) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos para exportar cotizaciones'], 403);
        }

        $query = Quote::with(['client:id,email'])
            ->select(['id','company_id','client_id','quote_number','amount','status','valid_until','notes'])
            ->orderBy('company_id')->orderBy('created_at','desc');
        if ($user->role !== 'admin') {
            $query->where('company_id', $user->company_id);
        }
        $quotes = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="quotes_export_'.now()->format('Ymd_His').'.csv"',
        ];
    $columns = ['quote_number','client_email','amount','status','valid_until','notes'];

        $callback = function() use ($quotes, $columns) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, $columns);
            foreach ($quotes as $q) {
                fputcsv($output, [
                    $q->quote_number,
                    optional($q->client)->email,
                    (string)$q->amount,
                    $q->status,
                    $q->valid_until?->format('Y-m-d'),
                    $q->notes,
                ]);
            }
            fclose($output);
        };
        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Import quotes from CSV
     */
    public function import(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'client' || !$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Solo las empresas pueden importar cotizaciones'
            ], 403);
        }
        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'No se adjuntó archivo CSV (campo "file")'
            ], 422);
        }
        $file = $request->file('file');
        if (!$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo inválido'
            ], 422);
        }

        $created = 0; $skipped = 0; $errors = [];
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $row = 0; $header = null;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $row++;
                if ($row === 1) {
                    $maybeHeader = array_map(fn($h) => strtolower(trim($h)), $data);
                    if (in_array('quote_number', $maybeHeader)) { $header = $maybeHeader; continue; }
                }
                if ($header) {
                    $map = array_combine($header, $data + array_fill(0, max(0, count($header)-count($data)), ''));
                    $quoteNumber = trim($map['quote_number'] ?? '');
                    $clientEmail = trim($map['client_email'] ?? '');
                    $amount = trim($map['amount'] ?? '');
                    $status = strtolower(trim($map['status'] ?? 'draft'));
                    $validUntil = trim($map['valid_until'] ?? '');
                    $notes = trim($map['notes'] ?? '');
                } else {
                    // Posicional: quote_number,client_email,amount,status,valid_until,notes
                    $quoteNumber = trim($data[0] ?? '');
                    $clientEmail = trim($data[1] ?? '');
                    $amount = trim($data[2] ?? '');
                    $status = strtolower(trim($data[3] ?? 'draft'));
                    $validUntil = trim($data[4] ?? '');
                    $notes = trim($data[5] ?? '');
                }

                if ($quoteNumber === '') { $skipped++; $errors[] = "Fila $row: quote_number vacío"; continue; }
                if ($clientEmail === '') { $skipped++; $errors[] = "Fila $row: client_email vacío"; continue; }
                if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) { $skipped++; $errors[] = "Fila $row: email inválido ($clientEmail)"; continue; }
                if ($amount === '' || !is_numeric($amount)) { $skipped++; $errors[] = "Fila $row: amount inválido"; continue; }
                if (!in_array($status, ['draft','sent','accepted','rejected','expired'])) { $skipped++; $errors[] = "Fila $row: status inválido ($status)"; continue; }
                $valid_until_parsed = $validUntil ? date_create($validUntil) : null;

                $client = Client::where('company_id', $user->company_id)->where('email', $clientEmail)->first();
                if (!$client) { $skipped++; $errors[] = "Fila $row: cliente no encontrado ($clientEmail)"; continue; }
                if (Quote::where('quote_number', $quoteNumber)->exists()) { $skipped++; $errors[] = "Fila $row: quote_number duplicado ($quoteNumber)"; continue; }

                Quote::create([
                    'company_id' => $user->company_id,
                    'client_id' => $client->id,
                    'quote_number' => $quoteNumber,
                    'amount' => (float)$amount,
                    'status' => $status,
                    'valid_until' => $valid_until_parsed?->format('Y-m-d'),
                    'notes' => $notes ?: null,
                ]);
                $created++;
            }
            fclose($handle);
        } else {
            return response()->json(['success' => false, 'message' => 'No se pudo leer el archivo CSV'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Importación de cotizaciones finalizada',
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 50)
            ]
        ]);
    }
}
