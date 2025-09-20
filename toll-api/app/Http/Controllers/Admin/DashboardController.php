<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GateCondition;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard page
     */
    public function index()
    {
        // Get total users
        $totalUsers = User::count();
        
        // Get total revenue
        $totalRevenue = Transaction::where('status', 'success')->sum('saldo_pembayaran');
        
        // Get total transactions
        $totalTransactions = Transaction::count();
        
        // Get today's transactions
        $todayTransactions = Transaction::whereDate('created_at', Carbon::today())->count();
        
        // Get gate status
        $gateStatus = GateCondition::first()->gate_status ?? 'off';
        
        // Get daily transactions for the last 7 days
        $dailyTransactions = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $dailyTransactionsLabels = [];
        $dailyTransactionsData = [];
        
        // Fill in any missing days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyTransactionsLabels[] = Carbon::parse($date)->format('d M');
            
            $found = false;
            foreach ($dailyTransactions as $transaction) {
                if ($transaction->date == $date) {
                    $dailyTransactionsData[] = $transaction->count;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $dailyTransactionsData[] = 0;
            }
        }
        
        // Get vehicle type distribution
        $vehicleTypes = Transaction::select('jenis_kendaraan', DB::raw('COUNT(*) as count'))
            ->groupBy('jenis_kendaraan')
            ->get();
        
        $vehicleTypeLabels = [];
        $vehicleTypeData = [];
        
        foreach ($vehicleTypes as $type) {
            $vehicleTypeLabels[] = $type->jenis_kendaraan ?? 'Tidak Diketahui';
            $vehicleTypeData[] = $type->count;
        }
        
        // Get recent transactions
        $recentTransactions = Transaction::with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        return view('admin.dashboard', compact(
            'totalUsers',
            'totalRevenue',
            'totalTransactions',
            'todayTransactions',
            'gateStatus',
            'dailyTransactionsLabels',
            'dailyTransactionsData',
            'vehicleTypeLabels',
            'vehicleTypeData',
            'recentTransactions'
        ));
    }
}
