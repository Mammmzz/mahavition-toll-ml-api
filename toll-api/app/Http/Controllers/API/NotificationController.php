<?php

namespace App\Http\Controllers\API;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    /**
     * Kirim notifikasi test ke user yang sedang login
     */
    public function sendTest(Request $request, FirebaseService $fcm)
    {
        $request->validate([
            'title'   => 'required|string',
            'body'    => 'required|string',
        ]);

        $userId = $request->user()->id;

        // Ambil semua token dari user yang sedang login
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'No device tokens found for your account. Please register a device token first using POST /api/device-tokens',
                'user_id' => $userId,
            ], 404);
        }

        // Kirim ke semua token dan hapus token yang tidak valid
        $results = [];
        $sentCount = 0;
        foreach ($tokens as $token) {
            $result = $fcm->sendToToken($token, $request->title, $request->body);
            $results[] = $result;

            // Cek jika token tidak valid dan hapus dari DB
            if (isset($result['error']) && (
                str_contains($result['error'], 'invalid registration token') || 
                str_contains($result['error'], 'Unregistered')
            )) {
                DeviceToken::where('token', $token)->delete();
            } else {
                $sentCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifications sent to ' . $sentCount . ' of ' . count($tokens) . ' device(s). Invalid tokens were removed.',
            'devices_count' => count($tokens),
            'results' => $results,
        ]);
    }

    /**
     * Kirim notifikasi transaksi toll ke user yang sedang login
     */
    public function notifyTransaction(Request $request, FirebaseService $fcm)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'data' => 'array',
        ]);

        $userId = $request->user()->id;

        // Ambil semua token dari user yang sedang login
        $tokens = DeviceToken::where('user_id', $userId)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'No device tokens found for your account. Please register a device token first.',
                'user_id' => $userId,
            ], 404);
        }

        // Data tambahan untuk notifikasi
        $notificationData = $request->input('data', []);
        
        // Kirim ke semua token dan hapus token yang tidak valid
        $results = [];
        $sentCount = 0;
        foreach ($tokens as $token) {
            $result = $fcm->sendToToken(
                $token,
                $request->title,
                $request->body,
                $notificationData
            );
            $results[] = $result;

            // Cek jika token tidak valid dan hapus dari DB
            if (isset($result['error']) && (
                str_contains($result['error'], 'invalid registration token') || 
                str_contains($result['error'], 'Unregistered')
            )) {
                DeviceToken::where('token', $token)->delete();
            } else {
                $sentCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaction notifications sent to ' . $sentCount . ' of ' . count($tokens) . ' device(s). Invalid tokens were removed.',
            'devices_count' => count($tokens),
            'results' => $results,
        ]);
    }
}
