<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    /**
     * Display a listing of clients
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Admin puede ver todos los clientes
        if ($user->isAdmin()) {
            $clients = Client::with('company')->get();
        } 
        // Empresas solo ven SUS clientes
        elseif ($user->isClient() && $user->company_id) {
            $clients = Client::where('company_id', $user->company_id)->get();
        } 
        else {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver clientes'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $clients,
            'total' => $clients->count()
        ]);
    }

    /**
     * Store a newly created client
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Solo empresas pueden crear clientes (no admin)
        if (!$user->isClient() || !$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Solo las empresas pueden crear clientes'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $client = Client::create([
            'company_id' => $user->company_id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'data' => $client
        ], 201);
    }

    /**
     * Display the specified client
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        // Admin puede ver cualquier cliente
        if ($user->isAdmin()) {
            $client->load('company');
        }
        // Empresas solo pueden ver SUS clientes
        elseif ($user->isClient() && $client->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver este cliente'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $client
        ]);
    }

    /**
     * Update the specified client
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        // Solo las empresas pueden editar SUS clientes
        if (!$user->isClient() || $client->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para editar este cliente'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $client->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'data' => $client
        ]);
    }

    /**
     * Remove the specified client
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $client = Client::find($id);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado'
            ], 404);
        }

        // Solo las empresas pueden eliminar SUS clientes
        if (!$user->isClient() || $client->company_id !== $user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para eliminar este cliente'
            ], 403);
        }

        $client->delete();

        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }

    /**
     * Export clients to CSV
     */
    public function export(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin() && !$user->isClient()) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para exportar clientes'
            ], 403);
        }

        // Obtener dataset según rol
        if ($user->isAdmin()) {
            $clients = Client::orderBy('company_id')->orderBy('name')->get(['company_id', 'name', 'email', 'phone', 'address']);
        } else {
            $clients = Client::where('company_id', $user->company_id)
                ->orderBy('name')
                ->get(['company_id', 'name', 'email', 'phone', 'address']);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="clients_export_'.now()->format('Ymd_His').'.csv"',
        ];

        $columns = ['name', 'email', 'phone', 'address'];

        $callback = function() use ($clients, $columns) {
            $output = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            // Encabezados
            fputcsv($output, $columns);
            // Filas
            foreach ($clients as $c) {
                fputcsv($output, [
                    $c->name,
                    $c->email,
                    $c->phone,
                    $c->address,
                ]);
            }
            fclose($output);
        };

        return new StreamedResponse($callback, 200, $headers);
    }

    /**
     * Import clients from CSV
     */
    public function import(Request $request): JsonResponse
    {
        $user = $request->user();

        // Solo empresas pueden importar (siguiendo la misma regla que store)
        if (!$user->isClient() || !$user->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Solo las empresas pueden importar clientes'
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

        // Abrir y parsear CSV
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            $row = 0;
            $header = null;
            while (($data = fgetcsv($handle, 0, ',')) !== false) {
                $row++;
                // Detectar encabezado
                if ($row === 1) {
                    $maybeHeader = array_map(fn($h) => strtolower(trim($h)), $data);
                    if (in_array('name', $maybeHeader)) {
                        $header = $maybeHeader; // usar nombres de columna
                        continue; // saltar encabezado
                    }
                }

                // Mapear columnas
                if ($header) {
                    $map = array_combine($header, $data + array_fill(0, max(0, count($header) - count($data)), ''));
                    $name = trim($map['name'] ?? '');
                    $email = trim($map['email'] ?? '');
                    $phone = trim($map['phone'] ?? '');
                    $address = trim($map['address'] ?? '');
                } else {
                    // Posicional: name,email,phone,address
                    $name = trim($data[0] ?? '');
                    $email = trim($data[1] ?? '');
                    $phone = trim($data[2] ?? '');
                    $address = trim($data[3] ?? '');
                }

                // Validaciones mínimas
                if ($name === '') {
                    $skipped++;
                    $errors[] = "Fila $row: nombre vacío";
                    continue;
                }
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    $errors[] = "Fila $row: email inválido ($email)";
                    continue;
                }
                // Unicidad global por email (siguiendo validación del modelo)
                if ($email !== '' && Client::where('email', $email)->exists()) {
                    $skipped++;
                    $errors[] = "Fila $row: email duplicado ($email)";
                    continue;
                }

                // Crear
                Client::create([
                    'company_id' => $user->company_id,
                    'name' => $name,
                    'email' => $email ?: null,
                    'phone' => $phone ?: null,
                    'address' => $address ?: null,
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
            'message' => 'Importación finalizada',
            'data' => [
                'created' => $created,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 50), // limitar respuesta
            ]
        ]);
    }
}
