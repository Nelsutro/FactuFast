<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Obtener la configuración de la empresa del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada para el usuario'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $company
        ]);
    }

    /**
     * Actualizar configuración de la empresa del usuario autenticado.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada para el usuario'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'tax_id' => 'sometimes|string|max:50',
            'email' => 'sometimes|nullable|email',
            'phone' => 'sometimes|nullable|string|max:20',
            'address' => 'sometimes|nullable|string|max:500',
            'currency_code' => 'sometimes|string|size:3',
            'tax_rate' => 'sometimes|numeric|min:0|max:100',
            'default_payment_terms' => 'sometimes|nullable|string|max:255',
            'send_email_on_invoice' => 'sometimes|boolean',
            'send_email_on_payment' => 'sometimes|boolean',
            'portal_enabled' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        $company->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Configuración actualizada',
            'data' => $company->fresh(),
        ]);
    }

    /**
     * Subir el logo de la empresa y actualizar logo_path
     */
    public function uploadLogo(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada para el usuario'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'logo' => 'required|image|max:2048', // 2MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        $file = $request->file('logo');
        $path = $file->store('public/logos');
        $publicPath = Storage::url($path);

        // Eliminar logo anterior si existía
        if ($company->logo_path) {
            $old = str_replace('/storage/', 'public/', $company->logo_path);
            if (Storage::exists($old)) {
                Storage::delete($old);
            }
        }

        $company->update(['logo_path' => $publicPath]);

        return response()->json([
            'success' => true,
            'message' => 'Logo actualizado',
            'data' => $company->fresh(),
        ]);
    }
}
