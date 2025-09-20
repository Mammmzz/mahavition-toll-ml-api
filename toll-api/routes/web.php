<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\TariffController;
use App\Http\Controllers\Admin\GateController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to admin login
Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Admin Login Routes
Route::get('/admin/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.submit');

// Admin Routes (Protected)
Route::middleware(['web', 'auth'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/update-balance', [UserController::class, 'updateBalance'])->name('users.update-balance');
    
    // Transaction Management
    Route::resource('transactions', TransactionController::class)->except(['create', 'store']);
    Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    
    // Tariff Management
    Route::resource('tariffs', TariffController::class);
    
    // Gate Management
    Route::get('gate', [GateController::class, 'index'])->name('gate.index');
    Route::post('gate/toggle', [GateController::class, 'toggleGate'])->name('gate.toggle');
    
    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('reports/vehicle-type', [ReportController::class, 'vehicleType'])->name('reports.vehicle-type');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
    
    // Settings
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
    
    // User Profile
    Route::get('profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    
    // Logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});