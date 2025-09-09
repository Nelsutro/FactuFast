<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    /**
     * Display a listing of companies (SOLO ADMIN)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Solo admin puede ver todas las empresas
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores pueden ver la lista de empresas'
            ], 403);
        }

        $companies = Company::with(['users' => function($query) {
            $query->where('role', 'client');
        }])->get();

        return response()->json([
            'success' => true,
            'data' => $companies,
            'total' => $companies->count()
        ]);
    }

    /**
     * Store a newly created company (SOLO ADMIN)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Solo admin puede crear empresas
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores pueden crear empresas'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tax_id' => 'required|string|unique:companies,tax_id',
            'email' => 'nullable|email',
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

        $company = Company::create([
            'name' => $request->name,
            'tax_id' => $request->tax_id,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Empresa creada exitosamente',
            'data' => $company
        ], 201);
    }

    /**
     * Display the specified company (SOLO ADMIN)
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        // Solo admin puede ver detalles de empresas
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores pueden ver detalles de empresas'
            ], 403);
        }

        $company = Company::with(['users', 'clients', 'invoices'])->find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }

    /**
     * Update the specified company (SOLO ADMIN)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        // Solo admin puede editar empresas
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores pueden editar empresas'
            ], 403);
        }

        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'tax_id' => 'required|string|unique:companies,tax_id,' . $id,
            'email' => 'nullable|email',
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

        $company->update([
            'name' => $request->name,
            'tax_id' => $request->tax_id,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Empresa actualizada exitosamente',
            'data' => $company
        ]);
    }

    /**
     * Remove the specified company (SOLO ADMIN)
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        // Solo admin puede eliminar empresas
        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo los administradores pueden eliminar empresas'
            ], 403);
        }

        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada'
            ], 404);
        }

        // Verificar si tiene usuarios asociados
        $hasUsers = User::where('company_id', $id)->exists();
        if ($hasUsers) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la empresa porque tiene usuarios asociados'
            ], 400);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Empresa eliminada exitosamente'
        ]);
    }
}
