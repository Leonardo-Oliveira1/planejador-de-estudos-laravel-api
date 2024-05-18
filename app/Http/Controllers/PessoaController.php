<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PessoaController extends Controller
{
    public function index(){
        return response()->json([
            'name' => 'test',
            'email' => 'test@gmail.com'
        ]);
    }
}
