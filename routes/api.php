<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('/register', [UserController::class, 'register']);
Route::get('/emailconfirmation/{code}/{user_id}', [UserController::class, 'emailConfirmation'])->name('emailconfirmation');
