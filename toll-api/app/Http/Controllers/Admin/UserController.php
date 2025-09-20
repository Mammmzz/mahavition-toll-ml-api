<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $query = User::query();
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('plat_nomor', 'like', "%{$search}%");
            });
        }
        
        // Filter by vehicle type
        if ($request->has('vehicle_type') && $request->vehicle_type != '') {
            $query->where('kelompok_kendaraan', $request->vehicle_type);
        }
        
        // Sort by column
        $sortColumn = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortColumn, $sortOrder);
        
        $users = $query->paginate(10);
        
        // Get unique vehicle types for filter dropdown
        $vehicleTypes = User::select('kelompok_kendaraan')
            ->distinct()
            ->whereNotNull('kelompok_kendaraan')
            ->pluck('kelompok_kendaraan');
        
        return view('admin.users.index', compact('users', 'vehicleTypes'));
    }
    
    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        return view('admin.users.create');
    }
    
    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'plat_nomor' => 'required|string|max:20|unique:users',
            'kelompok_kendaraan' => 'required|string|max:50',
            'saldo' => 'required|numeric|min:0',
            'alamat' => 'nullable|string|max:255',
            'no_telp' => 'nullable|string|max:20',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'plat_nomor' => strtoupper($request->plat_nomor),
            'kelompok_kendaraan' => $request->kelompok_kendaraan,
            'saldo' => $request->saldo,
            'alamat' => $request->alamat,
            'no_telp' => $request->no_telp,
        ]);
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan');
    }
    
    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        // Get user's transactions
        $transactions = Transaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        // Calculate statistics
        $totalTransactions = Transaction::where('user_id', $user->id)->count();
        $successTransactions = Transaction::where('user_id', $user->id)
            ->where('status', 'success')
            ->count();
        $totalSpent = Transaction::where('user_id', $user->id)
            ->where('status', 'success')
            ->sum('saldo_pembayaran');
        
        return view('admin.users.show', compact(
            'user',
            'transactions',
            'totalTransactions',
            'successTransactions',
            'totalSpent'
        ));
    }
    
    /**
     * Show the form for editing the specified user
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }
    
    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'plat_nomor' => 'required|string|max:20|unique:users,plat_nomor,' . $user->id,
            'kelompok_kendaraan' => 'required|string|max:50',
            'saldo' => 'required|numeric|min:0',
            'alamat' => 'nullable|string|max:255',
            'no_telp' => 'nullable|string|max:20',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'plat_nomor' => strtoupper($request->plat_nomor),
            'kelompok_kendaraan' => $request->kelompok_kendaraan,
            'saldo' => $request->saldo,
            'alamat' => $request->alamat,
            'no_telp' => $request->no_telp,
        ]);
        
        // Update password if provided
        if ($request->filled('password')) {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string|min:8|confirmed',
            ]);
            
            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }
            
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui');
    }
    
    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Check if user has transactions
        $hasTransactions = Transaction::where('user_id', $user->id)->exists();
        
        if ($hasTransactions) {
            return redirect()->back()
                ->with('error', 'Pengguna tidak dapat dihapus karena memiliki transaksi');
        }
        
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus');
    }
    
    /**
     * Update user's balance
     */
    public function updateBalance(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'type' => 'required|in:add,subtract',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $amount = $request->amount;
        $currentBalance = $user->saldo;
        
        if ($request->type === 'add') {
            $user->update([
                'saldo' => $currentBalance + $amount,
            ]);
            $message = "Saldo berhasil ditambahkan: Rp " . number_format($amount, 0, ',', '.');
        } else {
            if ($currentBalance < $amount) {
                return redirect()->back()
                    ->with('error', 'Saldo tidak mencukupi untuk pengurangan');
            }
            
            $user->update([
                'saldo' => $currentBalance - $amount,
            ]);
            $message = "Saldo berhasil dikurangi: Rp " . number_format($amount, 0, ',', '.');
        }
        
        return redirect()->back()->with('success', $message);
    }
}
