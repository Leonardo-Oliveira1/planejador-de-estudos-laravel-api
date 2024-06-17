<?php

namespace Tests\Feature;

use App\Http\Controllers\SubjectsController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SubjectsTest extends TestCase
{

    private function getJWTToken(){
        $user = User::factory()->create([
            'password' => '123'
        ]);
        
        $login = $this->post('/api/login', [
            'email' => $user->email,
            'password' => '123'
        ]);

        return $login['access_token'];
    }

    private function getRouteJson($route){
        return $this->withHeader('Authorization', 'Bearer ' . $this->getJWTToken())
                    ->getJson($route);
    }

    private function setSchedule(){
        $this->postJson('/api/schedule', [
            'day' => 0,
            'start' => '00:00:00',
            'end' => '00:00:00'
        ]);

        $this->postJson('/api/schedule', [
            'day' => 1,
            'start' => '21:00:00',
            'end' => '22:00:00'
        ]);

        $this->postJson('/api/schedule', [
            'day' => 2,
            'start' => '20:30:00',
            'end' => '21:00:00'
        ]);

        $this->postJson('/api/schedule', [
            'day' => 3,
            'start' => '20:30:00',
            'end' => '21:00:00'
        ]);

        $this->postJson('/api/schedule', [
            'day' => 4,
            'start' => '20:00:00',
            'end' => '21:00:00'
        ]);

        $this->postJson('/api/schedule', [
            'day' => 5,
            'start' => '00:00:00',
            'end' => '00:00:00'
        ]);

        $this->postJson('/api/schedule', [
            'day' => 6,
            'start' => '00:00:00',
            'end' => '00:00:00'
        ]);

    }
    
    private function setModules(){
        $request = $this->postJson('/api/module', [
            'name' => 'Estrutura de dados'
        ]);
    }

    private function setSubjects(){

        $this->setModules();

        $modules = $this->getJson('/api/module/list', [
            'user_id' => Auth::id()
        ]);

        $this->postJson('/api/subject', [
            'name' => 'Pilhas',
            'priority' => 1,
            'estimated_hours' => 1,
            'module_id' => $modules['result'][0]['id']
        ]);

        $this->postJson('/api/subject', [
            'name' => 'Filas',
            'priority' => 1,
            'estimated_hours' => 1.5,
            'module_id' => $modules['result'][0]['id']
        ]);

        $this->postJson('/api/subject', [
            'name' => 'Hash',
            'priority' => 3,
            'estimated_hours' => 11.2,
            'module_id' => $modules['result'][0]['id']
        ]);

        $this->postJson('/api/subject', [
            'name' => 'Bubble Sorting',
            'priority' => 4,
            'estimated_hours' => 2.2,
            'module_id' => $modules['result'][0]['id']
        ]);


    }

    public function test_example(): void
    {
        $this->postJson('/logout');
        $this->getRouteJson('/api/subject/orderByPriority');
        
        $this->setSchedule();
        $this->setSubjects();

        $response = $this->getJson('/api/subject/orderByPriority');
        $this->assertEquals(4, $response['result'][0]['days_to_this']);
        $this->assertEquals('2024-06-17', $response['result'][0]['initial_date']);
        $this->assertEquals('2024-06-20', $response['result'][0]['completion_date']);

        $this->assertEquals(28, $response['result'][1]['days_to_this']);
        $this->assertEquals('2024-06-21', $response['result'][1]['initial_date']);
        $this->assertEquals('2024-07-19', $response['result'][1]['completion_date']);

        $this->assertEquals(4, $response['result'][2]['days_to_this']);
        $this->assertEquals('2024-07-20', $response['result'][2]['initial_date']);
        $this->assertEquals('2024-07-23', $response['result'][2]['completion_date']);

        $this->assertEquals(3, $response['result'][3]['days_to_this']);
        $this->assertEquals('2024-07-25', $response['result'][3]['initial_date']);
        $this->assertEquals('2024-07-29', $response['result'][3]['completion_date']);


        $response->assertStatus(200);
    }
}
