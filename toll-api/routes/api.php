<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TarifController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\PlateController;
use App\Http\Controllers\API\GateStatusController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DeviceTokenController;
use App\Http\Controllers\API\NotificationController;

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

// FCM Authentication routes (alternative auth for notification testing)
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// FCM Notification routes (protected)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/device-tokens', [DeviceTokenController::class, 'store']);
    Route::post('/send-test', [NotificationController::class, 'sendTest']);
    
    // Toll-specific notification endpoints
    Route::post('/notify-transaction', [NotificationController::class, 'notifyTransaction']);
    Route::post('/notify-payment', [NotificationController::class, 'notifyPayment']);
    Route::post('/notify-gate-status', [NotificationController::class, 'notifyGateStatus']);
});

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
// Gate Status routes
Route::get('/gate-status', [GateStatusController::class, 'getStatus']);
Route::post('/gate-open', [GateStatusController::class, 'openGate']);
Route::post('/gate-close', [GateStatusController::class, 'closeGate']);

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
