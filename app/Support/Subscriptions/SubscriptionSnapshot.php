<?php

namespace App\Support\Subscriptions;

use App\Models\AcademicSubscription;
use App\Models\BaseSubscription;
use App\Models\CourseSubscription;
use App\Models\MeetingAttendance;
use App\Models\Payment;
use App\Models\QuranSubscription;
use App\Models\SessionConsumption;
use App\Models\SubscriptionAuditLog;
use App\Models\SubscriptionCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pure snapshot helper for the Phase C audit log.
 *
 * `SubscriptionSnapshot::capture($sub)` returns a deterministic array of
 * the fields that matter when diffing before/after state across a writer.
 * The output is what gets stored in `subscription_audit_log.before_state`
 * / `after_state` — keep it small, stable, and easy to JSON-diff.
 *
 * Anything you add here is visible in every future audit row; don't dump
 * the whole model.
 */
final class SubscriptionSnapshot
{
    /**
     * Capture a snapshot of the fields the audit log diffs.
     *
     * Reads from in-memory attributes (does not refresh from DB) so callers
     * are responsible for $sub->refresh()-ing if they want post-write
     * truth — this is intentional, the trait pattern in
     * RecordsSubscriptionAudit handles the refresh.
     *
     * @return array{
     *     id: int|null,
     *     morph_class: string,
     *     status: string|null,
     *     payment_status: string|null,
     *     sessions_used: int|null,
     *     sessions_remaining: int|null,
     *     total_sessions: int|null,
     *     starts_at: string|null,
     *     ends_at: string|null,
     *     grace_period_ends_at: string|null,
     *     current_cycle_id: int|null,
     *     current_cycle_state: string|null,
     *     current_cycle_payment_status: string|null,
     * }
     */
    public static function capture(BaseSubscription $sub): array
    {
        $cycle = $sub->relationLoaded('currentCycle')
            ? $sub->getRelation('currentCycle')
            : $sub->currentCycle;

        return [
            'id' => $sub->getKey() !== null ? (int) $sub->getKey() : null,
            'morph_class' => $sub->getMorphClass(),
            'status' => self::stringify($sub->getAttribute('status')),
            'payment_status' => self::stringify($sub->getAttribute('payment_status')),
            'sessions_used' => self::toIntOrNull($sub->getAttribute('sessions_used')),
            'sessions_remaining' => self::toIntOrNull($sub->getAttribute('sessions_remaining')),
            'total_sessions' => self::toIntOrNull($sub->getAttribute('total_sessions')),
            'starts_at' => self::toIso8601OrNull($sub->getAttribute('starts_at')),
            'ends_at' => self::toIso8601OrNull($sub->getAttribute('ends_at')),
            'grace_period_ends_at' => self::toIso8601OrNull($sub->getAttribute('grace_period_ends_at')),
            'current_cycle_id' => $cycle instanceof SubscriptionCycle ? (int) $cycle->getKey() : null,
            'current_cycle_state' => $cycle instanceof SubscriptionCycle
                ? self::stringify($cycle->getAttribute('cycle_state'))
                : null,
            'current_cycle_payment_status' => $cycle instanceof SubscriptionCycle
                ? self::stringify($cycle->getAttribute('payment_status'))
                : null,
        ];
    }

    private static function toIntOrNull(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    /**
     * Coerce enum / Stringable / scalar into a plain string for JSON.
     */
    private static function stringify(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_object($value)) {
            // Native PHP enum
            if ($value instanceof \BackedEnum) {
                return (string) $value->value;
            }
            if ($value instanceof \UnitEnum) {
                return $value->name;
            }
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }

            return null;
        }

