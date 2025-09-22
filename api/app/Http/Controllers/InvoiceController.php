<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Client;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoicePdfMail;
use App\Mail\InvoiceCreatedClientMail;
use App\Mail\InvoiceCreatedCompanyMail;
use Illuminate\Support\Facades\Log;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Invoice::with(['client', 'items']);

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
                $query->whereDate('issue_date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->whereDate('issue_date', '<=', $request->date_to);
            }

            // Búsqueda por número de factura o nombre de cliente
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
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
            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $invoices->items(),
                'pagination' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las facturas',
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
                'invoice_number' => 'required|string|unique:invoices,invoice_number',
                'amount' => 'required|numeric|min:0',
                'status' => ['required', Rule::in(['draft', 'pending', 'paid', 'overdue', 'cancelled'])],
                'issue_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string',
                'items.*.quantity' => 'required|numeric|min:0.01',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.amount' => 'required|numeric|min:0'
            ]);

            DB::beginTransaction();

            // Crear la factura
            $invoice = Invoice::create([
                'company_id' => $user->company_id,
                'client_id' => $validated['client_id'],
                'invoice_number' => $validated['invoice_number'],
                'amount' => $validated['amount'],
                'status' => $validated['status'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null
            ]);

            // Crear los items de la factura
            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $item['amount']
                ]);
            }

            DB::commit();

            // Cargar la factura con sus relaciones
            $invoice->load(['client', 'items']);

            // Notificaciones por correo (suaves: respetan configuración de la empresa y sólo si hay emails disponibles)
            try {
                $invoice->loadMissing('company');
                $sendEnabled = $invoice->company?->send_email_on_invoice ?? true;
                if ($sendEnabled) {
                    if (!empty($invoice->client?->email)) {
                        Mail::to($invoice->client->email)->send(new InvoiceCreatedClientMail($invoice));
                    }
                    if (!empty($invoice->company?->email)) {
                        Mail::to($invoice->company->email)->send(new InvoiceCreatedCompanyMail($invoice));
                    }
                }
            } catch (\Throwable $mailEx) {
                // No interrumpir el flujo por errores de correo
                Log::warning('No se pudo enviar notificación de factura creada: '.$mailEx->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Factura creada exitosamente'
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
                'message' => 'Error al crear la factura',
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
            $query = Invoice::with(['client', 'items', 'payments']);

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $invoice = $query->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la factura',
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
            $query = Invoice::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $invoice = $query->findOrFail($id);

            $validated = $request->validate([
                'client_id' => 'sometimes|exists:clients,id',
                'amount' => 'sometimes|numeric|min:0',
                'status' => ['sometimes', Rule::in(['draft', 'pending', 'paid', 'overdue', 'cancelled'])],
                'issue_date' => 'sometimes|date',
                'due_date' => 'sometimes|date|after_or_equal:issue_date',
                'notes' => 'nullable|string',
                'items' => 'sometimes|array|min:1',
                'items.*.description' => 'required_with:items|string',
                'items.*.quantity' => 'required_with:items|numeric|min:0.01',
                'items.*.unit_price' => 'required_with:items|numeric|min:0',
                'items.*.amount' => 'required_with:items|numeric|min:0'
            ]);

            DB::beginTransaction();

            // Actualizar la factura
            $invoice->update($validated);

            // Si se proporcionan items, reemplazar los existentes
            if (isset($validated['items'])) {
                // Eliminar items existentes
                $invoice->items()->delete();

                // Crear nuevos items
                foreach ($validated['items'] as $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'amount' => $item['amount']
                    ]);
                }
            }

            DB::commit();

            // Cargar la factura actualizada con sus relaciones
            $invoice->load(['client', 'items']);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Factura actualizada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
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
                'message' => 'Error al actualizar la factura',
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
            $query = Invoice::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $invoice = $query->findOrFail($id);

            // Verificar si la factura puede ser eliminada
            if ($invoice->status === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar una factura pagada'
                ], 422);
            }

            DB::beginTransaction();

            // Eliminar items de la factura (cascade delete)
            $invoice->items()->delete();
            
            // Eliminar la factura
            $invoice->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Factura eliminada exitosamente'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get invoice statistics for the current user's company
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $query = Invoice::query();

            // Filtrar por empresa del usuario (multi-tenant)
            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $stats = [
                'total' => $query->count(),
                'pending' => $query->where('status', 'pending')->count(),
                'paid' => $query->where('status', 'paid')->count(),
                'overdue' => $query->where('status', 'overdue')->count(),
                'total_amount' => $query->sum('amount'),
                'pending_amount' => $query->whereIn('status', ['pending', 'overdue'])->sum('amount'),
                'paid_amount' => $query->where('status', 'paid')->sum('amount')
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
     * Export invoices to CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        }

        if (!in_array($user->role, ['admin', 'client'])) {
            return response()->json(['success' => false, 'message' => 'No tienes permisos para exportar facturas'], 403);
        }

        $query = Invoice::with(['client:id,email'])
            ->select(['id','company_id','client_id','invoice_number','amount','status','issue_date','due_date','notes'])
            ->orderBy('company_id')->orderBy('issue_date','desc');

        if ($user->role !== 'admin') {
            $query->where('company_id', $user->company_id);
        }

        $invoices = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="invoices_export_'.now()->format('Ymd_His').'.csv"',
        ];

        $columns = ['invoice_number','client_email','amount','status','issue_date','due_date','notes'];

        $callback = function() use ($invoices, $columns) {
            $output = fopen('php://output', 'w');
            // BOM para Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, $columns);
            foreach ($invoices as $inv) {
                fputcsv($output, [
                    $inv->invoice_number,
                    optional($inv->client)->email,
                    (string)$inv->amount,
                    $inv->status,
                    $inv->issue_date?->format('Y-m-d'),
                    $inv->due_date?->format('Y-m-d'),
                    $inv->notes,
                ]);
            }
            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Import invoices from CSV
     */
    public function import(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Solo empresas (clientes) pueden importar para su propia compañía
        if (!$user || $user->role !== 'client' || !$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Solo las empresas pueden importar facturas'
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

        $created = 0;
        $skipped = 0;
        $errors = [];

        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $row = 0;
            $header = null;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $row++;

                // Detectar encabezado
                if ($row === 1) {
                    $maybeHeader = array_map(fn($h) => strtolower(trim($h)), $data);
                    if (in_array('invoice_number', $maybeHeader)) {
                        $header = $maybeHeader;
                        continue;
                    }
                }

                // Mapear columnas esperadas
                if ($header) {
                    $map = array_combine($header, $data + array_fill(0, max(0, count($header) - count($data)), ''));
                    $invoiceNumber = trim($map['invoice_number'] ?? '');
                    $clientEmail = trim($map['client_email'] ?? '');
                    $amount = trim($map['amount'] ?? '');
                    $status = strtolower(trim($map['status'] ?? 'pending'));
                    $issueDate = trim($map['issue_date'] ?? '');
                    $dueDate = trim($map['due_date'] ?? '');
                    $notes = trim($map['notes'] ?? '');
                } else {
                    // Posicional: invoice_number,client_email,amount,status,issue_date,due_date,notes
                    $invoiceNumber = trim($data[0] ?? '');
                    $clientEmail = trim($data[1] ?? '');
                    $amount = trim($data[2] ?? '');
                    $status = strtolower(trim($data[3] ?? 'pending'));
                    $issueDate = trim($data[4] ?? '');
                    $dueDate = trim($data[5] ?? '');
                    $notes = trim($data[6] ?? '');
                }

                // Validaciones básicas
                if ($invoiceNumber === '') { $skipped++; $errors[] = "Fila $row: invoice_number vacío"; continue; }
                if ($clientEmail === '') { $skipped++; $errors[] = "Fila $row: client_email vacío"; continue; }
                if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) { $skipped++; $errors[] = "Fila $row: email inválido ($clientEmail)"; continue; }
                if ($amount === '' || !is_numeric($amount)) { $skipped++; $errors[] = "Fila $row: amount inválido"; continue; }
                if (!in_array($status, ['draft','pending','paid','overdue','cancelled'])) { $skipped++; $errors[] = "Fila $row: status inválido ($status)"; continue; }
                // Fechas
                $issue_date_parsed = date_create($issueDate) ?: null;
                $due_date_parsed = date_create($dueDate) ?: null;
                if (!$issue_date_parsed || !$due_date_parsed) { $skipped++; $errors[] = "Fila $row: fechas inválidas"; continue; }
                if ($due_date_parsed < $issue_date_parsed) { $skipped++; $errors[] = "Fila $row: due_date anterior a issue_date"; continue; }

                // Cliente por email y compañía
                $client = Client::where('company_id', $user->company_id)->where('email', $clientEmail)->first();
                if (!$client) { $skipped++; $errors[] = "Fila $row: cliente no encontrado ($clientEmail)"; continue; }

                // Unicidad de invoice_number
                if (Invoice::where('invoice_number', $invoiceNumber)->exists()) { $skipped++; $errors[] = "Fila $row: invoice_number duplicado ($invoiceNumber)"; continue; }

                // Crear factura
                Invoice::create([
                    'company_id' => $user->company_id,
                    'client_id' => $client->id,
                    'invoice_number' => $invoiceNumber,
                    'amount' => (float)$amount,
                    'status' => $status,
                    'issue_date' => $issue_date_parsed->format('Y-m-d'),
                    'due_date' => $due_date_parsed->format('Y-m-d'),
                    'notes' => $notes ?: null,
                ]);
                $created++;
            }
            fclose($handle);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo leer el archivo CSV'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Importación de facturas finalizada',
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 50)
            ]
        ]);
    }

    /**
     * Download invoice as PDF
     */
    public function downloadPdf(string $id)
    {
        try {
            $user = Auth::user();
            $query = Invoice::with(['company', 'client', 'items']);

            if ($user->role !== 'admin') {
                $query->where('company_id', $user->company_id);
            }

            $invoice = $query->findOrFail($id);

            // Verificar disponibilidad de Dompdf
            if (!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Generación de PDF no disponible. Instala barryvdh/laravel-dompdf y habilita la extensión zip/unzip.'
                ], 501);
            }

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
                'invoice' => $invoice,
            ])->setPaper('A4', 'portrait');

            $filename = 'invoice_' . ($invoice->invoice_number ?? $invoice->id) . '.pdf';
            return $pdf->download($filename);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send invoice by email with optional PDF attachment
     */
    public function sendEmail(Request $request, string $id)
    {
        $user = Auth::user();
        $query = Invoice::with(['company','client','items']);
        if ($user->role !== 'admin') {
            $query->where('company_id', $user->company_id);
        }
        $invoice = $query->findOrFail($id);

        $validated = $request->validate([
            'to' => 'required|email',
            'cc' => 'nullable|array',
            'cc.*' => 'email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'attach_pdf' => 'sometimes|boolean'
        ]);

        $mailable = new InvoicePdfMail($invoice, $validated['subject'], $validated['message']);

        // Adjuntar PDF si se solicita y Dompdf está disponible
        if (!empty($validated['attach_pdf']) && class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [ 'invoice' => $invoice ])->setPaper('A4','portrait');
            $mailable->attachData($pdf->output(), 'invoice_' . ($invoice->invoice_number ?? $invoice->id) . '.pdf', [
                'mime' => 'application/pdf'
            ]);
        }

        $email = Mail::to($validated['to']);
        if (!empty($validated['cc'])) {
            $email->cc($validated['cc']);
        }
        $email->send($mailable);

        return response()->json([
            'success' => true,
            'message' => 'Correo enviado correctamente'
        ]);
    }
}
