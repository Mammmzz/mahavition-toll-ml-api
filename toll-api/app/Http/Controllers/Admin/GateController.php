<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GateCondition;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GateController extends Controller
{
    /**
     * Display gate management page
     */
    public function index()
    {
        // Get gate status
        $gate = GateCondition::first();
        
        if (!$gate) {
            $gate = GateCondition::create([
                'gate_status' => 'off',
                'last_transaction_id' => null,
            ]);
        }
        
        // Get recent gate activities
        $recentActivities = Transaction::select(
            'transactions.id',
            'transactions.plat_nomor',
            'transactions.jenis_kendaraan',
            'transactions.saldo_pembayaran',
            'transactions.status',
            'transactions.created_at'
        )
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Get gate usage statistics
        $dailyGateUsage = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'success')
            ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        $dailyLabels = [];
        $dailyData = [];
        
        // Fill in any missing days
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $dailyLabels[] = Carbon::parse($date)->format('d M');
            
            $found = false;
            foreach ($dailyGateUsage as $usage) {
                if ($usage->date == $date) {
                    $dailyData[] = $usage->count;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $dailyData[] = 0;
            }
        }
        
        // Get hourly distribution for today
        $hourlyGateUsage = Transaction::select(
            DB::raw('HOUR(created_at) as hour'),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'success')
            ->whereDate('created_at', Carbon::today())
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        $hourlyLabels = [];
        $hourlyData = [];
        
        // Fill in all hours
        for ($i = 0; $i < 24; $i++) {
            $hourlyLabels[] = sprintf("%02d:00", $i);
            
            $found = false;
            foreach ($hourlyGateUsage as $usage) {
                if ($usage->hour == $i) {
                    $hourlyData[] = $usage->count;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $hourlyData[] = 0;
            }
        }
        
        return view('admin.gate.index', compact(
            'gate',
            'recentActivities',
            'dailyLabels',
            'dailyData',
            'hourlyLabels',
            'hourlyData'
        ));
    }
    
    /**
     * Toggle gate status
     */
    public function toggleGate(Request $request)
    {
        $gate = GateCondition::first();
        
        if (!$gate) {
            $gate = GateCondition::create([
                'gate_status' => 'off',
                'last_transaction_id' => null,
            ]);
        }
        
        // Toggle gate status
        $newStatus = $gate->gate_status === 'on' ? 'off' : 'on';
        
        $gate->update([
            'gate_status' => $newStatus,
        ]);
        
        $statusText = $newStatus === 'on' ? 'dibuka' : 'ditutup';
        
        return redirect()->back()->with('success', "Gerbang berhasil $statusText");
    }
}
