<?php

namespace App\Services\Meeting;

use App\Models\BaseSession;
use Illuminate\Support\Facades\Log;

/**
 * Aggregates a session's client-side telemetry into a compact rollup on the
 * session row. The source is the daily `meeting-telemetry-YYYY-MM-DD.log` file
 * that `public/js/livekit/telemetry.js` writes to. Runs after the LiveKit
 * `room_finished` webhook so the session has reached a terminal state.
 *
 * Stored shape (JSON on `telemetry_summary`):
 *   {
 *     "reconnects":          int,   // room_created events
 *     "rnnoise_failures":    int,   // audio.rnnoise_track_ended_unexpectedly
 *     "token_fetch_failures":int,
 *     "samples":             int,   // stats.audio_sender sample count
 *     "max_rtt_ms":          int,
 *     "p50_rtt_ms":          int,
 *     "p50_erl_db":          int,   // negative = speaker-to-mic coupling
 *     "p50_erle_db":         int,   // AEC effectiveness, higher = better
 *     "loss_bursts":         int,   // samples with fraction_lost > 0.05
 *     "generated_at":        ISO8601,
 *   }
 *
 * The rollup is read by the super-admin Filament widget to surface sessions
 * with broken AEC (speaker-echo) or unstable reconnection behaviour. End users
 * never see these numbers.
 *
 * Interpreting the rollup values:
 *   p50_erl_db  > 20  healthy
 *   p50_erl_db  < 5   teacher probably on device speakers — echo leaks back
 *                     into the mic and no AEC tuning can catch up.
 *   p50_erl_db  < 0   mic is hearing speakers *louder* than the source.
 *   p50_erle_db > 10  AEC working well.
 *   p50_erle_db < 1   AEC effectively off — usually pairs with a low ERL.
 *
 * rnnoise_failures > 0 means the experimental WASM noise processor dropped
 * for that session — each event is an audible glitch. The default shipping
 * pipeline is native WebRTC NS (see connection.js); RNNoise is opt-in only.
 */
class AudioQualitySummaryService
{
    private const LOSS_BURST_THRESHOLD = 0.05;

    /**
     * Scan today's (and if needed yesterday's) telemetry log for the given
     * session's events, aggregate, and persist to the session row.
     */
    public function summarize(BaseSession $session): void
    {
        $summary = $this->aggregateFromLogs($session);
        if ($summary === null) {
            return;
        }

        $session->forceFill(['telemetry_summary' => $summary])->saveQuietly();
    }

    /**
     * @return array<string,mixed>|null null if no telemetry was found for the session
     */
    private function aggregateFromLogs(BaseSession $session): ?array
    {
        $marker = 'sess='.$session->id.' ';
        $files = $this->candidateLogFiles();

        $reconnects = 0;
        $rnnoiseFailures = 0;
        $tokenFailures = 0;
        $lossBursts = 0;
        $rtts = [];
        $erls = [];
        $erles = [];
        $samples = 0;

        foreach ($files as $file) {
            if (! is_readable($file)) {
                continue;
            }

            $fh = fopen($file, 'r');
            if ($fh === false) {
                continue;
            }

            try {
                while (($line = fgets($fh)) !== false) {
                    if (! str_contains($line, $marker)) {
                        continue;
                    }

                    if (str_contains($line, ' connection.room_created ')) {
                        $reconnects++;

                        continue;
                    }
                    if (str_contains($line, ' audio.rnnoise_track_ended_unexpectedly ')) {
                        $rnnoiseFailures++;

                        continue;
                    }
                    if (str_contains($line, ' connection.token_fetch_failed ')) {
                        $tokenFailures++;

                        continue;
                    }
                    if (! str_contains($line, ' stats.audio_sender ')) {
                        continue;
                    }

                    $payload = $this->extractJson($line);
                    if ($payload === null) {
                        continue;
                    }

                    $samples++;

                    if (isset($payload['rtt']) && is_numeric($payload['rtt'])) {
                        $rtts[] = (float) $payload['rtt'] * 1000.0;
                    }
                    if (isset($payload['echo_return_loss_db']) && is_numeric($payload['echo_return_loss_db'])) {
                        $erls[] = (float) $payload['echo_return_loss_db'];
                    }
                    if (isset($payload['echo_return_loss_enhancement_db']) && is_numeric($payload['echo_return_loss_enhancement_db'])) {
                        $erles[] = (float) $payload['echo_return_loss_enhancement_db'];
                    }
                    if (isset($payload['fraction_lost']) && is_numeric($payload['fraction_lost'])
                        && $payload['fraction_lost'] > self::LOSS_BURST_THRESHOLD) {
                        $lossBursts++;
                    }
                }
            } finally {
                fclose($fh);
            }
        }

        if ($samples === 0 && $reconnects === 0 && $rnnoiseFailures === 0) {
            return null;
        }

        return [
            'reconnects' => $reconnects,
            'rnnoise_failures' => $rnnoiseFailures,
            'token_fetch_failures' => $tokenFailures,
            'samples' => $samples,
            'max_rtt_ms' => $rtts === [] ? null : (int) round(max($rtts)),
            'p50_rtt_ms' => $this->p50Int($rtts),
            'p50_erl_db' => $this->p50Int($erls),
            'p50_erle_db' => $this->p50Int($erles),
            'loss_bursts' => $lossBursts,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Today plus yesterday (sessions straddling midnight), newest first.
     *
     * @return list<string>
     */
    private function candidateLogFiles(): array
    {
        $logDir = storage_path('logs');
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        return [
            $logDir.'/meeting-telemetry-'.$today.'.log',
            $logDir.'/meeting-telemetry-'.$yesterday.'.log',
        ];
    }

    /**
     * Extract the trailing JSON object from a Monolog line. The telemetry
     * writer always appends a single-line JSON payload, so we locate the
     * first '{' after the event name and decode from there.
     */
    private function extractJson(string $line): ?array
    {
        $start = strpos($line, '{');
        if ($start === false) {
            return null;
        }

        $end = strrpos($line, '}');
        if ($end === false || $end <= $start) {
            return null;
        }

        $json = substr($line, $start, $end - $start + 1);
        try {
            $decoded = json_decode($json, true, 8, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException $e) {
            Log::debug('AudioQualitySummaryService: malformed telemetry JSON', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * @param  list<float>  $values
     */
    private function p50Int(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);

        return (int) round($values[intdiv(count($values), 2)]);
    }
}
