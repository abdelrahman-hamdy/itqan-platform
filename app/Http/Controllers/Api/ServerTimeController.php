<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AcademyContextService;
use Illuminate\Http\JsonResponse;

/**
 * Server Time Controller
 *
 * Provides server time synchronization endpoints for session timers
 * and other time-sensitive client operations.
 */
class ServerTimeController extends Controller
{
    /**
     * Get current server time.
     *
     * Used by session pages to synchronize client timers with server time.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'timestamp' => now()->toISOString(),
                'unix_timestamp' => now()->getTimestamp(),
                'timezone' => AcademyContextService::getTimezone(),
            ],
        ]);
    }
}
