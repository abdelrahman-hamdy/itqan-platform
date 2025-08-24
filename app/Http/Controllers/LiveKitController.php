<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;

class LiveKitController extends Controller
{
    /**
     * Get LiveKit access token for a participant
     */
    public function getToken(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'room_name' => 'required|string',
                'participant_name' => 'required|string',
                'user_type' => 'required|string|in:quran_teacher,student'
            ]);

            $roomName = $request->input('room_name');
            $participantName = $request->input('participant_name');
            $userType = $request->input('user_type');

            // Get LiveKit configuration
            $apiKey = config('livekit.api_key');
            $apiSecret = config('livekit.api_secret');

            if (!$apiKey || !$apiSecret) {
                return response()->json([
                    'error' => 'LiveKit configuration not found'
                ], 500);
            }

            // Create access token options with metadata
            $metadata = json_encode([
                'userType' => $userType,
                'displayName' => $participantName,
                'role' => $userType === 'quran_teacher' ? 'teacher' : 'student'
            ]);
            
            $options = new AccessTokenOptions([
                'identity' => $participantName,
                'name' => $participantName,
                'metadata' => $metadata,
            ]);

            // Create access token
            $at = new AccessToken($apiKey, $apiSecret, $options);

            // Create video grant with properties
            $grantProperties = [
                'roomJoin' => true,
                'room' => $roomName,
            ];

            // Teachers get additional permissions
            if ($userType === 'quran_teacher') {
                $grantProperties['roomAdmin'] = true;
                $grantProperties['roomCreate'] = true;
                $grantProperties['canPublish'] = true;
                $grantProperties['canSubscribe'] = true;
            } else {
                // Students get basic permissions
                $grantProperties['canPublish'] = true;
                $grantProperties['canSubscribe'] = true;
            }

            // Create video grant
            $grant = new VideoGrant($grantProperties);

            // Set the video grant
            $at->setGrant($grant);

            // Generate token
            $token = $at->toJwt();

            return response()->json([
                'token' => $token,
                'room_name' => $roomName,
                'participant_name' => $participantName
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate token: ' . $e->getMessage()
            ], 500);
        }
    }
}
