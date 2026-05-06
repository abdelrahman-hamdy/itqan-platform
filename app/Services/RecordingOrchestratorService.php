<?php

namespace App\Services;

use App\Contracts\RecordingCapable;
use App\Enums\RecordingStatus;
use App\Enums\SessionStatus;
use App\Jobs\ProcessRecordingQueueJob;
use App\Models\AcademicSession;
use App\Models\BaseSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use App\Models\SessionRecording;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * RecordingOrchestratorService
 *
 * Manages automated session recording with capacity awareness.
 * Decides which sessions get recorded based on a configurable max concurrent limit
 * and a FIFO queue for sessions waiting for capacity.
 *
 * Auto-managed types (quran_individual, quran_group, academic_lesson, trial):
 *   - Recording starts automatically when session goes live
 *   - Queued when at capacity, promoted when a slot frees up
 *
 * Manual types (interactive_course):
 *   - Teacher controls recording manually
 *   - Counts toward capacity limit but NOT managed by this orchestrator
 */
class RecordingOrchestratorService
{
    private const LOCK_KEY = 'recording_orchestrator_lock';

    private const LOCK_TTL = 10; // seconds

    private const EARLY_JOIN_GRACE_MINUTES = 30;

    /**
     * Cache key for the "active recording" short-circuit. Held by this service —
     * use this helper from any caller that needs to read/clear the key (e.g. when
     * marking a recording FAILED) so the format stays in one place.
     */
    public static function activeRecordingCacheKey(string $morphClass, int|string $id): string
    {
        return "recording_active:{$morphClass}:{$id}";
    }

    public function __construct(
        private RecordingService $recordingService,
        private LiveKitService $liveKitService,
    ) {}

