<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Tarif;
use App\Models\GateCondition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with(['user', 'tarif'])->get();
        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'tarif_id' => 'required|exists:tarifs,id',
            'plat_nomor' => 'required|string',
            'jenis_kendaraan' => 'required|string',
            'saldo_pembayaran' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $transaction = Transaction::create([
            'user_id' => $request->user_id,
            'tarif_id' => $request->tarif_id,
            'plat_nomor' => $request->plat_nomor,
            'jenis_kendaraan' => $request->jenis_kendaraan,
            'saldo_pembayaran' => $request->saldo_pembayaran,
            'status' => 'SUCCESS'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully',
            'data' => $transaction->load(['user', 'tarif'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $transaction = Transaction::with(['user', 'tarif'])->find($id);
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Get transactions by user
     */
    public function getByUser(string $userId)
    {
        $user = User::find($userId);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $transactions = Transaction::with(['tarif'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Process plate transaction - Main function for toll gate
     */
    public function processPlateTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plat_nomor' => 'required|string',
            'jenis_kendaraan' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Find user by plate number
            $user = User::where('plat_nomor', $request->plat_nomor)->first();
            
            if (!$user) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Plate number not registered'
                ], 404);
            }

            // Find tarif by vehicle type
            $tarif = Tarif::where('kelompok_kendaraan', $request->jenis_kendaraan)->first();
            
            if (!$tarif) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Tarif for this vehicle type not found'
                ], 404);
            }

            // Check if user has enough balance
            if ($user->saldo < $tarif->harga) {
                // Create failed transaction record
                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'tarif_id' => $tarif->id,
                    'plat_nomor' => $request->plat_nomor,
                    'jenis_kendaraan' => $request->jenis_kendaraan,
                    'saldo_pembayaran' => $user->saldo,
                    'status' => 'FAILED'
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance',
                    'data' => [
                        'user' => $user,
                        'tarif' => $tarif,
                        'transaction' => $transaction,
                        'balance_needed' => $tarif->harga,
                        'current_balance' => $user->saldo
                    ]
                ], 402); // Payment Required
            }

            // Simpan saldo lama sebelum update
            $oldBalance = $user->saldo;
            
            // Deduct balance
            $newBalance = $user->saldo - $tarif->harga;
            $user->update(['saldo' => $newBalance]);

            // Update gate condition ON & saldo
            GateCondition::query()->update([
                'gate_status' => 'ON',
                'saldo' => $newBalance
            ]);
      
            // Create successful transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'tarif_id' => $tarif->id,
                'plat_nomor' => $request->plat_nomor,
                'jenis_kendaraan' => $request->jenis_kendaraan,
                'saldo_pembayaran' => $tarif->harga,
                'status' => 'SUCCESS'
            ]);

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Transaction successful',
                'data' => [
                    'user' => $user->fresh(),
                    'tarif' => $tarif,
                    'transaction' => $transaction,
                    'previous_balance' => $oldBalance, // Saldo sebelum dikurangi
                    'current_balance' => $newBalance // Saldo setelah dikurangi
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getGateStatus()
    {
        // Ambil transaksi terakhir yang berhasil
        $lastTransaction = Transaction::where('status', 'SUCCESS')
            ->latest()
            ->first();

        if (!$lastTransaction) {
            // Kalau belum ada transaksi sukses
            return response()->json([
                "success" => false,
                "gate_status" => "off",
                "sisa_saldo" => "Rp0"
            ], 404);
        }

        // Ambil saldo user setelah transaksi terakhir
        $user = $lastTransaction->user;
        $sisaSaldo = "Rp" . number_format($user->saldo, 0, ',', '.');

        return response()->json([
            "success" => true,
            "gate_status" => "on",
            "sisa_saldo" => $sisaSaldo
        ]);
    }

}