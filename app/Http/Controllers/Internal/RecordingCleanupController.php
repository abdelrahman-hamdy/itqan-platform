<?php

namespace App\Http\Controllers\Internal;

use App\Enums\RecordingStatus;
use App\Http\Controllers\Controller;
use App\Models\SessionRecording;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Read-only endpoints used by the LiveKit VPS orphan-file reaper to learn
 * which recording files it should delete and which it should keep. The
 * reaper script lives on the LiveKit VPS at /opt/livekit/scripts/.
 *
 * Authenticated via the InternalApiAuth middleware (bearer token from
 * config('livekit.internal_token')).
 */
class RecordingCleanupController extends Controller
{
    private const PAGE_SIZE = 1000;

    private const FRESH_GRACE_HOURS = 24;

    /**
     * File paths whose DB row is in a terminal "should be gone" state and
     * whose row hasn't been touched in the last 24 hours. The reaper
     * deletes both the recording file and its `.json` egress sidecar.
     */
    public function toDelete(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));

        $rows = SessionRecording::query()
            ->whereIn('status', [
                RecordingStatus::DELETED->value,
                RecordingStatus::FAILED->value,
                RecordingStatus::SKIPPED->value,
            ])
            ->whereNotNull('file_path')
            ->where('updated_at', '<', now()->subHours(self::FRESH_GRACE_HOURS))
            ->orderBy('id')
            ->forPage($page, self::PAGE_SIZE)
            ->get(['id', 'status', 'file_path']);

        $files = $rows->map(fn ($r) => [
            'id' => $r->id,
            'status' => $r->status->value,
            'path' => $r->file_path,
            'sidecar' => $r->file_path.'.json',
        ])->all();

        return response()->json([
            'files' => $files,
            'page' => $page,
            'page_size' => self::PAGE_SIZE,
        ]);
    }

    /**
     * File paths whose DB row is currently active and must NOT be deleted.
     * Used by the manual one-shot inventory script to classify on-disk
     * files into keep / orphan buckets.
     */
    public function activePaths(Request $request): JsonResponse
    {
        $page = max(1, (int) $request->query('page', 1));

        $rows = SessionRecording::query()
            ->whereIn('status', [
                RecordingStatus::COMPLETED->value,
                RecordingStatus::RECORDING->value,
                RecordingStatus::PROCESSING->value,
                RecordingStatus::QUEUED->value,
            ])
            ->whereNotNull('file_path')
            ->orderBy('id')
            ->forPage($page, self::PAGE_SIZE)
            ->get(['id', 'status', 'file_path']);

        $paths = $rows->map(fn ($r) => [
            'id' => $r->id,
            'status' => $r->status->value,
            'path' => $r->file_path,
        ])->all();

        return response()->json([
            'paths' => $paths,
            'page' => $page,
            'page_size' => self::PAGE_SIZE,
        ]);
    }
}
