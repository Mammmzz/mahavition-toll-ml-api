<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of transactions
     */
    public function index(Request $request)
    {
        $query = Transaction::with('user');
        
        // Filter by user
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        // Filter by plate number
        if ($request->has('plat_nomor') && $request->plat_nomor) {
            $query->where('plat_nomor', 'like', '%' . $request->plat_nomor . '%');
        }
        
        // Filter by vehicle type
        if ($request->has('jenis_kendaraan') && $request->jenis_kendaraan) {
            $query->where('jenis_kendaraan', $request->jenis_kendaraan);
        }
        
        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Sort by column
        $sortColumn = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortColumn, $sortOrder);
        
        $transactions = $query->paginate(15);
        
        // Get unique vehicle types for filter dropdown
        $vehicleTypes = Transaction::select('jenis_kendaraan')
            ->distinct()
            ->whereNotNull('jenis_kendaraan')
            ->pluck('jenis_kendaraan');
        
        // Get users for filter dropdown
        $users = User::select('id', 'name', 'plat_nomor')
            ->orderBy('name')
            ->get();
        
        return view('admin.transactions.index', compact('transactions', 'vehicleTypes', 'users'));
    }
    
    /**
     * Display the specified transaction
     */
    public function show(Transaction $transaction)
    {
        $transaction->load('user');
        
        return view('admin.transactions.show', compact('transaction'));
    }
    
    /**
     * Show the form for editing the specified transaction
     */
    public function edit(Transaction $transaction)
    {
        $transaction->load('user');
        
        return view('admin.transactions.edit', compact('transaction'));
    }
    
    /**
     * Update the specified transaction
     */
    public function update(Request $request, Transaction $transaction)
    {
        $request->validate([
            'status' => 'required|in:success,failed',
            'saldo_pembayaran' => 'required|numeric|min:0',
        ]);
        
        $transaction->update([
            'status' => $request->status,
            'saldo_pembayaran' => $request->saldo_pembayaran,
        ]);
        
        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaksi berhasil diperbarui');
    }
    
    /**
     * Remove the specified transaction
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();
        
        return redirect()->route('admin.transactions.index')
            ->with('success', 'Transaksi berhasil dihapus');
    }
    
    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $query = Transaction::with('user');
        
        // Apply filters (same as index method)
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('plat_nomor') && $request->plat_nomor) {
            $query->where('plat_nomor', 'like', '%' . $request->plat_nomor . '%');
        }
        
        if ($request->has('jenis_kendaraan') && $request->jenis_kendaraan) {
            $query->where('jenis_kendaraan', $request->jenis_kendaraan);
        }
        
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')->get();
        
        $filename = 'transactions_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];
        
        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, [
                'ID',
                'User ID',
                'Nama Pengguna',
                'Plat Nomor',
                'Jenis Kendaraan',
                'Jumlah',
                'Status',
                'Tanggal',
            ]);
            
            // Add data rows
            foreach ($transactions as $transaction) {
                fputcsv($file, [
                    $transaction->id,
                    $transaction->user_id,
                    $transaction->user->name ?? 'N/A',
                    $transaction->plat_nomor,
                    $transaction->jenis_kendaraan,
                    $transaction->saldo_pembayaran,
                    $transaction->status,
                    $transaction->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
}
