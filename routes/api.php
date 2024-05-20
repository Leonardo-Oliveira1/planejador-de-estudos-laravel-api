<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IsUserLogged;

Route::post('/register', [UserController::class, 'register'])->name('register');
Route::get('/emailconfirmation/{code}/{user_id}', [UserController::class, 'emailConfirmation'])->name('emailconfirmation');

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::group(['middleware' => 'apiJwt'], function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('/user', function (Request $request) {
            return $request->user();
        });
});