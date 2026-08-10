<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Autenticación STUB (§8: fuera de alcance construir auth real).
 *
 * Emite un Sanctum personal access token para uno de los usuarios semilla.
 * No hay registro, verificación de email, recuperación de contraseña ni
 * roles/permisos: es deliberadamente mínimo para que el candidato pueda
 * concentrar el tiempo en el importador.
 *
 * Credenciales semilla (ver database/seeders/DatabaseSeeder.php):
 *   empresa 1 -> ana@empresa-uno.test    / password
 *   empresa 2 -> beto@empresa-dos.test   / password
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ])->validate();

        if (! Auth::attempt($data)) {
            return response()->json([
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        $user = Auth::user()->load('company');

        return response()->json([
            'token' => $user->createToken('starter-kit')->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'company_name' => $user->company->name,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return $request->user()->load('company');
    }
}
