<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout(){
        try {
            $user = auth('api')->user();
            if(!$user) return response()->json(['message' => 'Você não possui uma sessão ativa.']);
            
            auth('api')->logout();
            return response()->json(['message' => 'Sessão encerrada!']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Falha ao encerrar a sessão', 'exception' => $e->getMessage()], 500);
        }
    }

        /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

}
