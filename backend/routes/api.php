<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\ServiceController;
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/businesses', [BusinessController::class, 'store']);
    Route::post('/businesses/{business}/staff', [StaffController::class, 'store']);
    Route::get('/businesses/{business}/staff', [StaffController::class, 'index']);
    Route::put('/businesses/{business}/staff/{staff}', [StaffController::class, 'update']);
    Route::delete('/businesses/{business}/staff/{staff}', [StaffController::class, 'destroy']);
    Route::get('/businesses/{business}/services', [ServiceController::class, 'index']);
    Route::post('/businesses/{business}/services', [ServiceController::class, 'store']);
    Route::put('/businesses/{business}/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/businesses/{business}/services/{service}', [ServiceController::class, 'destroy']);
});
