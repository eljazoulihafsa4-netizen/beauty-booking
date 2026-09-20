<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StaffServiceController;
use App\Http\Controllers\Api\BusinessWorkingHourController;
use App\Http\Controllers\Api\StaffWorkingHourController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AvailabilityController;


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
    Route::put('/businesses/{business}/staff/{staff}/services', [StaffServiceController::class, 'update']);
    Route::get('/businesses/{business}/working-hours', [BusinessWorkingHourController::class, 'index']);
    Route::put('/businesses/{business}/working-hours', [BusinessWorkingHourController::class, 'update']);
    Route::get(
        '/businesses/{business}/staff/{staff}/working-hours',
        [StaffWorkingHourController::class, 'index']
    );

    Route::put(
        '/businesses/{business}/staff/{staff}/working-hours',
        [StaffWorkingHourController::class, 'update']
    );
    
    Route::post('/businesses/{business}/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::patch(
        '/appointments/{appointment}/cancel',
        [AppointmentController::class, 'cancel']);
    Route::get('/businesses/{business}/availability', [AvailabilityController::class, 'index']);

});
