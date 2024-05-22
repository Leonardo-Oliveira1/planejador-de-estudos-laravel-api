<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Subject;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SubjectsController extends Controller
{
    public function create(Request $request){
        $params = $this->validateSubject($request);

        $subject = new Subject;
        $subject->module_id = $params['module_id'];
        $subject->name = $params['name'];
        $subject->priority = $params['priority'];
        $subject->estimated_hours = $params['estimated_hours'];
        $subject->save();

        return response()->json(['result' => "Assunto '".$params['name']."' adicionado!"]);
    }

    public function get(Request $request){
        $id = $request->input('id');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id do assunto é obrigatório.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerSubject($id);

        $subject = DB::table('subjects')
        ->select('subjects.name', 'modules.name as module_name', 'subjects.created_at', 'subjects.updated_at')
        ->join('modules', 'subjects.module_id', '=', 'modules.id')
        ->where('subjects.id', '=', $id)->get();

        return response()->json(['result' => $subject]);
    }

    public function list(){
        $subject = DB::table('subjects')
        ->select('subjects.id', 'subjects.name', 'modules.name as module', 'subjects.created_at', 'subjects.updated_at')
        ->join('modules', 'subjects.module_id', '=', 'modules.id')
        ->where('modules.user_id', '=', auth()->user()['id'])
        ->get();

        return response()->json(['result' => $subject]);
    }
    public function update(Request $request){
        $id = $request->input('id');
        $name = $request->input('name');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));
        if(is_null($name)) throw new HttpResponseException(response()->json(['result' => 'O novo nome da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerSubject($id);

        $subject = Subject::find($id);
        $subject->name = $name;
        $subject->save();

        return response()->json(['result' => 'Assunto alterado com sucesso!']);
    }

    public function delete(Request $request){
        $id = $request->input('id');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerSubject($id);

        $subject = Subject::find($id);
        $subject->delete();

        return response()->json(['result' => 'Assunto apagado com sucesso!']);
    }

    public function toggleFinish(Request $request){
        $id = $request->input('id');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id da matéria é obrigatório.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerSubject($id);

        $subject = Subject::find($id);

        $subject->isFinished == 0 ? $subject->isFinished = 1 : $subject->isFinished = 0; 
        $subject->save();

        return response()->json(['result' => 'Sucesso']);
    }

    private function validateSubject(Request $request){
        $name = $request->input('name');
        $priority = $request->input('priority');
        $estimated_hours = $request->input('estimated_hours');
        $module_id = $request->input('module_id');

        if(is_null($name)) throw new HttpResponseException(response()->json(['result' => 'O nome do assunto é obrigatório.'], Response::HTTP_BAD_REQUEST));
        if(is_null($priority)) throw new HttpResponseException(response()->json(['result' => 'A prioridade é obrigatória.'], Response::HTTP_BAD_REQUEST));
        if(is_null($estimated_hours)) throw new HttpResponseException(response()->json(['result' => 'O tempo estimado é obrigatório.'], Response::HTTP_BAD_REQUEST));
        if(is_null($module_id)) throw new HttpResponseException(response()->json(['result' => 'É necessário indicar a matéria.'], Response::HTTP_BAD_REQUEST));
        
        if(!is_int($priority)) throw new HttpResponseException(response()->json(['result' => 'A prioridade deve ser um inteiro.'], Response::HTTP_BAD_REQUEST));
        if(!is_int($module_id)) throw new HttpResponseException(response()->json(['result' => 'Você deve passar o id da matéria.'], Response::HTTP_BAD_REQUEST));
        if(!is_numeric($estimated_hours)) throw new HttpResponseException(response()->json(['result' => 'O tempo estimado é obrigatório.'], Response::HTTP_BAD_REQUEST));
        
        if(strlen($name) > 255) $name = substr($name, 0 , 255);
        
        $existsSubject = Subject::where('module_id', $module_id)->where('name', $name)->first();
        if($existsSubject) throw new HttpResponseException(response()->json(['result' => 'Esse assunto já está cadastrado'], Response::HTTP_BAD_REQUEST));
        
        $existsModule = Module::select('id')->where('id', $module_id)->where('user_id', auth()->user()['id'])->count();
        if($existsModule == 0) throw new HttpResponseException(response()->json(['result' => 'Essa matéria não existe.'], Response::HTTP_BAD_REQUEST));

        return [
                "module_id" => $module_id,
                "name" => $name,
                "priority" => $priority,
                "estimated_hours" => $estimated_hours,
                ];
    }

    private function validateOwnerSubject($id){
        $subjectValidation = DB::table('subjects')
        ->select('subjects.*', 'modules.name')
        ->join('modules', 'subjects.module_id', '=', 'modules.id')
        ->where('modules.user_id', '=', auth()->user()['id'])
        ->where('subjects.id', '=', $id)
        ->count();
        if($subjectValidation == 0) throw new HttpResponseException(response()->json(['result' => 'Não é possível realizar operações com um assunto que não está relacionado a você.'], Response::HTTP_BAD_REQUEST));
    }
}
