<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Receives batched LiveKit meeting telemetry events from the browser-side
 * telemetry.js and forwards them to the dedicated meeting-telemetry log
 * channel.
 *
 * Why: gives us objective measurements of echo (echoReturnLoss),
 * reconnect cycles, SDK load failures, and other meeting-side conditions
 * that user reports cannot describe precisely.
 */
class MeetingTelemetryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'events' => 'required|array|max:200',
            'events.*.t' => 'nullable|integer',
            'events.*.iso' => 'nullable|string|max:40',
            'events.*.session_id' => 'nullable|integer',
            'events.*.session_type' => 'nullable|string|max:50',
            'events.*.user_id' => 'nullable|integer',
            'events.*.user_role' => 'nullable|string|max:50',
            'events.*.category' => 'required|string|max:50',
            'events.*.name' => 'required|string|max:100',
            'events.*.level' => 'nullable|string|in:info,warning,error',
            'events.*.data' => 'nullable|array',
        ]);

        $logger = Log::channel('meeting-telemetry');
        $serverIp = $request->ip();
        $userAgent = substr((string) $request->userAgent(), 0, 240);
        // Authenticated user from session — overrides client-claimed user_id for trust
        $authUserId = $request->user()?->id;

        foreach ($data['events'] as $event) {
            $level = $event['level'] ?? 'info';
            // Laravel's PSR-3 logger uses 'warning' / 'info' / 'error' verbs
            $method = match ($level) {
                'error' => 'error',
                'warning' => 'warning',
                default => 'info',
            };

            $message = sprintf(
                '[MT] sess=%s user=%s/%s %s.%s',
                $event['session_id'] ?? '?',
                $authUserId ?? ($event['user_id'] ?? '?'),
                $event['user_role'] ?? '?',
                $event['category'],
                $event['name']
            );

            $context = $event['data'] ?? [];
            // Strip nested arrays bigger than ~4KB to keep log lines bounded
            $context['client_t'] = $event['t'] ?? null;
            $context['client_iso'] = $event['iso'] ?? null;
            $context['session_type'] = $event['session_type'] ?? null;
            $context['ip'] = $serverIp;
            $context['ua'] = $userAgent;
            if ($authUserId !== null && isset($event['user_id']) && (int) $event['user_id'] !== $authUserId) {
                $context['user_id_mismatch'] = true;
                $context['claimed_user_id'] = $event['user_id'];
            }

            $logger->{$method}($message, $context);
        }

        return response()->json([
            'ok' => true,
            'count' => count($data['events']),
        ]);
    }
}
