<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
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
}
