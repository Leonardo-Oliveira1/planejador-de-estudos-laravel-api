<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Subject;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use stdClass;
date_default_timezone_set('America/Sao_Paulo');

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
        ->select('subjects.id', 'subjects.name', 'subjects.priority', 'subjects.estimated_hours', 'subjects.isFinished', 'modules.id as module', 'subjects.created_at', 'subjects.updated_at')
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

    public function subjectsOrderedByPriority(){
        return DB::table('subjects')
        ->select('subjects.*', 'modules.color', 'modules.name as module_name')
        ->join('modules', 'subjects.module_id', '=', 'modules.id')
        ->where('modules.user_id', '=', auth()->user()['id'])
        ->where('subjects.isFinished', 0)
        ->orderByDesc('priority')
        ->get();
    }

    public function getCalendar(){
        $timeSpend = $this->timeToFinishEachSubject();

        return response()->json(['result' => $timeSpend]);
    }

    public function weekDaysHoursStartingFromToday(){
        $scheduleController = new SchedulesController;
        $weekDaysHours = $scheduleController->getHoursPerDay();

        return array_merge(array_slice($weekDaysHours->toArray(), date('w')), array_slice($weekDaysHours->toArray(), 0, date('w')));
    }

    private function timeToFinishEachSubject(){
        $subjectsOrderedByPriority = $this->subjectsOrderedByPriority();
        $subjectAndDuration = [];
        
        $required_days = 0;
        $currentSubjectIndex = 0;
        $weekDaysHoursStartingFromToday = $this->weekDaysHoursStartingFromToday();
        $currentDayIndex = 0;

        $initial_date_tmp = null;
        while($currentSubjectIndex <= count($subjectsOrderedByPriority) - 1){
            $tmp = ($required_days > 0) ? $required_days : 1;
            while($currentDayIndex < count($weekDaysHoursStartingFromToday)){
                $day = $weekDaysHoursStartingFromToday[$currentDayIndex];

                if($currentSubjectIndex >= count($subjectsOrderedByPriority)) $currentSubjectIndex = count($subjectsOrderedByPriority) - 1;

                $currentSubject = $subjectsOrderedByPriority[$currentSubjectIndex];
    
                
                // DEBUG
                // $test2 = $currentSubject->name;
                // $scheduleController = new SchedulesController;
                // $test3 = $scheduleController->convertNumberToDay($day->day);
                // $hours = $day->hours_studying;
                
                if ((float) $currentSubject->estimated_hours > 0) {
                    $currentSubject->estimated_hours -= $day->hours_studying;
                    $required_days++;      
                    // print_r("$required_days - $test2 ($test3 - $hours): ".(float) $currentSubject->estimated_hours."\n");
                }
    


                if ($currentSubject->estimated_hours <= 0) {
                    $day_in_seconds = 86400;

                    if($initial_date_tmp == null){
                        $initial_date = time() + ($tmp * $day_in_seconds) - $day_in_seconds;
                        $initial_date_tmp = $initial_date; 
                    }

                    $days_to_this = $required_days - ($tmp != 1 ? $tmp : 0);
                    $completion_date = $initial_date_tmp + ($days_to_this * $day_in_seconds) - $day_in_seconds;

                    if($required_days == 0) $initial_date_tmp = $completion_date;

                    $object = new stdClass();
                    $object->subject = $currentSubject->name;
                    $object->module = $currentSubject->module_name;
                    $object->color = $currentSubject->color;
                    $object->days_to_this = $days_to_this;
                    $object->initial_date = date('Y-m-d', $initial_date_tmp);
                    $object->completion_date = date('Y-m-d', $completion_date);
    
                    $initial_date_tmp = $completion_date + $day_in_seconds;

                    array_push($subjectAndDuration, $object);
                    
                    $currentSubjectIndex++;
                    $currentDayIndex++;
                    if ($currentDayIndex >= count($weekDaysHoursStartingFromToday)) {
                        $currentDayIndex = 0;
                    }

                    break;
                }

                $currentDayIndex++;
                if ($currentDayIndex >= count($weekDaysHoursStartingFromToday)) {
                    $currentDayIndex = 0;
                }
                
            }
        }

        return $subjectAndDuration;
    }
}
