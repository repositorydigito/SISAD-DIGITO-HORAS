<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DebugController extends Controller
{
    /**
     * Verificar credenciales sin generar token
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkCredentials(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Usuario no encontrado',
                'email' => $request->email
            ], 404);
        }

        $passwordValid = Hash::check($request->password, $user->password);

        return response()->json([
            'status' => 'success',
            'user_exists' => true,
            'password_valid' => $passwordValid,
            'user_id' => $user->id,
            'user_name' => $user->name,
            'has_api_tokens_trait' => method_exists($user, 'createToken'),
        ]);
    }
}
