<?php

namespace App\Services\Calendar;

use Carbon\CarbonInterface;
use Throwable;

final readonly class BatchScheduleResult
{
    /**
     * @param  list<array{date: string, reason: string}>  $failures
     */
    public function __construct(
        public int $created,
        public int $requested,
        public array $failures = [],
    ) {}

    public function isEmpty(): bool
    {
        return $this->created === 0;
    }

    public function isPartial(): bool
    {
        return $this->created > 0 && $this->created < $this->requested;
    }

    public function firstFailureReason(): ?string
    {
        return $this->failures[0]['reason'] ?? null;
    }

    /**
     * Translation key for the user-facing schedule outcome message.
     */
    public function messageKey(): string
    {
        $hasReason = $this->firstFailureReason() !== null;

        if ($this->isEmpty()) {
            return $hasReason
                ? 'calendar.schedule_no_sessions_created_with_reason'
                : 'calendar.schedule_no_sessions_created';
        }

        if ($this->isPartial()) {
            return $hasReason
                ? 'calendar.schedule_partial_with_reason'
                : 'calendar.schedule_partial';
        }

        return 'calendar.schedule_created_successfully';
    }

    /**
     * Translation params for {@see messageKey()}.
     *
     * @return array<string, int|string>
     */
    public function messageParams(): array
    {
        return array_filter(
            [
                'created' => $this->created,
                'requested' => $this->requested,
                'reason' => $this->firstFailureReason(),
            ],
            fn ($v) => $v !== null,
        );
    }

    /**
     * @return array{date: string, reason: string}
     */
    public static function failureEntry(CarbonInterface $date, Throwable $e): array
    {
        return [
            'date' => $date->format('Y-m-d H:i'),
            'reason' => $e->getMessage(),
        ];
    }
}