        return (string) $value;
    }

    private static function toIso8601OrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        // Carbon casts already return DateTimeInterface; this branch covers
        // raw string dates that slipped past the cast.
        return is_string($value) ? $value : null;
    }

    /**
     * Deep snapshot of every row that belongs to a subscription. Used by the
     * Phase 1 audit infrastructure (SubscriptionManifestService) and the
     * audit-one / audit-all artisan commands.
     *
     * This is intentionally separate from capture() — capture() is what
     * subscription_audit_log stores per-write (small, stable shape). The
     * manifest is read-only; it captures everything reachable from the
     * subscription so the auditor can reason about evidence without
     * additional queries.
     *
     * IMPORTANT: this MUST stay read-only. It refreshes nothing and writes
     * nothing.
     */
    public static function captureManifest(BaseSubscription $sub): array
    {
        $sub->loadMissing(['cycles', 'payments', 'currentCycle']);

        $morphClass = $sub->getMorphClass();
        $cycles = $sub->cycles()->orderBy('cycle_number')->get();
        $payments = $sub->payments()->orderBy('id')->get();

        $sessionRecords = self::fetchSessionRecords($sub);

        $consumptions = Schema::hasTable('session_consumption')
            ? SessionConsumption::query()
                ->where('subscription_type', $morphClass)
                ->where('subscription_id', $sub->getKey())
                ->orderBy('consumed_at')
                ->get()
            : collect();

        $auditLog = Schema::hasTable('subscription_audit_log')
            ? SubscriptionAuditLog::query()
                ->where('subscription_type', $morphClass)
                ->where('subscription_id', $sub->getKey())
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
            : collect();

        return [
            'subscription' => self::snapshotSubscriptionRow($sub),
            'core' => self::capture($sub),
            'cycles' => $cycles->map(fn (SubscriptionCycle $cycle) => self::snapshotCycle($cycle))->all(),
            'sessions' => array_values(array_map(
                fn (array $session) => self::snapshotSession($session),
                $sessionRecords,
            )),
            'payments' => $payments->map(fn (Payment $p) => self::snapshotPayment($p))->all(),
            'consumptions' => $consumptions->map(fn (SessionConsumption $c) => self::snapshotConsumption($c))->all(),
            'audit_log' => $auditLog->map(fn (SubscriptionAuditLog $a) => self::snapshotAuditLog($a))->all(),
            'counts' => [
                'cycles' => $cycles->count(),
                'sessions' => count($sessionRecords),
                'payments' => $payments->count(),
                'consumptions_active' => $consumptions->whereNull('reversed_at')->count(),
                'consumptions_reversed' => $consumptions->whereNotNull('reversed_at')->count(),
                'audit_log_entries' => $auditLog->count(),
                'audit_log_violations' => $auditLog->where('has_violations', true)->count(),
            ],
        ];
    }

    /**
     * Subscription-row dump (every column we care about for the audit).
     */
    private static function snapshotSubscriptionRow(BaseSubscription $sub): array
    {
        return [
            'id' => $sub->getKey() !== null ? (int) $sub->getKey() : null,
            'morph_class' => $sub->getMorphClass(),
            'academy_id' => self::toIntOrNull($sub->getAttribute('academy_id')),
            'student_id' => self::toIntOrNull($sub->getAttribute('student_id')),
            'teacher_id' => self::toIntOrNull($sub->getAttribute('teacher_id') ?? $sub->getAttribute('academic_teacher_id')),
            'package_id' => self::toIntOrNull($sub->getAttribute('package_id')),
            'status' => self::stringify($sub->getAttribute('status')),
            'payment_status' => self::stringify($sub->getAttribute('payment_status')),
            'currency' => self::stringify($sub->getAttribute('currency')),
            'sessions_used' => self::toIntOrNull($sub->getAttribute('sessions_used')),
            'sessions_remaining' => self::toIntOrNull($sub->getAttribute('sessions_remaining')),
            'total_sessions' => self::toIntOrNull($sub->getAttribute('total_sessions')),
            'total_price' => self::numericOrNull($sub->getAttribute('total_price')),
            'final_price' => self::numericOrNull($sub->getAttribute('final_price')),
            'starts_at' => self::toIso8601OrNull($sub->getAttribute('starts_at')),
            'ends_at' => self::toIso8601OrNull($sub->getAttribute('ends_at')),
            'grace_period_ends_at' => self::toIso8601OrNull($sub->getAttribute('grace_period_ends_at')),
            'paused_at' => self::toIso8601OrNull($sub->getAttribute('paused_at')),
            'cancelled_at' => self::toIso8601OrNull($sub->getAttribute('cancelled_at')),
            'created_at' => self::toIso8601OrNull($sub->getAttribute('created_at')),
            'updated_at' => self::toIso8601OrNull($sub->getAttribute('updated_at')),
        ];
    }

    private static function snapshotCycle(SubscriptionCycle $cycle): array
    {
        return [
            'id' => (int) $cycle->getKey(),
            'cycle_number' => self::toIntOrNull($cycle->getAttribute('cycle_number')),
            'cycle_state' => self::stringify($cycle->getAttribute('cycle_state')),
            'payment_status' => self::stringify($cycle->getAttribute('payment_status')),
            'pricing_source' => self::stringify($cycle->getAttribute('pricing_source')),
            'package_id' => self::toIntOrNull($cycle->getAttribute('package_id')),
            'currency' => self::stringify($cycle->getAttribute('currency')),
            'total_sessions' => self::toIntOrNull($cycle->getAttribute('total_sessions')),
            'sessions_used' => self::toIntOrNull($cycle->getAttribute('sessions_used')),
            'sessions_completed' => self::toIntOrNull($cycle->getAttribute('sessions_completed')),
            'sessions_missed' => self::toIntOrNull($cycle->getAttribute('sessions_missed')),
            'carryover_sessions' => self::toIntOrNull($cycle->getAttribute('carryover_sessions')),
            'total_price' => self::numericOrNull($cycle->getAttribute('total_price')),
            'final_price' => self::numericOrNull($cycle->getAttribute('final_price')),
            'starts_at' => self::toIso8601OrNull($cycle->getAttribute('starts_at')),
            'ends_at' => self::toIso8601OrNull($cycle->getAttribute('ends_at')),
            'grace_period_ends_at' => self::toIso8601OrNull($cycle->getAttribute('grace_period_ends_at')),
            'archived_at' => self::toIso8601OrNull($cycle->getAttribute('archived_at')),
            'v2_consumption_complete' => (bool) $cycle->getAttribute('v2_consumption_complete'),
        ];
    }

    /**
     * Fetch every session that belongs to this subscription. The session
     * tables are queried directly so the auditor can run without loading
     * type-specific eager-loaders that may be expensive.
     *
     * Course subscriptions do not have a direct FK on
     * interactive_course_sessions — those sessions belong to the course as
     * a whole and consumption is tracked per-attendee. We surface them by
     * scanning session_consumption rows that point at this subscription
     * (handled in the caller via the consumption manifest).
     *
     * @return list<array{table:string,row:array<string,mixed>}>
     */
    private static function fetchSessionRecords(BaseSubscription $sub): array
    {
        $tableMap = [
            QuranSubscription::class => ['quran_sessions', 'quran_subscription_id'],
            AcademicSubscription::class => ['academic_sessions', 'academic_subscription_id'],
        ];

        $class = $sub::class;
        if (! isset($tableMap[$class])) {
            return self::fetchSessionRecordsForCourse($sub);
        }

        [$table, $foreignKey] = $tableMap[$class];

        $columns = self::filterExistingColumns($table, [
            'id', 'status', 'scheduled_at', 'started_at', 'completed_at',
            'subscription_cycle_id', $foreignKey, 'student_id',
            'subscription_counted', 'subscription_counted_at',
            'created_at', 'updated_at',
        ]);

        $rows = DB::table($table)
            ->where($foreignKey, $sub->getKey())
            ->orderBy('scheduled_at')
            ->select($columns)
            ->get();

        $records = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            if (array_key_exists('subscription_cycle_id', $arr)) {
                $arr['cycle_id'] = $arr['subscription_cycle_id'];
            }
            $records[] = [
                'table' => $table,
                'row' => $arr,
            ];
        }

        return $records;
    }

    /**
     * For CourseSubscription: derive the session set from session_consumption
     * rows that point at this subscription. Returns whatever metadata is
     * available from interactive_course_sessions for each.
     *
     * @return list<array{table:string,row:array<string,mixed>}>
     */
    private static function fetchSessionRecordsForCourse(BaseSubscription $sub): array
    {
        if (! ($sub instanceof CourseSubscription)) {
            return [];
        }

        if (! Schema::hasTable('session_consumption')) {
            return [];
        }

        $sessionIds = SessionConsumption::query()
            ->where('subscription_type', $sub->getMorphClass())
            ->where('subscription_id', $sub->getKey())
            ->where('session_type', 'interactive_course_session')
            ->pluck('session_id')
            ->unique()
            ->values()
            ->all();

        if ($sessionIds === []) {
            return [];
        }

        $columns = self::filterExistingColumns('interactive_course_sessions', [
            'id', 'status', 'scheduled_at', 'started_at', 'completed_at',
            'subscription_cycle_id', 'created_at', 'updated_at',
        ]);

        $rows = DB::table('interactive_course_sessions')
            ->whereIn('id', $sessionIds)
            ->orderBy('scheduled_at')
            ->select($columns)
            ->get();

        $records = [];
        foreach ($rows as $row) {
            $arr = (array) $row;
            if (array_key_exists('subscription_cycle_id', $arr)) {
                $arr['cycle_id'] = $arr['subscription_cycle_id'];
            }
            $records[] = [
                'table' => 'interactive_course_sessions',
                'row' => $arr,
            ];
        }

        return $records;
    }

    private static function snapshotSession(array $sessionRecord): array
    {
        $row = $sessionRecord['row'];
        $table = $sessionRecord['table'];
        $sessionMorph = match ($table) {
            'quran_sessions' => 'quran_session',
            'academic_sessions' => 'academic_session',
            'interactive_course_sessions' => 'interactive_course_session',
            default => $table,
        };
        $sessionId = (int) ($row['id'] ?? 0);

        $attendance = MeetingAttendance::query()
            ->where('session_type', $sessionMorph)
            ->where('session_id', $sessionId)
            ->get();

        $reportTable = match ($table) {
            'quran_sessions' => 'student_session_reports',
            'academic_sessions' => 'academic_session_reports',
            'interactive_course_sessions' => 'interactive_session_reports',
            default => null,
        };

        $reports = collect();
        if ($reportTable !== null && Schema::hasTable($reportTable)) {
            $reports = collect(DB::table($reportTable)
                ->where('session_id', $sessionId)
                ->select(['id', 'student_id', 'attendance_status', 'evaluated_at', 'is_calculated', 'manually_evaluated'])
                ->get());
        }

        $hasRecording = false;
        if (Schema::hasTable('session_recordings')) {
            // session_recordings uses recordable_type / recordable_id
            // (polymorphic via the recordable() morph), not the session_*
            // naming the other meeting tables use.
            if (Schema::hasColumn('session_recordings', 'recordable_type')) {
                $hasRecording = DB::table('session_recordings')
                    ->where('recordable_type', $sessionMorph)
                    ->where('recordable_id', $sessionId)
                    ->exists();
            } elseif (Schema::hasColumn('session_recordings', 'session_type')) {
                $hasRecording = DB::table('session_recordings')
                    ->where('session_type', $sessionMorph)
                    ->where('session_id', $sessionId)
                    ->exists();
            }
        }

        return [
            'id' => $sessionId,
            'session_type' => $sessionMorph,
            'status' => $row['status'] ?? null,
            'scheduled_at' => self::toIso8601OrNull($row['scheduled_at'] ?? null),
            'completed_at' => self::toIso8601OrNull($row['completed_at'] ?? null),
            'cycle_id' => self::toIntOrNull($row['cycle_id'] ?? null),
            'student_id' => self::toIntOrNull($row['student_id'] ?? null),
            'legacy_subscription_counted' => array_key_exists('subscription_counted', $row)
                ? (bool) $row['subscription_counted']
                : null,
            'legacy_subscription_counted_at' => self::toIso8601OrNull($row['subscription_counted_at'] ?? null),
            'attendance' => $attendance->map(fn (MeetingAttendance $a) => [
                'id' => (int) $a->getKey(),
                'user_id' => self::toIntOrNull($a->user_id),
                'attendance_status' => self::stringify($a->attendance_status),
                'duration_minutes' => self::toIntOrNull($a->duration_minutes),
                'subscription_counted_at' => self::toIso8601OrNull($a->getAttribute('subscription_counted_at')),
            ])->all(),
            'reports' => $reports->map(fn ($r) => [
                'id' => (int) $r->id,
                'student_id' => self::toIntOrNull($r->student_id),
                'attendance_status' => $r->attendance_status,
                'evaluated_at' => self::toIso8601OrNull($r->evaluated_at ?? null),
                'is_calculated' => array_key_exists('is_calculated', (array) $r)
                    ? (bool) $r->is_calculated
                    : null,
                'manually_evaluated' => array_key_exists('manually_evaluated', (array) $r)
                    ? (bool) $r->manually_evaluated
                    : null,
            ])->all(),
            'has_recording' => $hasRecording,
        ];
    }

    private static function snapshotPayment(Payment $payment): array
    {
        return [
            'id' => (int) $payment->getKey(),
            'amount' => self::numericOrNull($payment->getAttribute('amount')),
            'currency' => self::stringify($payment->getAttribute('currency')),
            'status' => self::stringify($payment->getAttribute('status')),
            'gateway' => self::stringify($payment->getAttribute('gateway')),
            'paid_at' => self::toIso8601OrNull($payment->getAttribute('paid_at')),
            'created_at' => self::toIso8601OrNull($payment->getAttribute('created_at')),
        ];
    }

    private static function snapshotConsumption(SessionConsumption $c): array
    {
        return [
            'id' => (int) $c->getKey(),
            'session_id' => (int) $c->session_id,
            'session_type' => $c->session_type,
            'cycle_id' => (int) $c->cycle_id,
            'student_user_id' => (int) $c->student_user_id,
            'consumption_type' => $c->consumption_type,
            'source' => $c->source,
            'consumed_at' => self::toIso8601OrNull($c->consumed_at),
            'reversed_at' => self::toIso8601OrNull($c->reversed_at),
            'reversed_reason' => $c->reversed_reason,
        ];
    }

    private static function snapshotAuditLog(SubscriptionAuditLog $row): array
    {
        return [
            'id' => (int) $row->getKey(),
            'action' => $row->action,
            'source' => $row->source,
            'cycle_id' => self::toIntOrNull($row->cycle_id),
            'has_violations' => (bool) $row->has_violations,
            'invariant_violations' => $row->invariant_violations ?? [],
            'view_state_before' => $row->view_state_before,
            'view_state_after' => $row->view_state_after,
            'created_at' => self::toIso8601OrNull($row->created_at),
        ];
    }

    private static function numericOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param  list<string>  $candidates
     * @return list<string>
     */
    private static function filterExistingColumns(string $table, array $candidates): array
    {
        $existing = [];
        foreach ($candidates as $column) {
            if (Schema::hasColumn($table, $column)) {
                $existing[] = $column;
            }
        }

        return $existing !== [] ? $existing : ['id'];
    }
}
