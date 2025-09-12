<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlateController extends Controller
{
    /**
     * Validate if a plate number exists in the database
     */
    public function validatePlate(Request $request, $plat_nomor = null)
    {
        // Jika menggunakan route GET dengan parameter di URL
        if ($plat_nomor) {
            $plateNumber = urldecode($plat_nomor);
        }
        // Jika menggunakan route POST dengan JSON body
        else {
            $validator = Validator::make($request->all(), [
                'plat_nomor' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $plateNumber = $request->plat_nomor;
        }
        
        // Log untuk debugging
        \Log::info('Validating plate number: ' . $plateNumber);
        
        // Cari user berdasarkan plat nomor
        $user = User::where('plat_nomor', $plateNumber)->first();
        
        // Debug log
        \Log::info('User found: ' . ($user ? 'Yes' : 'No'));
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Plat nomor tidak terdaftar',
                'plate' => $plateNumber
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => 'Plat nomor terdaftar',
            'plate' => $plateNumber,
            'data' => [
                'user_id' => $user->id,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'saldo' => $user->saldo,
                'plat_nomor' => $user->plat_nomor,
                'kelompok_kendaraan' => $user->kelompok_kendaraan
            ]
        ], 200);
    }

    /**
     * Validate plate number AND vehicle type (double security)
     */
    public function validatePlateAndVehicle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plat_nomor' => 'required|string',
            'kelompok_kendaraan' => 'required|string|in:Mobil,Bus,Truk,ambulan'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $plateNumber = $request->plat_nomor;
        $vehicleType = $request->kelompok_kendaraan;
        
        // Log untuk debugging
        \Log::info('Validating plate: ' . $plateNumber . ' with vehicle type: ' . $vehicleType);
        
        // Cari user berdasarkan plat nomor
        $user = User::where('plat_nomor', $plateNumber)->first();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Plat nomor tidak terdaftar',
                'plate' => $plateNumber,
                'vehicle_type' => $vehicleType
            ], 404);
        }
        
        // Cek apakah kelompok kendaraan cocok
        if ($user->kelompok_kendaraan !== $vehicleType) {
            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'Kelompok kendaraan tidak sesuai',
                'plate' => $plateNumber,
                'detected_vehicle' => $vehicleType,
                'registered_vehicle' => $user->kelompok_kendaraan,
                'error_type' => 'VEHICLE_TYPE_MISMATCH'
            ], 403); // Forbidden
        }
        
        return response()->json([
            'success' => true,
            'valid' => true,
            'message' => 'Plat nomor dan kelompok kendaraan valid',
            'plate' => $plateNumber,
            'vehicle_type' => $vehicleType,
            'data' => [
                'user_id' => $user->id,
                'username' => $user->username,
                'nama_lengkap' => $user->nama_lengkap,
                'saldo' => $user->saldo,
                'plat_nomor' => $user->plat_nomor,
                'kelompok_kendaraan' => $user->kelompok_kendaraan
            ]
        ], 200);
    }

    /**
     * Get all registered plates
     */
    public function getAllPlates()
    {
        $users = User::whereNotNull('plat_nomor')
                    ->select('id', 'username', 'nama_lengkap', 'plat_nomor', 'kelompok_kendaraan', 'saldo')
                    ->get();

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}