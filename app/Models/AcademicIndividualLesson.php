<?php

namespace App\Models;

use App\Enums\LessonStatus;
use App\Enums\SessionStatus;
use App\Models\Traits\ScopedToAcademy;
use DB;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Log;

class AcademicIndividualLesson extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'academic_teacher_id',
        'student_id',
        'academic_subscription_id',
        'lesson_code',
        'name',
        'description',
        'academic_subject_id',
        'academic_grade_level_id',
        'total_sessions',
        'sessions_scheduled',
        'sessions_completed',
        'sessions_remaining',
        'lesson_topics_covered',
        'current_topics',
        'progress_percentage',
        'default_duration_minutes',
        'preferred_times',
        'status',
        'started_at',
        'completed_at',
        'last_session_at',
        'recording_enabled',
        'materials_used',
        'learning_objectives',
        'admin_notes',
        'supervisor_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => LessonStatus::class,
        'preferred_times' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_session_at' => 'datetime',
        'recording_enabled' => 'boolean',
        'materials_used' => 'array',
        'learning_objectives' => 'array',
        'progress_percentage' => 'decimal:2',
        'total_sessions' => 'integer',
        'sessions_scheduled' => 'integer',
        'sessions_completed' => 'integer',
        'sessions_remaining' => 'integer',
        'default_duration_minutes' => 'integer',
    ];

    /**
     * Boot method to auto-generate lesson code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->lesson_code)) {
                if (! $model->academy_id) {
                    throw new \RuntimeException('academy_id is required to generate lesson_code');
                }
                $academyId = $model->academy_id;
                DB::transaction(function () use ($model, $academyId) {
                    $last = static::withoutGlobalScopes()
                        ->where('academy_id', $academyId)
                        ->lockForUpdate()
                        ->orderByRaw('CAST(SUBSTRING(lesson_code, -4) AS UNSIGNED) DESC')
                        ->first(['lesson_code']);
                    $seq = $last && preg_match('/(\d{4})$/', $last->lesson_code, $m) ? (int) $m[1] + 1 : 1;
                    $model->lesson_code = 'AL-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-'.str_pad($seq, 4, '0', STR_PAD_LEFT);
                });
            }
        });
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function academicTeacher(): BelongsTo
    {
        return $this->belongsTo(AcademicTeacherProfile::class, 'academic_teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function academicSubscription(): BelongsTo
    {
        return $this->belongsTo(AcademicSubscription::class);
    }

    /**
     * Alias for academicSubscription (for API compatibility)
     */
    public function subscription(): BelongsTo
    {
        return $this->academicSubscription();
    }

    public function academicSubject(): BelongsTo
    {
        return $this->belongsTo(AcademicSubject::class);
    }

    public function subject(): BelongsTo
    {
        return $this->academicSubject();
    }

    public function academicGradeLevel(): BelongsTo
    {
        return $this->belongsTo(AcademicGradeLevel::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(AcademicSession::class, 'academic_individual_lesson_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function quizAssignments(): MorphMany
    {
        return $this->morphMany(QuizAssignment::class, 'assignable');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', LessonStatus::ACTIVE->value);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('academic_teacher_id', $teacherId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Helper methods
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name.' ('.$this->lesson_code.')';
    }

    public function isActive(): bool
    {
        return $this->status === LessonStatus::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->status === LessonStatus::COMPLETED;
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_sessions == 0) {
            return 0;
        }

        return round(($this->sessions_completed / $this->total_sessions) * 100, 2);
    }

    /**
     * Update session counts from related sessions
     */
    public function updateSessionCounts(): void
    {
        $scheduled = $this->sessions()
            ->active()
            ->count();

        $completed = $this->sessions()
            ->countable()
            ->count();

        $remaining = $this->sessions()
            ->where('status', SessionStatus::UNSCHEDULED->value)
            ->count();

        $this->update([
            'sessions_scheduled' => $scheduled,
            'sessions_completed' => $completed,
            'sessions_remaining' => $remaining,
        ]);
    }

    /**
     * Handle session cancellation by creating a replacement UNSCHEDULED session.
     * The scheduling system requires UNSCHEDULED rows to schedule — without a
     * replacement, the freed slot cannot be rescheduled.
     */
    public function handleSessionCancelled(AcademicSession $cancelledSession): void
    {
        DB::transaction(function () use ($cancelledSession) {
            $lesson = static::lockForUpdate()->find($this->id);

            if (! $lesson) {
                return;
            }

            // Create a replacement UNSCHEDULED session so the slot can be rescheduled
            $lesson->createReplacementUnscheduledSession($cancelledSession);

            // Recalculate all cached counters from actual session data
            $lesson->update([
                'sessions_scheduled' => $lesson->sessions()->active()->count(),
                'sessions_completed' => $lesson->sessions()->countable()->count(),
                'sessions_remaining' => $lesson->sessions()->where('status', SessionStatus::UNSCHEDULED->value)->count(),
            ]);

            Log::info("Lesson {$lesson->id} replacement session created after cancellation", [
                'lesson_id' => $lesson->id,
                'cancelled_session_id' => $cancelledSession->id,
                'new_remaining' => $lesson->sessions_remaining,
            ]);
        });
    }

    /**
     * Create a single UNSCHEDULED AcademicSession to replace a cancelled or uncounted session.
     * Uses withoutEvents() to bypass the BaseSessionObserver schedulability guard.
     */
    public function createReplacementUnscheduledSession(AcademicSession $sourceSession): void
    {
        $nextNumber = ((int) AcademicSession::where('academic_individual_lesson_id', $this->id)->max('session_number')) + 1;
        $subscriptionId = $sourceSession->academic_subscription_id;
        $sessionCode = 'AS-' . $subscriptionId . '-' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

        $subjectName = $sourceSession->academicSubscription?->subject_name ?? 'مادة';

        AcademicSession::withoutEvents(function () use ($sourceSession, $nextNumber, $sessionCode, $subjectName) {
            AcademicSession::create([
                'academy_id' => $sourceSession->academy_id,
                'academic_teacher_id' => $sourceSession->academic_teacher_id,
                'academic_subscription_id' => $sourceSession->academic_subscription_id,
                'academic_individual_lesson_id' => $this->id,
                'student_id' => $sourceSession->student_id,
                'session_code' => $sessionCode,
                'session_type' => 'individual',
                'status' => SessionStatus::UNSCHEDULED,
                'session_number' => $nextNumber,
                'title' => __('sessions.naming.academic_session', ['n' => $nextNumber, 'subject' => $subjectName]),
                'description' => $sourceSession->description,
                'duration_minutes' => $sourceSession->duration_minutes ?? 60,
                'created_by' => $sourceSession->student_id,
            ]);
        });

        Log::info("Replacement UNSCHEDULED session created for lesson {$this->id}", [
            'lesson_id' => $this->id,
            'source_session_id' => $sourceSession->id,
            'new_session_number' => $nextNumber,
        ]);
    }
}
