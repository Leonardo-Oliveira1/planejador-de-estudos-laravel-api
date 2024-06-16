<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class SchedulesController extends Controller
{
    public function create(Request $request){
        $params = $this->validateSchedule($request);

        $schedule = new Schedule();
        $schedule->user_id = auth()->user()['id'];
        $schedule->day = $params['day'];
        $schedule->start = $params['start'];
        $schedule->end = $params['end'];
        $schedule->save();

        return response()->json(['result' => "Horário de ".$this->convertNumberToDay($params['day'])." adicionado!"]);
    }

    public function get(Request $request){
        $id = $request->input('id');
        if(is_null($id)) throw new HttpResponseException(response()->json(['result' => 'O id é obrigatório.'], Response::HTTP_BAD_REQUEST));

        $this->validateOwnerSchedule($id);

        $schedule = Schedule::find($id)->only(['day', 'start', 'end']);

        return response()->json(['result' => $schedule]);
    }

    public function list(){
        $schedule = Schedule::select('day', 'start', 'end')->where("user_id", auth()->user()['id'])->get();

        return response()->json(['result' => $schedule]);
    }
    public function update(Request $request){
        $params = $this->validateSchedule($request, true);
        
        $scheduleValidation = Schedule::select('id')->where('user_id', auth()->user()['id'])->where('day', $params['day'])->get();
        if($scheduleValidation->isEmpty()) throw new HttpResponseException(response()->json(['result' => 'Não é possível realizar operações com um horário que não está relacionada a você.'], Response::HTTP_BAD_REQUEST));

        $schedule = Schedule::find($scheduleValidation[0]->id);
        $schedule->start = $params['start'];
        $schedule->end = $params['end'];
        $schedule->save();

        return response()->json(['result' => 'Horário de '.$this->convertNumberToDay($params['day']).' alterado com sucesso!']);
    }

    private function validateSchedule(Request $request, $isUpdate = null){
        $day = $request->input('day');
        $start = $request->input('start');
        $end = $request->input('end');

        if(is_null($day)) throw new HttpResponseException(response()->json(['result' => 'O dia é obrigatório.'], Response::HTTP_BAD_REQUEST));
        if(is_null($start)) throw new HttpResponseException(response()->json(['result' => 'A hora de início é obrigatória.'], Response::HTTP_BAD_REQUEST));
        if(is_null($end)) throw new HttpResponseException(response()->json(['result' => 'A hora final é obrigatória.'], Response::HTTP_BAD_REQUEST));
        
        if(!is_int($day)) throw new HttpResponseException(response()->json(['result' => 'O dia deve ser um inteiro.'], Response::HTTP_BAD_REQUEST));
        if(!($day >= 0 && $day <= 6)) throw new HttpResponseException(response()->json(['result' => 'O dia deve estar entre 0 e 6.'], Response::HTTP_BAD_REQUEST));
        if($this->validateTime($start) != true)throw new HttpResponseException(response()->json(['result' => 'Formato de data inicial inválida. Use hh:mm:ss.'], Response::HTTP_BAD_REQUEST));
        if($this->validateTime($end) != true)throw new HttpResponseException(response()->json(['result' => 'Formato de data fim inválida. Use hh:mm:ss.'], Response::HTTP_BAD_REQUEST));
        
        if(!$isUpdate){
            $existsSchedule = Schedule::where('day', $day)->where('user_id', auth()->user()['id'])->first();
            if($existsSchedule) throw new HttpResponseException(response()->json(['result' => 'O horário de '.$this->convertNumberToDay($day).' já está cadastrado'], Response::HTTP_BAD_REQUEST));
        }
        
        return [
                "day" => $day,
                "start" => $start,
                "end" => $end,
                ];
    }

    private function validateOwnerSchedule($id){
        $scheduleValidation = Schedule::select('id')->where('user_id', auth()->user()['id'])->where('id', $id)->get();
        if($scheduleValidation->isEmpty()) throw new HttpResponseException(response()->json(['result' => 'Não é possível realizar operações com um horário que não está relacionada a você.'], Response::HTTP_BAD_REQUEST));
    }

    private function validateTime($time)
    {
        // Verifica se o formato é hh:mm:ss e se os valores estão dentro da faixa permitida
        if (preg_match('/^(-?\d{1,3}):([0-5][0-9]):([0-5][0-9])$/', $time, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = (int)$matches[3];
    
            // Verifica se as horas estão na faixa permitida
            if ($hours >= -838 && $hours <= 838) {
                return true;
            }
        }
    
        return false;
    }

    public function convertNumberToDay($number){
        $days = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta', 'sábado'];
        return $days[$number];
    }

    public function getHoursPerDay(){
        $hoursPerDayArray = DB::table('schedules')
        ->select(DB::raw('day, TIME_TO_SEC(TIMEDIFF(end, start)) / 3600 as hours_studying'))
        ->where('user_id', auth()->user()['id'])
        ->orderBy('day')
        ->get();

        return $hoursPerDayArray;
    }
    
}
