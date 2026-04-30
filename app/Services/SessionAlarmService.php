<?php

namespace App\Services;

use App\Enums\PushPayloadType;
use App\Models\SessionAlarm;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Dispatches "alarm the other participant" requests across Quran, Academic,
 * and Interactive sessions.
 *
 * Responsibilities:
 *  - Resolve the session from a (type, id) pair and verify both caller and
 *    target are legitimate participants.
 *  - Record every attempt in the `session_alarms` table for audit.
 *  - Dispatch a high-priority FCM payload via [FcmService]. The payload
 *    carries a shared call_id so the mobile CallKit UI can dismiss other
 *    devices when one answers.
 */
class SessionAlarmService
{
    public function __construct(
        private readonly FcmService $fcm,
        private readonly SessionSettingsService $sessionSettings,
    ) {}

    /**
     * Send an alarm from [$caller] to [$targetUserId] for a session.
     *
     * @return array{status: 'sent'|'forbidden', call_id?: string, message?: string}
     */
    public function alarm(
        User $caller,
        string $sessionType,
        string $sessionId,
        int $targetUserId,
    ): array {
        $session = $this->sessionSettings->resolveSessionByType($sessionType, $sessionId);
        if ($session === null) {
            return ['status' => 'forbidden', 'message' => 'session_not_found'];
        }

        if ($targetUserId === $caller->id) {
            return ['status' => 'forbidden', 'message' => 'cannot_alarm_self'];
        }

        // Use the canonical participant check defined on each session model
        // (BaseSession::isUserParticipant). It correctly handles the
        // academic_teacher_id-vs-user_id pivot and the interactive course
        // teacher-on-course path that the previous hand-rolled check
        // mishandled. Collapsing missing-user into the same response as
        // non-participant avoids leaking which user IDs exist to callers.
        $target = User::query()->find($targetUserId);
        if ($target === null ||
            ! $session->isUserParticipant($caller) ||
            ! $session->isUserParticipant($target)) {
            return ['status' => 'forbidden', 'message' => 'not_a_participant'];
        }

        $callId = (string) Str::uuid();

        $alarm = SessionAlarm::create([
            'academy_id' => $caller->academy_id,
            'call_id' => $callId,
            'session_type' => $sessionType,
            'session_id' => $session->getKey(),
            'caller_id' => $caller->id,
            'target_id' => $targetUserId,
        ]);

        $callerRole = $this->getUserRole($caller);

        $payload = [
            'type' => PushPayloadType::SessionAlarm->value,
            'call_id' => $callId,
            'session_type' => $sessionType,
            'session_id' => (string) $session->getKey(),
            'caller_id' => (string) $caller->id,
            'caller_name' => $caller->name,
            'caller_role' => $callerRole,
            'alarm_id' => (string) $alarm->id,
        ];

        try {
            $this->fcm->sendAlarmToUser($target, $payload);
        } catch (\Throwable $e) {
            Log::error('SessionAlarmService: FCM dispatch failed', [
                'call_id' => $callId,
                'target_id' => $targetUserId,
                'error' => $e->getMessage(),
            ]);
        }

        return ['status' => 'sent', 'call_id' => $callId];
    }

    public function markAnswered(string $callId, User $user): ?SessionAlarm
    {
        return $this->markAndNotifyCaller(
            callId: $callId,
            user: $user,
            column: 'answered_at',
            pushType: PushPayloadType::SessionAlarmAnswered,
            titleKey: 'meetings.alarm.answered_title',
            bodyKey: 'meetings.alarm.answered_body',
        );
    }

    public function markDeclined(string $callId, User $user): ?SessionAlarm
    {
        return $this->markAndNotifyCaller(
            callId: $callId,
            user: $user,
            column: 'declined_at',
            pushType: PushPayloadType::SessionAlarmDeclined,
            titleKey: 'meetings.alarm.declined_title',
            bodyKey: 'meetings.alarm.declined_body',
        );
    }

    /**
     * Caller cancels the ring before the target picks up. Pushes
     * [PushPayloadType::SessionAlarmCancelled] to the *target* so the
     * incoming-call UI can dismiss itself; this is the symmetric counterpart
     * of [markAnswered]/[markDeclined], which notify the caller.
     */
    public function markCancelled(string $callId, User $user): ?SessionAlarm
    {
        $alarm = SessionAlarm::query()
            ->with('target')
            ->where('call_id', $callId)
            ->first();
        if ($alarm === null || $alarm->caller_id !== $user->id) {
            return null;
        }
        $alarm->cancelled_at ??= now();
        $alarm->save();

        $target = $alarm->target;
        if ($target !== null) {
            try {
                $this->fcm->sendAlarmToUser($target, [
                    'type' => PushPayloadType::SessionAlarmCancelled->value,
                    'call_id' => $callId,
                    'session_type' => $alarm->session_type,
                    'session_id' => (string) $alarm->session_id,
                ]);
            } catch (\Throwable $e) {
                Log::warning('SessionAlarmService: cancel notify failed', [
                    'call_id' => $callId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $alarm;
    }

    private function markAndNotifyCaller(
        string $callId,
        User $user,
        string $column,
        PushPayloadType $pushType,
        string $titleKey,
        string $bodyKey,
    ): ?SessionAlarm {
        $alarm = SessionAlarm::query()
            ->with('caller')
            ->where('call_id', $callId)
            ->first();
        if ($alarm === null || $alarm->target_id !== $user->id) {
            return null;
        }
        $alarm->{$column} ??= now();
        $alarm->save();

        $caller = $alarm->caller;
        if ($caller !== null) {
            try {
                $this->fcm->sendToUser(
                    $caller,
                    __($titleKey),
                    __($bodyKey),
                    [
                        'type' => $pushType->value,
                        'call_id' => $callId,
                    ],
                );
            } catch (\Throwable $e) {
                Log::warning('SessionAlarmService: caller notify failed', [
                    'call_id' => $callId,
                    'type' => $pushType->value,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $alarm;
    }

    private function getUserRole(User $user): ?string
    {
        return $user->user_type ?? null;
    }
}
