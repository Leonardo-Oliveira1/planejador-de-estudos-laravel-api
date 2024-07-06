<?php

namespace App\Http\Controllers;

use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class ModulesController extends Controller
{
    public function create(Request $request){
        $params = $this->validateModule($request);

        $existsModule = Module::select('name')->where('name', $params['name'])->where('user_id', auth()->user()['id'])->first();
        if($existsModule) throw new HttpResponseException(response()->json(['result' => 'Essa matéria já está cadastrada'], Response::HTTP_BAD_REQUEST));

        $module = new Module;
        $module->user_id = auth()->user()['id'];
        $module->name = $params['name'];
        $module->color = $params['color'];
        $module->save();

        return response()->json(['result' => "Matéria '".$params['name']."' adicionada!"]);
    }

    public function get(Request $request){
        $id = $request->input('id');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerModule($id);

        $module = Module::select('name', 'color', 'created_at', 'updated_at')->where('id', $request->input('id'))->first();
        return response()->json(['result' => $module]);
    }

    public function list(){
        $module = Module::select('id', 'name', 'color', 'created_at', 'updated_at')->where('user_id', auth()->user()['id'])->get();
        return response()->json(['result' => $module]);
    }
    public function update(Request $request){
        $id = $request->input('id');
        $name = $request->input('name');
        $color = $request->input('color');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));
        if(is_null($name)) throw new HttpResponseException(response()->json(['result' => 'O novo nome da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));
        if(is_null($color)) throw new HttpResponseException(response()->json(['result' => 'Defina uma cor para esta matéria.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerModule($id);

        $module = Module::find($id);
        $module->name = $name;
        $module->color = $color;
        $module->save();

        return response()->json(['result' => 'Matéria alterada com sucesso!']);
    }

    public function delete(Request $request){
        $id = $request->input('id');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerModule($id);

        $module = Module::find($id);
        if(!$module->subjects->isEmpty()) throw new HttpResponseException(response()->json(['result' => 'Apague todos os assuntos antes de excluir a matéria.'], Response::HTTP_BAD_REQUEST));
        
        $module->delete();

        return response()->json(['result' => 'Matéria apagada com sucesso!']);
    }

    private function validateModule(Request $request){
        $name = $request->input('name');
        $color = $request->input('color');

        if(is_null($name)) throw new HttpResponseException(response()->json(['result' => 'O nome da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));
        if(is_null($color)) throw new HttpResponseException(response()->json(['result' => 'Defina uma cor para esta matéria.'], Response::HTTP_BAD_REQUEST));
        if(strlen($name) > 255) $name = substr($name, 0 , 255);

        return ["name" => $name, "color" => $color];
    }

    private function validateOwnerModule($id){
        $moduleValidation = Module::select('id')->where('user_id', auth()->user()['id'])->where('id', $id)->get();
        if($moduleValidation->isEmpty()) throw new HttpResponseException(response()->json(['result' => 'Não é possível realizar operações com uma matéria que não está relacionada a você.'], Response::HTTP_BAD_REQUEST));
    }
}
