<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ReportController extends Controller
{
    /**
     * Display the reports index page
     */
    public function index()
    {
        return view('admin.reports.index');
    }
    
    /**
     * Display daily reports
     */
    public function daily(Request $request)
    {
        // Default to current month if not specified
        $month = $request->month ?? Carbon::now()->format('Y-m');
        
        // Parse the month
        $date = Carbon::createFromFormat('Y-m', $month);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        
        // Get daily transactions for the selected month
        $dailyTransactions = Transaction::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_count'),
            DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
            DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'),
            DB::raw('SUM(CASE WHEN status = "success" THEN saldo_pembayaran ELSE 0 END) as total_revenue')
        )
            ->whereDate('created_at', '>=', $startOfMonth)
            ->whereDate('created_at', '<=', $endOfMonth)
            ->groupBy('date')
            ->orderBy('date')
            ->get();
        
        // Prepare data for chart
        $labels = [];
        $totalData = [];
        $successData = [];
        $failedData = [];
        $revenueData = [];
        
        // Fill in all days of the month
        $currentDate = $startOfMonth->copy();
        while ($currentDate <= $endOfMonth) {
            $dateStr = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('d M');
            
            $dayData = $dailyTransactions->firstWhere('date', $dateStr);
            
            $totalData[] = $dayData ? $dayData->total_count : 0;
            $successData[] = $dayData ? $dayData->success_count : 0;
            $failedData[] = $dayData ? $dayData->failed_count : 0;
            $revenueData[] = $dayData ? $dayData->total_revenue : 0;
            
            $currentDate->addDay();
        }
        
        // Calculate summary statistics
        $totalTransactions = array_sum($totalData);
        $totalSuccessTransactions = array_sum($successData);
        $totalFailedTransactions = array_sum($failedData);
        $totalRevenue = array_sum($revenueData);
        
        // Get available months for the dropdown
        $availableMonths = Transaction::select(
            DB::raw('DISTINCT DATE_FORMAT(created_at, "%Y-%m") as month')
        )
            ->orderBy('month', 'desc')
            ->pluck('month');
        
        return view('admin.reports.daily', compact(
            'month',
            'labels',
            'totalData',
            'successData',
            'failedData',
            'revenueData',
            'totalTransactions',
            'totalSuccessTransactions',
            'totalFailedTransactions',
            'totalRevenue',
            'availableMonths'
        ));
    }
    
    /**
     * Display monthly reports
     */
    public function monthly(Request $request)
    {
        // Default to current year if not specified
        $year = $request->year ?? Carbon::now()->format('Y');
        
        // Get monthly transactions for the selected year
        $monthlyTransactions = Transaction::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as total_count'),
            DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
            DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'),
            DB::raw('SUM(CASE WHEN status = "success" THEN saldo_pembayaran ELSE 0 END) as total_revenue')
        )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();
        
        // Prepare data for chart
        $labels = [];
        $totalData = [];
        $successData = [];
        $failedData = [];
        $revenueData = [];
        
        // Fill in all months of the year
        $monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        
        for ($i = 1; $i <= 12; $i++) {
            $labels[] = $monthNames[$i - 1];
            
            $monthData = $monthlyTransactions->firstWhere('month', $i);
            
            $totalData[] = $monthData ? $monthData->total_count : 0;
            $successData[] = $monthData ? $monthData->success_count : 0;
            $failedData[] = $monthData ? $monthData->failed_count : 0;
            $revenueData[] = $monthData ? $monthData->total_revenue : 0;
        }
        
        // Calculate summary statistics
        $totalTransactions = array_sum($totalData);
        $totalSuccessTransactions = array_sum($successData);
        $totalFailedTransactions = array_sum($failedData);
        $totalRevenue = array_sum($revenueData);
        
        // Get available years for the dropdown
        $availableYears = Transaction::select(
            DB::raw('DISTINCT YEAR(created_at) as year')
        )
            ->orderBy('year', 'desc')
            ->pluck('year');
        
        return view('admin.reports.monthly', compact(
            'year',
            'labels',
            'totalData',
            'successData',
            'failedData',
            'revenueData',
            'totalTransactions',
            'totalSuccessTransactions',
            'totalFailedTransactions',
            'totalRevenue',
            'availableYears'
        ));
    }
    
    /**
     * Display vehicle type reports
     */
    public function vehicleType(Request $request)
    {
        // Default to current month if not specified
        $period = $request->period ?? 'month';
        
        $query = Transaction::query();
        
        // Apply period filter
        if ($period === 'month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year);
            $periodLabel = 'Bulan Ini (' . Carbon::now()->format('F Y') . ')';
        } elseif ($period === 'year') {
            $query->whereYear('created_at', Carbon::now()->year);
            $periodLabel = 'Tahun Ini (' . Carbon::now()->format('Y') . ')';
        } elseif ($period === 'all') {
            $periodLabel = 'Semua Waktu';
        }
        
        // Get vehicle type distribution
        $vehicleTypeData = $query->select(
            'jenis_kendaraan',
            DB::raw('COUNT(*) as total_count'),
            DB::raw('SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count'),
            DB::raw('SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count'),
            DB::raw('SUM(CASE WHEN status = "success" THEN saldo_pembayaran ELSE 0 END) as total_revenue')
        )
            ->groupBy('jenis_kendaraan')
            ->orderBy('total_count', 'desc')
            ->get();
        
        // Prepare data for charts
        $labels = [];
        $countData = [];
        $revenueData = [];
        $colors = [
            'rgba(59, 130, 246, 0.7)',   // Blue
            'rgba(16, 185, 129, 0.7)',   // Green
            'rgba(245, 158, 11, 0.7)',   // Yellow
            'rgba(239, 68, 68, 0.7)',    // Red
            'rgba(139, 92, 246, 0.7)',   // Purple
            'rgba(236, 72, 153, 0.7)',   // Pink
        ];
        
        foreach ($vehicleTypeData as $index => $data) {
            $labels[] = $data->jenis_kendaraan ?? 'Tidak Diketahui';
            $countData[] = $data->total_count;
            $revenueData[] = $data->total_revenue;
        }
        
        // Calculate summary statistics
        $totalTransactions = $vehicleTypeData->sum('total_count');
        $totalSuccessTransactions = $vehicleTypeData->sum('success_count');
        $totalFailedTransactions = $vehicleTypeData->sum('failed_count');
        $totalRevenue = $vehicleTypeData->sum('total_revenue');
        
        return view('admin.reports.vehicle-type', compact(
            'period',
            'periodLabel',
            'labels',
            'countData',
            'revenueData',
            'colors',
            'vehicleTypeData',
            'totalTransactions',
            'totalSuccessTransactions',
            'totalFailedTransactions',
            'totalRevenue'
        ));
    }
    
    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $type = $request->type ?? 'daily';
        $period = $request->period ?? null;
        
        $query = Transaction::with('user')->select('*');
        
        // Apply filters based on report type
        if ($type === 'daily') {
            $month = $period ?? Carbon::now()->format('Y-m');
            $date = Carbon::createFromFormat('Y-m', $month);
            
            $query->whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year);
                
            $filename = 'daily_report_' . $month . '.csv';
        } elseif ($type === 'monthly') {
            $year = $period ?? Carbon::now()->format('Y');
            
            $query->whereYear('created_at', $year);
            
            $filename = 'monthly_report_' . $year . '.csv';
        } elseif ($type === 'vehicle-type') {
            if ($period === 'month') {
                $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
                $filename = 'vehicle_type_report_' . Carbon::now()->format('Y-m') . '.csv';
            } elseif ($period === 'year') {
                $query->whereYear('created_at', Carbon::now()->year);
                $filename = 'vehicle_type_report_' . Carbon::now()->format('Y') . '.csv';
            } else {
                $filename = 'vehicle_type_report_all_time.csv';
            }
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'Tanggal',
                'User ID',
                'Nama Pengguna',
                'Plat Nomor',
                'Jenis Kendaraan',
                'Jumlah',
                'Status',
            ]);
            
            // Add data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                    $transaction->user_id,
                    $transaction->user->name ?? 'N/A',
                    $transaction->plat_nomor,
                    $transaction->jenis_kendaraan,
                    $transaction->saldo_pembayaran,
                    $transaction->status,
                ]);
            }
            
            fclose($file);
        };
        
        return Response::stream($callback, 200, $headers);
    }
}
