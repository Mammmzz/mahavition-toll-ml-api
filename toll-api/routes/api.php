<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TarifController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\PlateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'store']);

// User routes
Route::apiResource('users', UserController::class);
Route::put('/users/{id}/saldo', [UserController::class, 'updateSaldo']);
Route::post('/users/plat-nomor', [UserController::class, 'getByPlatNomor']);

// Tarif routes
Route::apiResource('tarifs', TarifController::class);
Route::post('/tarifs/vehicle-type', [TarifController::class, 'getByVehicleType']);

// Transaction routes
Route::apiResource('transactions', TransactionController::class)->except(['update', 'destroy']);
Route::get('/users/{id}/transactions', [TransactionController::class, 'getByUser']);
Route::post('/transactions/plate', [TransactionController::class, 'processPlateTransaction']);
Route::get('/gate-status', [TransactionController::class, 'getGateStatus']);

// Plate validation routes
Route::get('/validate-plate/{plat_nomor}', [PlateController::class, 'validatePlate']);
Route::post('/validate-plate', [PlateController::class, 'validatePlate']);
Route::post('/validate-plate-vehicle', [PlateController::class, 'validatePlateAndVehicle']);
Route::get('/plates', [PlateController::class, 'getAllPlates']);

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now()
    ]);
});
