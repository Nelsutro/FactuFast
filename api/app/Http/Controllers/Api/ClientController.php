<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    /**
     * Display a listing of clients
     */
    public function index(Request $request): JsonResponse
    {
        // Datos de ejemplo - reemplazar con modelo real
        $clients = [
            [
                'id' => 1,
                'name' => 'Juan Pérez',
                'email' => 'juan@example.com',
                'phone' => '+57 300 123 4567',
                'company' => 'Empresa ABC',
                'status' => 'active',
                'created_at' => '2024-01-15'
            ],
            [
                'id' => 2,
                'name' => 'María García',
                'email' => 'maria@example.com',
                'phone' => '+57 301 987 6543',
                'company' => 'Corporación XYZ',
                'status' => 'active',
                'created_at' => '2024-02-20'
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $clients,
            'total' => count($clients)
        ]);
    }

    /**
     * Store a newly created client
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simular creación - reemplazar con modelo real
        $client = [
            'id' => rand(3, 1000),
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'company' => $request->company,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'status' => 'active',
            'created_at' => now()->toDateString()
        ];

        return response()->json([
            'success' => true,
            'message' => 'Cliente creado exitosamente',
            'data' => $client
        ], 201);
    }

    /**
     * Display the specified client
     */
    public function show(string $id): JsonResponse
    {
        // Simular búsqueda - reemplazar con modelo real
        if ($id == '1') {
            $client = [
                'id' => 1,
                'name' => 'Juan Pérez',
                'email' => 'juan@example.com',
                'phone' => '+57 300 123 4567',
                'company' => 'Empresa ABC',
                'address' => 'Calle 123 #45-67',
                'city' => 'Bogotá',
                'country' => 'Colombia',
                'status' => 'active',
                'created_at' => '2024-01-15'
            ];

            return response()->json([
                'success' => true,
                'data' => $client
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Cliente no encontrado'
        ], 404);
    }

    /**
     * Update the specified client
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:clients,email,' . $id,
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simular actualización - reemplazar con modelo real
        $client = [
            'id' => $id,
            'name' => $request->name ?? 'Juan Pérez',
            'email' => $request->email ?? 'juan@example.com',
            'phone' => $request->phone,
            'company' => $request->company,
            'address' => $request->address,
            'city' => $request->city,
            'country' => $request->country,
            'status' => 'active',
            'updated_at' => now()->toDateString()
        ];

        return response()->json([
            'success' => true,
            'message' => 'Cliente actualizado exitosamente',
            'data' => $client
        ]);
    }

    /**
     * Remove the specified client
     */
    public function destroy(string $id): JsonResponse
    {
        // Simular eliminación - reemplazar con modelo real
        return response()->json([
            'success' => true,
            'message' => 'Cliente eliminado exitosamente'
        ]);
    }
}
