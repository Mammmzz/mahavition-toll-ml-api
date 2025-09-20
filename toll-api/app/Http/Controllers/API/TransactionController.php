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
use Illuminate\Support\Facades\Log;

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

            // Check if Pay Later is enabled (dapat ditambahkan ke request atau config)
            $payLaterEnabled = $request->input('pay_later', true); // Default true untuk sistem pay later
            
            // Handle insufficient balance
            if ($user->saldo < $tarif->harga) {
                if (!$payLaterEnabled) {
                    // Logic lama - Buat transaksi FAILED jika pay later disabled
                    $now = now();
                    $transaction = Transaction::create([
                        'user_id' => $user->id,
                        'tarif_id' => $tarif->id,
                        'plat_nomor' => $request->plat_nomor,
                        'jenis_kendaraan' => $request->jenis_kendaraan,
                        'saldo_pembayaran' => $user->saldo,
                        'status' => 'FAILED',
                        'created_at' => $now,
                        'updated_at' => $now
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
                    ], 402);
                } else {
                    // Pay Later System - Allow negative balance
                    Log::info('Pay Later Transaction', [
                        'user_id' => $user->id,
                        'plat_nomor' => $request->plat_nomor,
                        'current_balance' => $user->saldo,
                        'required_amount' => $tarif->harga,
                        'shortage' => $tarif->harga - $user->saldo
                    ]);
                }
            }

            // Simpan saldo lama sebelum update
            $oldBalance = $user->saldo;
            $payLaterMode = $user->saldo < $tarif->harga;
            
            // Deduct balance (bisa menjadi negatif jika pay later)
            $newBalance = $user->saldo - $tarif->harga;
            $user->update(['saldo' => $newBalance]);

            // Update gate condition to open & saldo
            GateCondition::updateOrCreate(
                ['id' => 1],
                [
                    'gate_status' => 'open',
                    'saldo' => $newBalance
                ]
            );
      
            // Create successful transaction record
            $now = now(); // Get current time based on app timezone
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'tarif_id' => $tarif->id,
                'plat_nomor' => $request->plat_nomor,
                'jenis_kendaraan' => $request->jenis_kendaraan,
                'saldo_pembayaran' => $tarif->harga,
                'status' => 'SUCCESS',
                'created_at' => $now,
                'updated_at' => $now
            ]);

            DB::commit();
            
            $message = $payLaterMode ? 'Transaction successful (Pay Later)' : 'Transaction successful';
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'user' => $user->fresh(),
                    'tarif' => $tarif,
                    'transaction' => $transaction,
                    'previous_balance' => $oldBalance, // Saldo sebelum dikurangi
                    'current_balance' => $newBalance, // Saldo setelah dikurangi (bisa negatif)
                    'pay_later' => $payLaterMode,
                    'negative_balance' => $newBalance < 0
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

   

}