    /**
     * Handle a session going live (called from webhook on participant_joined).
     * Decides whether to start recording or queue the session.
     */
    public function handleSessionLive(BaseSession $session, string $roomName): void
    {
        if (! ($session instanceof RecordingCapable)) {
            return;
        }

        // Only auto-manage configured session types
        if (! $this->isAutoManagedType($session)) {
            return;
        }

        // Fast cache check — avoid DB/lock overhead on repeated participant_joined events
        $cacheKey = self::activeRecordingCacheKey($session->getMorphClass(), $session->id);
        if (Cache::has($cacheKey)) {
            return;
        }

        // Grace window — `participant_joined` is the only signal we get, so a single
        // early arrival that stays the whole session must still trigger recording.
        $session->refresh();
        if ($session->scheduled_at && now()->lt($session->scheduled_at->copy()->subMinutes(self::EARLY_JOIN_GRACE_MINUTES))) {
            return;
        }

        if (! $session->isRecordingEnabled()) {
            return;
        }

        // Check if already recording or queued
        $exists = SessionRecording::query()
            ->where('recordable_type', $session->getMorphClass())
            ->where('recordable_id', $session->id)
            ->whereIn('status', [RecordingStatus::RECORDING->value, RecordingStatus::QUEUED->value])
            ->exists();

        if ($exists) {
            Cache::put($cacheKey, true, 300);

            return;
        }

        // Acquire lock to prevent race conditions
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        try {
            $lock->block(5); // Wait up to 5 seconds for lock

            $activeCount = $this->getActiveRecordingCount();
            $maxConcurrent = $this->getMaxConcurrentRecordings();

            if ($activeCount < $maxConcurrent) {
                $this->startAutoRecording($session);
            } else {
                $this->queueRecording($session, $roomName);
            }
        } catch (Exception $e) {
            // If lock fails, queue as safe default
            Log::warning('Orchestrator: Lock failed, queuing session as fallback', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
            $this->queueRecording($session, $roomName);
        } finally {
            $lock?->release();
        }
    }

    /**
     * Process the recording queue — promote oldest queued sessions when capacity is available.
     * Called after an egress ends (slot freed) or by the safety-net scheduled command.
     */
    public function processQueue(): void
    {
        // Collect candidates inside lock, then release before making HTTP calls
        $candidates = [];
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TTL);

        try {
            $lock->block(5);

            $activeCount = $this->getActiveRecordingCount();
            $maxConcurrent = $this->getMaxConcurrentRecordings();
            $slotsAvailable = $maxConcurrent - $activeCount;

            if ($slotsAvailable <= 0) {
                return;
            }

            $queuedRecordings = SessionRecording::query()
                ->oldestQueued()
                ->with('recordable')
                ->limit($slotsAvailable + 5) // extra buffer for skipped ones
                ->get();

            foreach ($queuedRecordings as $queued) {
                if (count($candidates) >= $slotsAvailable) {
                    break;
                }

                $session = $queued->recordable;

                if (! $session || ! ($session instanceof RecordingCapable)) {
                    $queued->markAsSkipped('session_unavailable');

                    continue;
                }

                if (! $session->meeting_room_name) {
                    $queued->markAsSkipped('session_ended');

                    continue;
                }

                $candidates[] = $queued;
            }
        } catch (Exception $e) {
            Log::warning('Orchestrator: processQueue lock failed', [
                'error' => $e->getMessage(),
            ]);

            return;
        } finally {
            $lock?->release();
        }

        // Now promote candidates outside the lock (HTTP calls to LiveKit)
        foreach ($candidates as $queued) {
            $session = $queued->recordable;

            try {
                $config = $session->getRecordingConfiguration();
                $egressResponse = $this->liveKitService->startRecording(
                    $config['room_name'],
                    $config
                );

                $queued->update([
                    'status' => RecordingStatus::RECORDING,
                    'recording_id' => $egressResponse['egress_id'],
                    'meeting_room' => $config['room_name'],
                    'started_at' => now(),
                    'file_format' => ($config['audio_only'] ?? false) ? 'm4a' : 'mp4',
                ]);

                Log::info('Orchestrator: Promoted queued recording', [
                    'recording_id' => $queued->id,
                    'session_id' => $session->id,
                    'egress_id' => $egressResponse['egress_id'],
                ]);
            } catch (Exception $e) {
                $queued->markAsFailed($e->getMessage());
                Log::error('Orchestrator: Failed to promote queued recording', [
                    'recording_id' => $queued->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Safety net for sessions that went live but never got a SessionRecording row.
     *
     * Common cause: a single early-arriving participant joins before scheduled_at,
     * gets dropped by handleSessionLive's grace-window check (or any other early
     * return), and then no further `participant_joined` webhook fires to retry.
     * Also catches missed webhooks, observer-only joins, and post-RoomStartedHandler
     * "ghost ongoing" sessions.
     *
     * Scheduled by ProcessRecordingQueueCommand alongside processStaleQueue().
     */
    public function retryMissedRecordings(): int
    {
        $retried = 0;
        $models = [QuranSession::class, AcademicSession::class];

        foreach ($models as $cls) {
            $cls::query()
                ->where('status', SessionStatus::ONGOING)
                ->whereNotNull('meeting_room_name')
                ->whereDoesntHave('recordings', function ($q) {
                    // Already has an active or already-produced recording — skip
                    $q->whereIn('status', [
                        RecordingStatus::RECORDING,
                        RecordingStatus::QUEUED,
                        RecordingStatus::PROCESSING,
                        RecordingStatus::COMPLETED,
                    ]);
                })
                ->chunkById(50, function ($sessions) use (&$retried) {
                    foreach ($sessions as $session) {
                        try {
                            $this->handleSessionLive($session, $session->meeting_room_name);
                            $retried++;
                        } catch (Exception $e) {
                            Log::warning('Orchestrator: retryMissedRecordings failed for session', [
                                'session_id' => $session->id,
                                'session_type' => $session->getMorphClass(),
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                });
        }

        if ($retried > 0) {
            Log::info('Orchestrator: retryMissedRecordings retried sessions', ['count' => $retried]);
        }

        return $retried;
    }

    /**
     * Handle a session ending — mark any queued recordings as skipped.
     */
    public function handleSessionEnded(BaseSession $session): void
    {
        $count = SessionRecording::query()
            ->where('recordable_type', $session->getMorphClass())
            ->where('recordable_id', $session->id)
            ->where('status', RecordingStatus::QUEUED->value)
            ->update([
                'status' => RecordingStatus::SKIPPED->value,
                'skipped_reason' => 'session_ended',
            ]);

        if ($count > 0) {
            Log::info('Orchestrator: Marked queued recordings as skipped (session ended)', [
                'session_id' => $session->id,
                'count' => $count,
            ]);
        }

        Cache::forget(self::activeRecordingCacheKey($session->getMorphClass(), $session->id));

        ProcessRecordingQueueJob::dispatch();
    }

    /**
     * Process stale queue entries — safety net scheduled every minute.
     */
    public function processStaleQueue(): void
    {
        $timeoutMinutes = config('livekit.recordings.queue_timeout_minutes', 60);

        $count = SessionRecording::query()
            ->queued()
            ->where('queued_at', '<', now()->subMinutes($timeoutMinutes))
            ->update([
                'status' => RecordingStatus::SKIPPED->value,
                'skipped_reason' => 'queue_timeout',
            ]);

        if ($count > 0) {
            Log::info('Orchestrator: Skipped stale queued recordings', [
                'count' => $count,
                'timeout_minutes' => $timeoutMinutes,
            ]);
        }

        $this->processQueue();
    }

    /**
     * Get current capacity status for the dashboard.
     */
    public function getCapacityStatus(): array
    {
        $activeCount = $this->getActiveRecordingCount();
        $maxConcurrent = $this->getMaxConcurrentRecordings();
        $queuedCount = SessionRecording::query()->queued()->count();

        return [
            'active_count' => $activeCount,
            'max_count' => $maxConcurrent,
            'queued_count' => $queuedCount,
            'utilization_percentage' => $maxConcurrent > 0
                ? round(($activeCount / $maxConcurrent) * 100)
                : 0,
            'server_status' => $this->getServerStatus($activeCount, $maxConcurrent),
        ];
    }

    /**
     * Start auto-recording for a session — delegates to RecordingService to avoid duplication.
     */
    private function startAutoRecording(RecordingCapable $session): void
    {
        try {
            $recording = $this->recordingService->startRecording($session);
            $recording->update(['auto_managed' => true]);

            Cache::put(self::activeRecordingCacheKey($session->getMorphClass(), $session->id), true, 300);

            Log::info('Orchestrator: Auto-recording started', [
                'session_id' => $session->id,
                'recording_id' => $recording->id,
            ]);
        } catch (Exception $e) {
            Log::error('Orchestrator: Failed to start auto-recording', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue a session for recording when capacity is full.
     */
    private function queueRecording(RecordingCapable $session, string $roomName): void
    {
        SessionRecording::create([
            'recordable_type' => $session->getMorphClass(),
            'recordable_id' => $session->id,
            'meeting_room' => $roomName,
            'status' => RecordingStatus::QUEUED->value,
            'queued_at' => now(),
            'metadata' => $session->getRecordingMetadata(),
            'auto_managed' => true,
        ]);

        Log::info('Orchestrator: Session queued for recording (at capacity)', [
            'session_id' => $session->id,
            'room_name' => $roomName,
        ]);
    }

    /**
     * Count all active recordings (both auto-managed and manual).
     */
    private function getActiveRecordingCount(): int
    {
        return SessionRecording::query()
            ->where('status', RecordingStatus::RECORDING->value)
            ->count();
    }

    /**
     * Get the configured maximum concurrent recordings.
     */
    private function getMaxConcurrentRecordings(): int
    {
        return (int) config('livekit.recordings.max_concurrent_recordings', 15);
    }

    /**
     * Check if a session type is auto-managed by the orchestrator.
     */
    private function isAutoManagedType(BaseSession $session): bool
    {
        // Interactive courses are manually managed
        if ($session instanceof InteractiveCourseSession) {
            return false;
        }

        $sessionType = $this->resolveRecordingType($session);
        $autoManagedTypes = config('livekit.recordings.auto_managed_types', []);

        return in_array($sessionType, $autoManagedTypes);
    }

    /**
     * Resolve the recording type string for a session.
     */
    public function resolveRecordingType(BaseSession $session): string
    {
        if ($session instanceof InteractiveCourseSession) {
            return 'interactive_course';
        }

        if ($session instanceof AcademicSession) {
            return 'academic_lesson';
        }

        if ($session instanceof QuranSession) {
            if ($session->session_type === 'trial') {
                return 'trial';
            }

            return in_array($session->session_type, ['group', 'circle'])
                ? 'quran_group'
                : 'quran_individual';
        }

        return 'unknown';
    }

    /**
     * Determine server status based on utilization.
     */
    private function getServerStatus(int $activeCount, int $maxConcurrent): string
    {
        if ($maxConcurrent === 0) {
            return 'error';
        }

        $utilization = $activeCount / $maxConcurrent;

        if ($utilization >= 1) {
            return 'at_capacity';
        }

        return 'healthy';
    }
}
