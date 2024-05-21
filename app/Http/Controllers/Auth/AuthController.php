<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;


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

        $user = User::where('email', $credentials['email'])->first();

        if (is_null($user->email_verified_at)) {
            throw new HttpResponseException(response()->json(['result' => 'Verifique sua caixa de entrada de email para poder entrar em sua conta'], Response::HTTP_BAD_REQUEST));
        }

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout(){
        try {
            $user = auth('api')->user();
            if(!$user) return response()->json(['result' => 'Você não possui uma sessão ativa.']);
            
            auth('api')->logout();
            return response()->json(['result' => 'Sessão encerrada!']);
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
