<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    private ?Messaging $messaging = null;
    private bool $messagingResolutionAttempted = false;

    /**
     * Resolve Messaging lazily: a missing/broken
     * `storage/app/firebase-credentials.json` would make Laravel's container
     * blow up on every request that touches FcmService (route resolution,
     * controllers, jobs). Deferring lets the rest of the app keep working
     * while push delivery degrades to a no-op.
     */
    private function messaging(): ?Messaging
    {
        if ($this->messagingResolutionAttempted) {
            return $this->messaging;
        }
        $this->messagingResolutionAttempted = true;

        try {
            $this->messaging = app(Messaging::class);
        } catch (\Throwable $e) {
            Log::warning('FcmService: Firebase Messaging unavailable — push notifications will no-op', [
                'error' => $e->getMessage(),
            ]);
            $this->messaging = null;
        }

        return $this->messaging;
    }

    /**
     * Send a push notification to all devices of a user.
     *
     * @return array{sent: int, failed: int, invalidated: int}
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        $tokens = $user->deviceTokens()->pluck('token')->all();

        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'invalidated' => 0];
        }

        $messaging = $this->messaging();
        if ($messaging === null) {
            return ['sent' => 0, 'failed' => count($tokens), 'invalidated' => 0];
        }

        $notification = Notification::create($title, $body);
        $sanitizedData = $this->sanitizeData($data);

        $message = CloudMessage::new()
            ->withNotification($notification)
            ->withData($sanitizedData);

        $totalSent = 0;
        $totalFailed = 0;
        $totalInvalidated = 0;

        // FCM allows max 500 tokens per multicast
        foreach (array_chunk($tokens, 500) as $chunk) {
            try {
                $report = $messaging->sendMulticast($message, $chunk);

                $totalSent += $report->successes()->count();
                $totalFailed += $report->failures()->count();

                $totalInvalidated += $this->removeInvalidTokens($report, $chunk);
            } catch (\Throwable $e) {
                Log::error('FcmService: multicast send failed', [
                    'user_id' => $user->id,
                    'token_count' => count($chunk),
                    'error' => $e->getMessage(),
                ]);
                $totalFailed += count($chunk);
            }
        }

        return [
            'sent' => $totalSent,
            'failed' => $totalFailed,
            'invalidated' => $totalInvalidated,
        ];
    }

    /**
     * Remove invalid/unregistered tokens from the database.
     */
    private function removeInvalidTokens(MulticastSendReport $report, array $tokens): int
    {
        $invalidTokens = [];

        foreach ($report->failures()->getItems() as $failure) {
            $target = $failure->target();
            $error = $failure->error();

            if ($target && $error) {
                $errorCode = match (true) {
                    $error instanceof \Kreait\Firebase\Exception\Messaging\NotFound => 'NOT_FOUND',
                    $error instanceof \Kreait\Firebase\Exception\Messaging\InvalidArgument => 'INVALID_ARGUMENT',
                    default => '',
                };
                $token = $target->value();

                if (in_array($errorCode, ['NOT_FOUND', 'UNREGISTERED', 'INVALID_ARGUMENT'])) {
                    $invalidTokens[] = $token;
                }
            }
        }

        if (! empty($invalidTokens)) {
            DeviceToken::whereIn('token', $invalidTokens)->delete();

            Log::info('FcmService: removed invalid tokens', [
                'count' => count($invalidTokens),
            ]);
        }

        return count($invalidTokens);
    }

    /**
     * FCM data values must all be strings.
     */
    private function sanitizeData(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = json_encode($value);
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $sanitized[$key] = '';
            } else {
                $sanitized[$key] = (string) $value;
            }
        }

        return $sanitized;
    }
}
