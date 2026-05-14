<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SessionConsumption
 *
 * Single source of truth for "this session consumed quota from this cycle
 * of this subscription" (Phase A.2 — kills R2 in
 * docs/subscription-recovery-plan.md).
 *
 * Per INV-B2, a session counts toward a cycle if and only if a non-reversed
 * row exists here. The legacy `subscription_counted` (on session rows) and
 * `subscription_counted_at` (on meeting_attendances) become read-derived
 * projections during the migration window — writers to them are deleted in
 * Phase E.
 *
 * All writes go through {@see \App\Services\Subscription\SubscriptionConsumption}
 * (the precedence-aware writer). Raw `SessionConsumption::create()` calls
 * bypass the P5 cascade — only the bootstrap command and tests should hit
 * the model directly.
 *
 * Polymorphic on both ends:
 *   - `session`  → QuranSession | AcademicSession | InteractiveCourseSession
 *   - `subscription` → QuranSubscription | AcademicSubscription | CourseSubscription
 *
 * The morph aliases (quran_session / quran_subscription / …) live in
 * AppServiceProvider::morphMap.
 *
 * @property int $id
 * @property int $session_id
 * @property string $session_type
 * @property int $subscription_id
 * @property string $subscription_type
 * @property int $cycle_id
 * @property int $student_user_id
 * @property string $consumption_type
 * @property string $source
 * @property int|null $source_user_id
 * @property \Carbon\Carbon $consumed_at
 * @property \Carbon\Carbon|null $reversed_at
 * @property string|null $reversed_reason
 * @property int|null $reversed_by_user_id
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class SessionConsumption extends Model
{
    use HasFactory;

    protected $table = 'session_consumption';

    /**
     * P5 precedence cascade — keys are the `source` ENUM values, values are
     * comparable integers (higher wins). SubscriptionConsumption::record()
     * compares the existing row's precedence against the incoming one;
     * lower-precedence writes are dropped + audit-logged as
     * `consumption_demoted_attempt`.
     *
     * @var array<string, int>
     */
    public const SOURCE_PRECEDENCE = [
        'auto_attendance' => 1,
        'teacher_report' => 2,
        'admin_manual' => 3,
    ];

    public const SOURCE_ADMIN_MANUAL = 'admin_manual';

    public const SOURCE_TEACHER_REPORT = 'teacher_report';

    public const SOURCE_AUTO_ATTENDANCE = 'auto_attendance';

    public const TYPE_ATTENDED = 'attended';

    public const TYPE_LATE = 'late';

    public const TYPE_LEFT = 'left';

    public const TYPE_ABSENT_COUNTED = 'absent_counted';

    protected $fillable = [
        'session_id',
        'session_type',
        'subscription_id',
        'subscription_type',
        'cycle_id',
        'student_user_id',
        'consumption_type',
        'source',
        'source_user_id',
        'consumed_at',
        'reversed_at',
        'reversed_reason',
        'reversed_by_user_id',
    ];

    protected $casts = [
        'session_id' => 'integer',
        'subscription_id' => 'integer',
        'cycle_id' => 'integer',
        'student_user_id' => 'integer',
        'source_user_id' => 'integer',
        'reversed_by_user_id' => 'integer',
        'consumed_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    /**
     * Look up the precedence integer for a `source` value. Defaults to 0 for
     * unknown sources so they always lose against any known precedence — the
     * writer should still reject unknown sources before calling this.
     */
    public static function precedenceFor(string $source): int
    {
        return self::SOURCE_PRECEDENCE[$source] ?? 0;
    }

    // ========================================================================
    // Relations
    // ========================================================================

    /**
     * Polymorphic — the session this row charges. Morph alias lives in
     * AppServiceProvider::morphMap (quran_session / academic_session /
     * interactive_course_session).
     */
    public function session(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Polymorphic — the subscription whose quota was consumed. Morph alias:
     * quran_subscription / academic_subscription / course_subscription.
     */
    public function subscription(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The cycle this consumption charges. Reconciler reads
     * `WHERE cycle_id = ? AND reversed_at IS NULL` to derive
     * cycle.sessions_used (INV-B3).
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(SubscriptionCycle::class, 'cycle_id');
    }

    /**
     * The student User whose quota was consumed. For group sessions, several
     * rows share `session_id` with different `student_user_id`s.
     */
    public function studentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    /**
     * Who wrote the row (NULL for system / auto-attendance with no
     * attributable user).
     */
    public function sourceUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'source_user_id');
    }

    /**
     * Who reversed the row (NULL while still active).
     */
    public function reversedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by_user_id');
    }

    // ========================================================================
    // Scopes
    // ========================================================================

    /**
     * Non-reversed rows — the set the reconciler counts against cycle
     * `sessions_used` per INV-B3.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('reversed_at');
    }

    // ========================================================================
    // Convenience predicates
    // ========================================================================

    public function isActive(): bool
    {
        return $this->reversed_at === null;
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }
}
