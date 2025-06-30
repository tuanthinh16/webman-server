<?php

use app\controller\v1\UserController;
use Webman\Route;

Route::get('', [UserController::class, 'index']);
Route::get('/search', [UserController::class, 'search']);
Route::get('/{identifier}', [UserController::class, 'show']);
Route::post('/register', [UserController::class, 'create']);
Route::post('/password', [UserController::class, 'changePassword']);
Route::post('/confirm', [UserController::class, 'confirmRegister']);
