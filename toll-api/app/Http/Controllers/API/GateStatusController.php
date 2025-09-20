<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GateCondition;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GateStatusController extends Controller
{
    /**
     * Get current gate status
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatus()
    {
        // Ambil satu-satunya record di gate_condition (atau buat jika belum ada)
        $gateCondition = GateCondition::firstOrCreate(
            ['id' => 1],
            [
                'gate_status' => 'closed',
                'saldo' => 0
            ]
        );
        
        // Format saldo dengan pemisah ribuan
        $formattedSaldo = "Rp" . number_format($gateCondition->saldo, 0, ',', '.');
        
        // Konversi status untuk kompatibilitas dengan kode Arduino yang sudah ada
        $arduinoStatus = ($gateCondition->gate_status === 'open') ? 'on' : 'off';
        
        return response()->json([
            "success" => true,
            "gate_status" => $arduinoStatus,
            "sisa_saldo" => $formattedSaldo,
            "timestamp" => Carbon::now()->toDateTimeString()
        ]);
    }
    
    /**
     * Open the toll gate when transaction is successful
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function openGate(Request $request)
    {
        // Validasi request - hanya validasi saldo jika ada
        $request->validate([
            'saldo' => 'sometimes|numeric'
        ]);
        
        // Update satu-satunya record (atau buat jika belum ada)
        $gateCondition = GateCondition::updateOrCreate(
            ['id' => 1],
            [
                'gate_status' => 'open',
                'saldo' => $request->saldo ?? 0
            ]
        );
        
        return response()->json([
            "success" => true,
            "message" => "Gerbang tol berhasil dibuka",
            "gate_status" => $gateCondition->gate_status,
            "timestamp" => Carbon::now()->toDateTimeString()
        ]);
    }
    
    /**
     * Close the toll gate after timeout (5 seconds)
     * 
     * Arduino akan memanggil endpoint ini dengan cara:
     * 1. Memeriksa status gerbang melalui endpoint /gate-status
     * 2. Jika status "open", Arduino menunggu 5 detik
     * 3. Setelah 5 detik, Arduino memanggil endpoint ini (/gate-close) untuk menutup gerbang
     * 
     * Contoh penggunaan di Arduino:
     * - Buat timer/interval 5 detik setelah membaca status "open"
     * - Setelah timer berakhir, panggil HTTP POST ke endpoint /gate-close
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function closeGate()
    {
        // Update satu-satunya record (atau buat jika belum ada)
        $gateCondition = GateCondition::updateOrCreate(
            ['id' => 1],
            ['gate_status' => 'closed']
        );
        
        return response()->json([
            "success" => true,
            "message" => "Gerbang tol berhasil ditutup",
            "gate_status" => $gateCondition->gate_status,
            "timestamp" => Carbon::now()->toDateTimeString()
        ]);
    }
}