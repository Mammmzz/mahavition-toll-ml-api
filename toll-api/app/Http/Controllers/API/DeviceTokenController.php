<?php

namespace App\Http\Controllers\API;

use App\Models\DeviceToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'platform' => 'nullable|string',
        ]);

        $userId = $request->user()?->id;

        DeviceToken::updateOrCreate(
            ['token' => $request->token],
            ['user_id' => $userId, 'platform' => $request->platform]
        );

        return response()->json(['success' => true, 'message' => 'Token saved']);
    }
}

