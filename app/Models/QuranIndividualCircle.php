<?php

namespace App\Models;

use App\Models\Traits\ScopedToAcademy;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;

/**
 * QuranIndividualCircle Model
 *
 * Represents a 1-to-1 Quran learning relationship between a teacher and student.
 *
 * DECOUPLED ARCHITECTURE:
 * - This model can exist independently from subscriptions
 * - subscription_id is NULLABLE (circles don't require a subscription)
 * - Subscriptions link to circles via polymorphic education_unit relationship
 * - Deleting a subscription does NOT delete the circle
 */
class QuranIndividualCircle extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'quran_teacher_id',
        'student_id',
        'subscription_id',
        'circle_code',
        'name',
        'description',
        'specialization',
        'memorization_level',
        'total_sessions',
        'sessions_scheduled',
        'sessions_completed',
        'sessions_remaining',
        // Homework-based progress tracking (lifetime totals)
        'total_memorized_pages',
        'total_reviewed_pages',
        'total_reviewed_surahs',
        'default_duration_minutes',
        'is_active',
        'recording_enabled',
        'started_at',
        'completed_at',
        'last_session_at',
        'learning_objectives',
        'admin_notes',
        'teacher_notes',
        'supervisor_notes',
    ];

    protected $casts = [
        'total_sessions' => 'integer',
        'sessions_scheduled' => 'integer',
        'sessions_completed' => 'integer',
        'sessions_remaining' => 'integer',
        // Homework-based progress tracking
        'total_memorized_pages' => 'integer',
        'total_reviewed_pages' => 'integer',
        'total_reviewed_surahs' => 'integer',
        'default_duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'recording_enabled' => 'boolean',
        'learning_objectives' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_session_at' => 'datetime',
    ];

    // Constants - Standardized across individual and group circles
    const SPECIALIZATIONS = [
        'memorization' => 'حفظ',
        'recitation' => 'تلاوة',
        'interpretation' => 'تفسير',
        'tajweed' => 'تجويد',
        'complete' => 'شامل',
    ];

    const MEMORIZATION_LEVELS = [
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    /**
     * Get the teacher user for this individual circle
     */
    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quran_teacher_id');
    }

    /**
     * Get the Quran teacher profile for this individual circle
     * Uses user_id as the foreign key match since quran_teacher_id stores user IDs
     */
    public function quranTeacherProfile(): BelongsTo
    {
        return $this->belongsTo(QuranTeacherProfile::class, 'quran_teacher_id', 'user_id');
    }

    /**
     * Alias for quranTeacher for consistency
     */
    public function teacher(): BelongsTo
    {
        return $this->quranTeacher();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the legacy subscription (via direct FK - deprecated)
     *
     * @deprecated Use activeSubscription() or linkedSubscriptions() instead
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class, 'subscription_id');
    }

    /**
     * Get all subscriptions that link to this circle via polymorphic relationship
     * (New decoupled architecture)
     */
    public function linkedSubscriptions(): MorphMany
    {
        return $this->morphMany(QuranSubscription::class, 'education_unit');
    }

    /**
     * Get the active subscription for this circle.
     *
     * "Active" here means "schedulable right now", which includes subscriptions
     * whose current cycle is in a grace period even if payment is pending. Uses
     * the `isSchedulable()` contract defined on BaseSubscription so grace-period
     * semantics propagate through every scheduling gate.
     *
     * Checks both polymorphic relationship (new) and direct FK (legacy).
     */
    public function getActiveSubscriptionAttribute(): ?QuranSubscription
    {
        // Use loaded collection when eager-loaded, otherwise use targeted query
        if ($this->relationLoaded('linkedSubscriptions')) {
            $activeLinked = $this->linkedSubscriptions->first(
                fn ($sub) => $sub->isSchedulable()
            );
        } else {
            $activeLinked = $this->linkedSubscriptions()->schedulable()->first();
        }

        if ($activeLinked) {
            return $activeLinked;
        }

        // Fallback to legacy direct FK
        if ($this->subscription_id && $this->subscription?->isSchedulable()) {
            return $this->subscription;
        }

        return null;
    }

    /**
     * Check if this circle has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription !== null;
    }

    /**
     * Check if this circle exists independently (no subscription)
     */
    public function isIndependent(): bool
    {
        return $this->subscription_id === null &&
               $this->linkedSubscriptions()->count() === 0;
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'individual_circle_id');
    }

    public function scheduledSessions(): HasMany
    {
        return $this->sessions()->active();
    }

    public function completedSessions(): HasMany
    {
        return $this->sessions()->countable();
    }

    public function quizAssignments(): MorphMany
    {
        return $this->morphMany(QuizAssignment::class, 'assignable');
    }

    // Note: homework() relationship removed - Quran homework is now tracked through
    // QuranSession model fields and graded through student session reports
    // See migration: 2025_11_17_190605_drop_quran_homework_tables.php

    // Note: progress() relationship removed - Progress is now calculated
    // dynamically from session reports using the QuranReportService
    // See migration: 2025_11_23_drop_progress_tables.php

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: circles that are effectively active (subscription-aware).
     * Active = not completed AND (has schedulable subscription OR no subscription but is_active=true).
     *
     * Uses `schedulable()` so grace-period subscriptions count as effectively active.
     */
    public function scopeEffectivelyActive($query)
    {
        return $query->whereNull('completed_at')
            ->where(function ($q) {
                $q->whereHas('subscription', fn ($sq) => $sq->schedulable())
                    ->orWhereHas('linkedSubscriptions', fn ($sq) => $sq->schedulable())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('subscription_id')
                            ->whereDoesntHave('linkedSubscriptions')
                            ->where('is_active', true);
                    });
            });
    }

    /**
     * Scope: circles that are effectively paused (subscription-aware).
     * Paused = not completed AND (has non-schedulable subscription OR no subscription with is_active=false).
     */
    public function scopeEffectivelyPaused($query)
    {
        return $query->whereNull('completed_at')
            ->where(function ($q) {
                // Has legacy subscription that is not schedulable (or orphaned/deleted)
                $q->where(function ($q2) {
                    $q2->whereNotNull('subscription_id')
                        ->whereDoesntHave('subscription', fn ($sq) => $sq->schedulable())
                        ->whereDoesntHave('linkedSubscriptions', fn ($sq) => $sq->schedulable());
                })->orWhere(function ($q2) {
                    // Has only linked subscriptions, none schedulable
                    $q2->whereNull('subscription_id')
                        ->whereHas('linkedSubscriptions')
                        ->whereDoesntHave('linkedSubscriptions', fn ($sq) => $sq->schedulable());
                })->orWhere(function ($q2) {
                    // No subscriptions at all, is_active=false
                    $q2->whereNull('subscription_id')
                        ->whereDoesntHave('linkedSubscriptions')
                        ->where('is_active', false);
                });
            });
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeWithProgress($query)
    {
        return $query->where('total_memorized_pages', '>', 0)
            ->orWhere('total_reviewed_pages', '>', 0);
    }

    // Methods
    public function generateCircleCode(): string
    {
        $prefix = 'QIC'; // Quran Individual Circle
        $academyCode = $this->academy->code ?? 'AC';
        $timestamp = now()->format('Ymd');

        // Generate unique code by checking database for collisions
        $attempt = 0;
        do {
            $attempt++;
            // Use timestamp with microseconds and random string for better uniqueness
            $random = strtoupper(substr(uniqid().bin2hex(random_bytes(2)), -6));
            $code = "{$prefix}-{$academyCode}-{$timestamp}-{$random}";
        } while (
            self::where('circle_code', $code)->exists() && $attempt < 10
        );

        return $code;
    }

    public function updateSessionCounts(): void
    {
        $this->update([
            'sessions_scheduled' => $this->scheduledSessions()->count(),
            'sessions_completed' => $this->completedSessions()->count(),
            'sessions_remaining' => $this->calculateRemainingSessions(),
        ]);
    }

    /**
     * Handle session cancellation with row locking to prevent concurrent overwrites.
     */
    public function handleSessionCancelled(): void
    {
        DB::transaction(function () {
            $circle = static::lockForUpdate()->find($this->id);

            if (! $circle) {
                return;
            }

            $circle->update([
                'sessions_scheduled' => $circle->scheduledSessions()->count(),
                'sessions_completed' => $circle->completedSessions()->count(),
                'sessions_remaining' => $circle->calculateRemainingSessions(),
            ]);

            Log::info("Circle {$circle->id} session counts recalculated after cancellation", [
                'circle_id' => $circle->id,
                'remaining' => $circle->sessions_remaining,
            ]);
        });
    }

    /**
     * Calculate how many session slots are still available.
     * Uses subscription.sessions_remaining (accounts for consumed sessions set by admin)
     * minus active/pending sessions that haven't been counted against subscription yet.
     */
    private function calculateRemainingSessions(): int
    {
        $subscriptionRemaining = $this->subscription?->sessions_remaining;

        if ($subscriptionRemaining === null) {
            // Fallback when no subscription exists
            $total = $this->total_sessions;
            $used = $this->sessions()->notCancelled()->count();

            return max(0, $total - $used);
        }

        $activeSessions = $this->sessions()->active()->count();

        return max(0, $subscriptionRemaining - $activeSessions);
    }

    /**
     * Update session-based progress (sessions completed/remaining)
     */
    public function updateProgress(): void
    {
        $completedSessions = $this->completedSessions()->count();
        $lastSession = $this->completedSessions()->latest('ended_at')->first();
        $firstSession = $this->completedSessions()->oldest('started_at')->first();

        $updates = [
            'last_session_at' => $lastSession?->ended_at,
        ];

        // Set started_at on first session completion
        if ($completedSessions > 0 && ! $this->started_at) {
            $updates['started_at'] = $firstSession?->started_at;
        }

        // Set completed_at when all sessions are done
        if ($this->total_sessions > 0 && $completedSessions >= $this->total_sessions && ! $this->completed_at) {
            $updates['completed_at'] = now();
        }

        $this->update($updates);
    }

    /**
     * Update homework-based progress from session homework records.
     * Called after session homework is submitted/updated.
     */
    public function updateProgressFromHomework(): void
    {
        $sessions = $this->sessions()->with('homework')->get();

        $totalMemorized = 0;
        $totalReviewed = 0;
        $totalReviewedSurahs = 0;

        foreach ($sessions as $session) {
            if ($session->homework) {
                $totalMemorized += $session->homework->new_memorization_pages ?? 0;
                $totalReviewed += $session->homework->review_pages ?? 0;
                $totalReviewedSurahs += count($session->homework->comprehensive_review_surahs ?? []);
            }
        }

        $this->update([
            'total_memorized_pages' => $totalMemorized,
            'total_reviewed_pages' => $totalReviewed,
            'total_reviewed_surahs' => $totalReviewedSurahs,
        ]);
    }

    /**
     * Get progress summary in Arabic
     */
    public function getProgressSummary(): string
    {
        $parts = [];

        if ($this->total_memorized_pages > 0) {
            $parts[] = "{$this->total_memorized_pages} صفحة محفوظة";
        }
        if ($this->total_reviewed_pages > 0) {
            $parts[] = "{$this->total_reviewed_pages} صفحة مراجعة";
        }
        if ($this->total_reviewed_surahs > 0) {
            $parts[] = "{$this->total_reviewed_surahs} سورة مراجعة شاملة";
        }

        return $parts ? implode('، ', $parts) : 'لم يتم تحديد التقدم';
    }

    /**
     * Get unified display status for consistent rendering across all views.
     *
     * Priority: completed_at → subscription status → is_active fallback.
     *
     * @return array{text: string, class: string}
     */
    public function getDisplayStatusAttribute(): array
    {
        if ($this->completed_at !== null) {
            return [
                'text' => __('teacher.individual_circles_list.status_completed'),
                'class' => 'bg-yellow-100 text-yellow-800',
            ];
        }

        $subscription = $this->activeSubscription ?? $this->subscription;
        $isEffectivelyActive = $subscription
            ? $subscription->isSchedulable()
            : $this->is_active;

        return $isEffectivelyActive
            ? ['text' => __('teacher.individual_circles_list.status_active'), 'class' => 'bg-green-100 text-green-800']
            : ['text' => __('teacher.individual_circles_list.status_paused'), 'class' => 'bg-orange-100 text-orange-800'];
    }

    // Boot method to handle model events
    protected static function booted()
    {
        parent::booted();

        static::creating(function ($circle) {
            if (empty($circle->circle_code)) {
                $circle->circle_code = $circle->generateCircleCode();
            }

            if (empty($circle->name)) {
                $circle->name = "الحلقة الفردية - {$circle->student->name}";
            }

            // Calculate sessions remaining
            $circle->sessions_remaining = $circle->total_sessions;
        });

        // Note: sessions_remaining is maintained by updateSessionCounts() and handleSessionCancelled()
        // No automatic recalculation here to avoid conflicting with those methods
    }
}
