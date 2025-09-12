<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users',
            'nama_lengkap' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'plat_nomor' => 'nullable|unique:users',
            'alamat' => 'nullable',
            'no_telp' => 'nullable',
            'saldo' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'username' => $request->username,
            'nama_lengkap' => $request->nama_lengkap,
            'name' => $request->nama_lengkap, // untuk compatibility
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'plat_nomor' => $request->plat_nomor,
            'alamat' => $request->alamat,
            'no_telp' => $request->no_telp,
            'saldo' => $request->saldo ?? 0
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'username' => 'sometimes|unique:users,username,' . $id,
            'nama_lengkap' => 'sometimes',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'plat_nomor' => 'nullable|unique:users,plat_nomor,' . $id,
            'alamat' => 'nullable',
            'no_telp' => 'nullable',
            'saldo' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'username', 'nama_lengkap', 'email', 'plat_nomor', 
            'alamat', 'no_telp', 'saldo'
        ]));

        if ($request->has('nama_lengkap')) {
            $user->update(['name' => $request->nama_lengkap]);
        }

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $user
        ]);
    }

    /**
     * Get user by plate number
     */
    public function getByPlatNomor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'plat_nomor' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('plat_nomor', $request->plat_nomor)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User with this plate number not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    /**
     * Update user balance
     */
    public function updateSaldo(Request $request, string $id)
    {
        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'saldo' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update(['saldo' => $request->saldo]);

        return response()->json([
            'success' => true,
            'message' => 'Balance updated successfully',
            'data' => $user
        ]);
    }
}