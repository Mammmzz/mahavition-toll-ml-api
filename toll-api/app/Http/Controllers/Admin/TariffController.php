<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tarif;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TariffController extends Controller
{
    /**
     * Display a listing of tariffs
     */
    public function index()
    {
        $tariffs = Tarif::orderBy('kelompok_kendaraan')->get();
        
        // Get transaction counts for each tariff
        foreach ($tariffs as $tariff) {
            $tariff->transaction_count = Transaction::where('tarif_id', $tariff->id)->count();
        }
        
        return view('admin.tariffs.index', compact('tariffs'));
    }
    
    /**
     * Show the form for creating a new tariff
     */
    public function create()
    {
        return view('admin.tariffs.create');
    }
    
    /**
     * Store a newly created tariff
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kelompok_kendaraan' => 'required|string|max:50|unique:tarifs',
            'harga' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        Tarif::create([
            'kelompok_kendaraan' => $request->kelompok_kendaraan,
            'harga' => $request->harga,
        ]);
        
        return redirect()->route('admin.tariffs.index')
            ->with('success', 'Tarif berhasil ditambahkan');
    }
    
    /**
     * Display the specified tariff
     */
    public function show(Tarif $tariff)
    {
        // Get recent transactions using this tariff
        $recentTransactions = Transaction::where('tarif_id', $tariff->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
        
        // Get transaction count
        $transactionCount = Transaction::where('tarif_id', $tariff->id)->count();
        
        return view('admin.tariffs.show', compact('tariff', 'recentTransactions', 'transactionCount'));
    }
    
    /**
     * Show the form for editing the specified tariff
     */
    public function edit(Tarif $tariff)
    {
        return view('admin.tariffs.edit', compact('tariff'));
    }
    
    /**
     * Update the specified tariff
     */
    public function update(Request $request, Tarif $tariff)
    {
        $validator = Validator::make($request->all(), [
            'kelompok_kendaraan' => 'required|string|max:50|unique:tarifs,kelompok_kendaraan,' . $tariff->id,
            'harga' => 'required|numeric|min:0',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $tariff->update([
            'kelompok_kendaraan' => $request->kelompok_kendaraan,
            'harga' => $request->harga,
        ]);
        
        return redirect()->route('admin.tariffs.index')
            ->with('success', 'Tarif berhasil diperbarui');
    }
    
    /**
     * Remove the specified tariff
     */
    public function destroy(Tarif $tariff)
    {
        // Check if tariff is being used in transactions
        $hasTransactions = Transaction::where('tarif_id', $tariff->id)->exists();
        
        if ($hasTransactions) {
            return redirect()->back()
                ->with('error', 'Tarif tidak dapat dihapus karena sedang digunakan dalam transaksi');
        }
        
        $tariff->delete();
        
        return redirect()->route('admin.tariffs.index')
            ->with('success', 'Tarif berhasil dihapus');
    }
}
