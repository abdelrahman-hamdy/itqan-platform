<?php

namespace App\Console\Commands\Backfill;

use App\Enums\SessionSubscriptionStatus;
use App\Models\AcademicSubscription;
use App\Models\QuranSubscription;
use Illuminate\Support\Facades\DB;

/**
 * Bug #9 audit — find (student, teacher, package) tuples where a CANCELLED
 * sub and a newer ACTIVE/PENDING sub exist within 60 minutes of each other.
 * That's the gateway-retry ghost-pair signature.
 *
 * Read-only. Do NOT auto-merge — money or scheduled sessions may be tied to
 * one of the two rows. Operator decides per-pair.
 *
 *   php artisan subscriptions:audit-ghost-pairs
 */
class AuditGhostSubscriptionPairsCommand extends BaseAuditCommand
{
    protected $signature = 'subscriptions:audit-ghost-pairs
                            {--window-minutes=60 : Pair window in minutes between cancel and successor}
                            {--out= : Optional CSV path}';

    protected $description = 'Bug #9: list (student, teacher, package) ghost-pair candidates';

    public function handle(): int
    {
        $window = (int) $this->option('window-minutes');
        $rows = collect();

        $this->auditModel(QuranSubscription::class, ['quran_teacher_id', 'package_id'], $window, $rows);
        $this->auditModel(AcademicSubscription::class, ['teacher_id', 'academic_package_id'], $window, $rows);

        $this->info(sprintf('Found %d ghost-pair candidate(s).', $rows->count()));

        if ($rows->isEmpty()) {
            return self::SUCCESS;
        }

        $path = $this->writeCsv(
            'bug9',
            [
                'model', 'student_id', 'teacher_id', 'package_id',
                'cancelled_id', 'cancelled_at', 'successor_id', 'successor_status', 'successor_created_at',
            ],
            $rows,
        );

        $this->info("CSV written to: $path");

        return self::SUCCESS;
    }

    /**
     * Find every (cancelled, successor) pair on the model's table in a single
     * self-join query, instead of N+1 lookups per cancelled row.
     */
    private function auditModel(string $modelClass, array $keyFields, int $window, $bucket): void
    {
        [$teacherCol, $packageCol] = $keyFields;
        $table = (new $modelClass)->getTable();

        $cancelledValue = SessionSubscriptionStatus::CANCELLED->value;
        $activeValue = SessionSubscriptionStatus::ACTIVE->value;
        $pendingValue = SessionSubscriptionStatus::PENDING->value;

        $pairs = DB::table("$table as cancelled")
            ->join("$table as successor", function ($join) use ($teacherCol, $window) {
                $join->on('cancelled.academy_id', '=', 'successor.academy_id')
                    ->on('cancelled.student_id', '=', 'successor.student_id')
                    ->on("cancelled.$teacherCol", '=', "successor.$teacherCol")
                    ->whereColumn('cancelled.id', '!=', 'successor.id')
                    ->whereRaw(
                        'successor.created_at >= DATE_SUB(cancelled.cancelled_at, INTERVAL ? MINUTE)',
                        [$window]
                    )
                    ->whereRaw(
                        'successor.created_at <= DATE_ADD(cancelled.cancelled_at, INTERVAL ? MINUTE)',
                        [$window]
                    );
            })
            ->where('cancelled.status', $cancelledValue)
            ->whereNotNull('cancelled.cancelled_at')
            ->where('cancelled.cancelled_at', '>=', now()->subDays(180))
            ->whereIn('successor.status', [$activeValue, $pendingValue])
            ->select([
                'cancelled.id as cancelled_id',
                'cancelled.student_id',
                "cancelled.$teacherCol as teacher_col",
                "cancelled.$packageCol as package_col",
                'cancelled.cancelled_at',
                'successor.id as successor_id',
                'successor.status as successor_status',
                'successor.created_at as successor_created_at',
            ])
            ->get();

        foreach ($pairs as $pair) {
            $bucket->push([
                class_basename($modelClass),
                $pair->student_id,
                $pair->teacher_col,
                $pair->package_col,
                $pair->cancelled_id,
                $pair->cancelled_at,
                $pair->successor_id,
                $pair->successor_status,
                $pair->successor_created_at,
            ]);
        }
    }
}
