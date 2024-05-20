<?php

namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\EmailValidate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(Request $request){
        $params = $this->validateRegister($request);

        $user = new User;
        $user->name = $params['name'];
        $user->email = $params['email'];
        $user->password = $params['password'];
        $user->save();

        Mail::to($params['email'])->send(new EmailValidate($this->improvesEncryptionToValidateEmail($params['password']), $user->id));
        return response()->json(['message' => "Verifique sua caixa de entrada de email para finalizar a criação da sua conta."]);
    }

    public function login(Request $request)
    {
        $params = $this->validateLogin($request);

        if (!$params['email_verified']) {
            Mail::to($params['email'])->send(new EmailValidate($this->improvesEncryptionToValidateEmail($params['db_password']), $params['user_id']));
            throw new HttpResponseException(response()->json(['message' => 'Verifique sua caixa de entrada de email para poder entrar em sua conta'], Response::HTTP_BAD_REQUEST));
        }

        if (Hash::check($params['input_password'], $params['db_password'])) {
            $user = User::where('email', $params['email'])->first();

            if (!$token = auth()->attempt(['email' => $params['email'], 'password' => $params['input_password']])) {
                return response()->json(['message' => 'Credenciais inválidas'], Response::HTTP_BAD_REQUEST);
            }

            return $this->respondWithToken($token);
        } else {
            throw new HttpResponseException(response()->json(['message' => 'Senha incorreta'], Response::HTTP_BAD_REQUEST));
        }
    }


    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' =>  60
        ]);
    }

    public function emailConfirmation($code, $user_id){
        $user = User::find($user_id);
        if(!$user) throw new HttpResponseException(response()->json(['message' => 'Ocorreu um erro. Tente novamente!'], Response::HTTP_BAD_REQUEST));
        if($user->email_verified_at) throw new HttpResponseException(response()->json(['message' => 'Você já confirmou o seu email!'], Response::HTTP_BAD_REQUEST));

        $password = $this->improvesEncryptionToValidateEmail($user->password);

        if ($password === $code){
            $user->email_verified_at = Carbon::now()->timestamp;
            $user->save();

            return response()->json(['message' => "Email confirmado!"]);
        } else {
            if(!$user) throw new HttpResponseException(response()->json(['message' => 'Ocorreu um erro. Tente novamente!'], Response::HTTP_BAD_REQUEST));
        }
    }

    private function validateLogin(Request $request){
        $email = $request->input('email');
        $password = $request->input('password');

        if(is_null($email)) throw new HttpResponseException(response()->json(['message' => 'Por favor, preencha o email'], Response::HTTP_BAD_REQUEST));
        if(!preg_match("/^[a-zA-Z0-9-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i", $email)) throw new HttpResponseException(response()->json(['message' => 'Email inválido'], Response::HTTP_BAD_REQUEST));
        if(is_null($password)) throw new HttpResponseException(response()->json(['message' => 'Por favor, preencha a senha'], Response::HTTP_BAD_REQUEST));

        $user = User::select('password', 'email_verified_at', 'id')->where('email', $email)->first();
        if(!$user) throw new HttpResponseException(response()->json(['message' => 'Esse e-mail ainda não foi cadastrado'], Response::HTTP_BAD_REQUEST));

        return ['email' => $email,
                'input_password' => $password,
                'db_password' => $user->password,
                'email_verified' => $user->email_verified_at ? true : false,
                'user_id' => $user->id];
    }

    private function validateRegister(Request $request){
        $name = $request->input('name');
        $email = $request->input('email');
        $password = $request->input('password');

        if(is_null($name)) throw new HttpResponseException(response()->json(['message' => 'O nome é obrigatório'], Response::HTTP_BAD_REQUEST));
        if(is_null($email)) throw new HttpResponseException(response()->json(['message' => 'O email é obrigatório'], Response::HTTP_BAD_REQUEST));
        if(!preg_match("/^[a-zA-Z0-9-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/i", $email)) throw new HttpResponseException(response()->json(['message' => 'Email inválido'], Response::HTTP_BAD_REQUEST));
        if(is_null($password)) throw new HttpResponseException(response()->json(['message' => 'A senha é obrigatória'], Response::HTTP_BAD_REQUEST));

        $existsUser = User::select('email')->where('email', $email)->first();
        if($existsUser) throw new HttpResponseException(response()->json(['message' => 'Esse e-mail já está cadastrado'], Response::HTTP_BAD_REQUEST));

        if(strlen($name) > 255) $name = substr($name, 0 , 255);
        if(strlen($email) > 255) $email = substr($email, 0 , 255);

        return ['name' => $name,
                'email' => $email,
                'password' => bcrypt($password)];
    }

    private function improvesEncryptionToValidateEmail($code){
        $charactersToRemove = ['.', '/', '$', '&'];

        return str_replace($charactersToRemove, '', $code);
    }

}
