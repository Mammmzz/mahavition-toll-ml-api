<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tarif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TarifController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tarifs = Tarif::all();
        return response()->json([
            'success' => true,
            'data' => $tarifs
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kelompok_kendaraan' => 'required|string',
            'harga' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarif = Tarif::create([
            'kelompok_kendaraan' => $request->kelompok_kendaraan,
            'harga' => $request->harga
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tarif created successfully',
            'data' => $tarif
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tarif = Tarif::find($id);
        
        if (!$tarif) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tarif
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tarif = Tarif::find($id);
        
        if (!$tarif) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'kelompok_kendaraan' => 'sometimes|string',
            'harga' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarif->update($request->only(['kelompok_kendaraan', 'harga']));

        return response()->json([
            'success' => true,
            'message' => 'Tarif updated successfully',
            'data' => $tarif
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tarif = Tarif::find($id);
        
        if (!$tarif) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif not found'
            ], 404);
        }

        $tarif->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tarif deleted successfully'
        ]);
    }

    /**
     * Get tarif by vehicle type
     */
    public function getByVehicleType(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kelompok_kendaraan' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $tarif = Tarif::where('kelompok_kendaraan', $request->kelompok_kendaraan)->first();

        if (!$tarif) {
            return response()->json([
                'success' => false,
                'message' => 'Tarif for this vehicle type not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $tarif
        ]);
    }
}