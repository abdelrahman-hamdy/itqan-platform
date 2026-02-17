<?php

namespace App\Jobs\Traits;

use Exception;
use App\Models\Academy;
use Illuminate\Support\Facades\Log;

/**
 * Trait TenantAwareJob
 *
 * Provides multi-tenancy support for background jobs.
 * Use this trait to ensure jobs process data on a per-academy basis.
 */
trait TenantAwareJob
{
    /**
     * The current academy ID being processed.
     */
    protected ?int $academyId = null;

    /**
     * Get the current academy ID for query filtering.
     */
    protected function getAcademyIdForQuery(): ?int
    {
        return $this->academyId;
    }

    /**
     * Set the academy context for this job.
     */
    public function setAcademyContext(?int $academyId): self
    {
        $this->academyId = $academyId;

        return $this;
    }

    /**
     * Process a callback for each active academy.
     *
     * @param  callable  $processor  Callback receives Academy instance, returns result
     * @return array Results keyed by academy ID
     */
    protected function processForEachAcademy(callable $processor): array
    {
        $results = [];
        $academies = Academy::where('is_active', true)->get();

        foreach ($academies as $academy) {
            $this->academyId = $academy->id;

            Log::info("[TenantAwareJob] Processing academy: {$academy->name} (ID: {$academy->id})");

            try {
                $results[$academy->id] = $processor($academy);
            } catch (Exception $e) {
                Log::error("[TenantAwareJob] Error processing academy {$academy->id}: {$e->getMessage()}");
                $results[$academy->id] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Process a callback for a specific academy only.
     *
     * @param  int  $academyId  The academy to process
     * @param  callable  $processor  Callback receives Academy instance
     * @return mixed Result from processor
     */
    protected function processForAcademy(int $academyId, callable $processor): mixed
    {
        $academy = Academy::find($academyId);

        if (! $academy) {
            Log::warning("[TenantAwareJob] Academy not found: {$academyId}");

            return null;
        }

        if (! $academy->is_active) {
            Log::warning("[TenantAwareJob] Academy is inactive: {$academyId}");

            return null;
        }

        $this->academyId = $academy->id;

        Log::info("[TenantAwareJob] Processing single academy: {$academy->name} (ID: {$academy->id})");

        return $processor($academy);
    }

    /**
     * Get a summary log message for job completion.
     */
    protected function getProcessingSummary(array $results): string
    {
        $total = count($results);
        $successful = count(array_filter($results, fn ($r) => ! isset($r['error'])));
        $failed = $total - $successful;

        return "[TenantAwareJob] Completed: {$total} academies processed, {$successful} successful, {$failed} failed";
    }
}
