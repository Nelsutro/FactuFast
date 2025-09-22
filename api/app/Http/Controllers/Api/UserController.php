<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Obtener el usuario autenticado (alias de /auth/user)
     */
    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('company')
        ]);
    }

    /**
     * Actualizar perfil: name, email, password opcional
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'sometimes|required_with:password|string|min:8',
            'password' => 'sometimes|nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Cambio de contraseña si viene
        if (isset($data['password'])) {
            // Validar current_password
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 403);
            }
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['name'])) $user->name = $data['name'];
        if (isset($data['email'])) $user->email = $data['email'];
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado',
            'data' => $user->fresh()->load('company')
        ]);
    }
}
