<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\ModulesController;
use App\Http\Controllers\SubjectsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [UserController::class, 'register'])->name('register');
Route::get('/emailconfirmation/{code}/{user_id}', [UserController::class, 'emailConfirmation'])->name('emailconfirmation');

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => 'apiJwt'], function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/module', [ModulesController::class, 'create'])->name('createModule');
    Route::get('/module', [ModulesController::class, 'get'])->name('getModule');
    Route::get('/module/list', [ModulesController::class, 'list'])->name('listModules');
    Route::put('/module', [ModulesController::class, 'update'])->name('updateModule');
    Route::delete('/module', [ModulesController::class, 'delete'])->name('deleteModule');

    Route::post('/subject', [SubjectsController::class, 'create'])->name('createSubject');
    Route::get('/subject', [SubjectsController::class, 'get'])->name('getSubject');
    Route::get('/subject/list', [SubjectsController::class, 'list'])->name('listSubjects');
    Route::put('/subject', [SubjectsController::class, 'update'])->name('updateSubject');
    Route::delete('/subject', [SubjectsController::class, 'delete'])->name('deleteSubject');
});