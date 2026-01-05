<?php

namespace App\Models;

use App\Contracts\RecordingCapable;
use App\Enums\AttendanceStatus;
use App\Enums\SessionStatus;
use App\Models\Traits\HasRecording;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class InteractiveCourseSession extends BaseSession implements RecordingCapable
{
    use HasRecording;

    /**
     * Interactive-specific fillable fields (merged with parent in constructor)
     * NOTE: Parent BaseSession fields (including academy_id, scheduled_at) are auto-merged in constructor
     */
    protected $fillable = [
        // Interactive-specific fields
        'course_id',
        'session_number',
        'attendance_count',

        // Lesson content
        'lesson_content',

        // Homework
        'homework_assigned',
        'homework_description',
        'homework_file',
    ];

    protected $attributes = [
        'status' => SessionStatus::SCHEDULED->value,
        'attendance_count' => 0,
        'homework_assigned' => false,
    ];

    /**
     * Constructor - Merge parent fillable with child-specific fields
     * This approach avoids duplicating 37 BaseSession fields while maintaining consistency
     */
    public function __construct(array $attributes = [])
    {
        // Merge parent's static base fillable fields with child-specific fields FIRST
        $this->fillable = array_merge(parent::$baseFillable, $this->fillable);

        // Call grandparent (Model) constructor directly to avoid BaseSession overwriting fillable
        \Illuminate\Database\Eloquent\Model::__construct($attributes);
    }

    /**
     * Boot method - Override parent's ScopedToAcademyForWeb trait
     *
     * InteractiveCourseSession doesn't have an academy_id column directly.
     * It gets academy through the course relationship.
     * We override the global scope to use whereHas('course') instead.
     */
    protected static function booted(): void
    {
        // Remove parent's academy_web scope that filters by academy_id column
        static::withoutGlobalScope('academy_web');

        // Add our own scope that filters via course relationship
        static::addGlobalScope('academy_web', function (Builder $builder) {
            // Skip in console context (jobs, commands)
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            $academyContextService = app(AcademyContextService::class);

            // Skip for super admin in global view mode
            if ($academyContextService->isSuperAdmin() && $academyContextService->isGlobalViewMode()) {
                return;
            }

            $currentAcademyId = $academyContextService->getCurrentAcademyId();

            // Only apply scoping if a specific academy is selected
            if ($currentAcademyId) {
                $builder->whereHas('course', function (Builder $query) use ($currentAcademyId) {
                    $query->where('academy_id', $currentAcademyId);
                });
            }
        });

        // Auto-generate session_code on creation
        static::creating(function (self $session) {
            if (empty($session->session_code)) {
                $session->session_code = $session->generateUniqueSessionCode();
            }
        });
    }

    /**
     * Generate a unique session code for interactive course sessions.
     *
     * Format: IC-{YYMM}-{SEQ} (e.g., IC-2601-0042)
     */
    protected function generateUniqueSessionCode(): string
    {
        return \DB::transaction(function () {
            $prefix = 'IC';
            $yearMonth = now()->format('ym');
            $codePrefix = "{$prefix}-{$yearMonth}-";

            // Get the last sequence number for this month
            $lastSession = static::withTrashed()
                ->where('session_code', 'LIKE', $codePrefix.'%')
                ->lockForUpdate()
                ->orderByRaw("CAST(SUBSTRING(session_code, -4) AS UNSIGNED) DESC")
                ->first(['session_code']);

            $nextSequence = 1;
            if ($lastSession && preg_match('/(\d{4})$/', $lastSession->session_code, $matches)) {
                $nextSequence = (int) $matches[1] + 1;
            }

            return $codePrefix.str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        }, 5); // 5 retries for deadlock handling
    }

    /**
     * Get the casts array - merges parent BaseSession casts with Interactive-specific casts
     * This ensures Laravel properly casts attributes like status (enum) and scheduled_at (datetime)
     *
     * NOTE: We don't use protected $casts property because it would override parent's casts.
     * Instead, we merge parent casts with Interactive-specific casts at runtime.
     */
    public function getCasts(): array
    {
        return array_merge(parent::getCasts(), [
            // Interactive-specific casts
            'attendance_count' => 'integer',
            'homework_assigned' => 'boolean',
        ]);
    }

    /**
     * Academy ID accessor - returns column value or fallback to course
     *
     * Priority:
     * 1. Return the actual academy_id column value if set
     * 2. Fall back to course->academy_id only if column is NULL
     */
    public function getAcademyIdAttribute(): ?int
    {
        // Check raw column value first (not through accessor to avoid recursion)
        $columnValue = $this->attributes['academy_id'] ?? null;

        if ($columnValue !== null) {
            return (int) $columnValue;
        }

        // Fallback to course relationship for legacy records
        return $this->course?->academy_id;
    }

    /**
     * Override academy() relationship
     * Uses the academy_id column directly (backfilled from course for legacy records)
     *
     * NOTE: The getAcademyIdAttribute() accessor provides a fallback for
     * direct attribute access when academy_id column is NULL
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class, 'academy_id');
    }

    /**
     * العلاقة مع الدورة التفاعلية
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(InteractiveCourse::class, 'course_id');
    }

    /**
     * حضور الطلاب في هذه الجلسة
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id');
    }

    /**
     * Get all attendance records for this interactive session
     * Overrides BaseSession abstract method
     */
    public function attendanceRecords(): HasMany
    {
        return $this->attendances();
    }

    /**
     * Get enrolled students through the course relationship
     */
    public function enrolledStudents(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            StudentProfile::class,
            InteractiveCourseEnrollment::class,
            'course_id',
            'id',
            'course_id',
            'student_id'
        );
    }

    /**
     * تقارير الطلاب لهذه الجلسة
     */
    public function studentReports(): HasMany
    {
        return $this->hasMany(InteractiveSessionReport::class, 'session_id');
    }

    /**
     * Alias for studentReports for consistency with other session types
     */
    public function reports(): HasMany
    {
        return $this->studentReports();
    }

    /**
     * الطلاب الحاضرون
     */
    public function presentStudents(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id')
                    ->where('attendance_status', AttendanceStatus::ATTENDED->value);
    }

    /**
     * الطلاب الغائبون
     */
    public function absentStudents(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id')
                    ->where('attendance_status', AttendanceStatus::ABSENT->value);
    }

    /**
     * الطلاب المتأخرون
     */
    public function lateStudents(): HasMany
    {
        return $this->hasMany(InteractiveSessionAttendance::class, 'session_id')
                    ->where('attendance_status', AttendanceStatus::LATE->value);
    }

    // Common scopes (scheduled, completed, cancelled, ongoing, today, upcoming, past)
    // are inherited from BaseSession

    /**
     * نطاق الجلسات لهذا الأسبوع
     * Uses academy timezone for accurate comparison
     */
    public function scopeThisWeek($query)
    {
        $now = AcademyContextService::nowInAcademyTimezone();
        return $query->whereBetween('scheduled_at', [
            $now->copy()->startOfWeek(),
            $now->copy()->endOfWeek()
        ]);
    }

    /**
     * الحصول على حالة الجلسة بالعربية
     */
    public function getStatusInArabicAttribute(): string
    {
        return $this->status->label();
    }


    /**
     * الحصول على وقت انتهاء الجلسة
     */
    public function getEndTimeAttribute(): ?Carbon
    {
        if (!$this->scheduled_at) {
            return null;
        }
        return $this->scheduled_at->copy()->addMinutes($this->duration_minutes ?? 60);
    }

    /**
     * Check if session can be started
     * Session can start 30 minutes before scheduled time and up to 30 minutes after
     * Uses academy timezone for accurate comparison
     */
    public function canStart(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        if ($this->status !== \App\Enums\SessionStatus::SCHEDULED) {
            return false;
        }

        $now = AcademyContextService::nowInAcademyTimezone();
        $minutesUntilSession = $now->diffInMinutes($this->scheduled_at, false);
        // Can start 30 minutes before or up to 30 minutes after scheduled time
        return $minutesUntilSession >= -30 && $minutesUntilSession <= 30;
    }

    /**
     * التحقق من أن الجلسة قابلة للإلغاء
     */
    public function canCancel(): bool
    {
        if (!$this->scheduled_at) {
            return false;
        }

        return in_array($this->status, [\App\Enums\SessionStatus::SCHEDULED, \App\Enums\SessionStatus::ONGOING]) &&
               $this->scheduled_at->isFuture();
    }

    // ========================================
    // STATUS MANAGEMENT METHODS (Aligned with AcademicSession/QuranSession)
    // ========================================

    /**
     * Mark session as ongoing
     * Called when teacher starts the session
     */
    public function markAsOngoing(): bool
    {
        if (!in_array($this->status, [\App\Enums\SessionStatus::SCHEDULED, \App\Enums\SessionStatus::READY])) {
            return false;
        }

        $this->update([
            'status' => \App\Enums\SessionStatus::ONGOING,
            'started_at' => now(),
        ]);

        return true;
    }

    /**
     * Mark session as completed
     * Updates attendance counts and session records
     */
    public function markAsCompleted(array $additionalData = []): bool
    {
        return \DB::transaction(function () use ($additionalData) {
            // Lock for update to prevent race conditions
            $session = self::lockForUpdate()->find($this->id);

            if (!$session) {
                return false;
            }

            if (!in_array($session->status, [\App\Enums\SessionStatus::ONGOING, \App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::SCHEDULED])) {
                return false;
            }

            // Validate that we're not completing before start time
            if ($session->started_at && now()->lt($session->started_at)) {
                return false; // Cannot complete before start
            }

            $updateData = array_merge([
                'status' => \App\Enums\SessionStatus::COMPLETED,
                'ended_at' => now(),
                'attendance_status' => \App\Enums\AttendanceStatus::ATTENDED->value,
            ], $additionalData);

            $session->update($updateData);

            // Update attendance count cache
            $session->updateAttendanceCount();

            // Refresh the model
            $this->refresh();

            return true;
        });
    }

    /**
     * Mark session as cancelled
     * Does not affect course completion
     */
    public function markAsCancelled(?string $reason = null, ?User $cancelledBy = null, ?string $cancellationType = null): bool
    {
        if (!in_array($this->status, [\App\Enums\SessionStatus::SCHEDULED, \App\Enums\SessionStatus::READY, \App\Enums\SessionStatus::ONGOING])) {
            return false;
        }

        $this->update([
            'status' => \App\Enums\SessionStatus::CANCELLED,
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
            'cancelled_at' => now(),
            'cancellation_type' => $cancellationType,
        ]);

        return true;
    }

    /**
     * تحديث عدد الحضور
     */
    public function updateAttendanceCount(): void
    {
        $this->update([
            'attendance_count' => $this->attendances()->where('attendance_status', AttendanceStatus::ATTENDED->value)->count()
        ]);
    }

    /**
     * الحصول على نسبة الحضور
     */
    public function getAttendanceRateAttribute(): float
    {
        $totalEnrolled = $this->course->enrollments()->where('enrollment_status', 'enrolled')->count();
        
        if ($totalEnrolled === 0) {
            return 0;
        }

        return round(($this->attendance_count / $totalEnrolled) * 100, 2);
    }

    /**
     * الحصول على متوسط درجة المشاركة
     */
    public function getAverageParticipationScoreAttribute(): float
    {
        $scores = $this->attendances()
                      ->whereNotNull('participation_score')
                      ->pluck('participation_score');

        if ($scores->isEmpty()) {
            return 0;
        }

        return round($scores->avg(), 1);
    }


    /**
     * الحصول على تفاصيل الجلسة
     */
    public function getSessionDetailsAttribute(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'session_number' => $this->session_number,
            'scheduled_at' => $this->scheduled_at?->format('Y-m-d H:i'),
            'duration_minutes' => $this->duration_minutes,
            'status' => $this->status,
            'status_in_arabic' => $this->status_in_arabic,
            'attendance_count' => $this->attendance_count,
            'attendance_rate' => $this->attendance_rate,
            'average_participation_score' => $this->average_participation_score,
            'homework_assigned' => $this->homework_assigned,
            'meeting_link' => $this->meeting_link, // Now using LiveKit
            'can_start' => $this->canStart(),
            'can_cancel' => $this->canCancel(),
        ];
    }

    // ========================================
    // ABSTRACT METHOD IMPLEMENTATIONS (Required by BaseSession)
    // ========================================

    /**
     * Get the session type key for naming service.
     *
     * Returns 'interactive_course' for interactive course sessions.
     */
    public function getSessionTypeKey(): string
    {
        return 'interactive_course';
    }

    /**
     * Get the meeting type identifier (abstract method implementation)
     */
    public function getMeetingType(): string
    {
        return 'interactive';
    }

    /**
     * Get all participants for this session (abstract method implementation)
     */
    public function getParticipants(): array
    {
        $participants = [];

        // Add the course teacher
        if ($this->course && $this->course->academicTeacher && $this->course->academicTeacher->user) {
            $participants[] = [
                'id' => $this->course->academicTeacher->user->id,
                'name' => trim($this->course->academicTeacher->user->first_name . ' ' . $this->course->academicTeacher->user->last_name),
                'email' => $this->course->academicTeacher->user->email,
                'role' => 'academic_teacher',
                'is_teacher' => true,
                'user' => $this->course->academicTeacher->user,
            ];
        }

        // Add all enrolled students
        if ($this->course) {
            $enrolledStudents = $this->course->enrollments()->with('student.user')->get();
            foreach ($enrolledStudents as $enrollment) {
                if ($enrollment->student && $enrollment->student->user) {
                    $user = $enrollment->student->user;
                    $participants[] = [
                        'id' => $user->id,
                        'name' => trim($user->first_name . ' ' . $user->last_name),
                        'email' => $user->email,
                        'role' => 'student',
                        'is_teacher' => false,
                        'user' => $user,
                    ];
                }
            }
        }

        return $participants;
    }

    /**
     * Get meeting-specific configuration (abstract method implementation)
     * Recording setting is controlled at course level
     */
    public function getMeetingConfiguration(): array
    {
        return [
            'session_type' => 'interactive',
            'session_id' => $this->id,
            'session_number' => $this->session_number,
            'course_id' => $this->course_id,
            'duration_minutes' => $this->duration_minutes ?? 90,
            'max_participants' => 30,
            'recording_enabled' => $this->course?->recording_enabled ?? true,
            'chat_enabled' => true,
            'screen_sharing_enabled' => true,
            'whiteboard_enabled' => true,
            'breakout_rooms_enabled' => true,
            'waiting_room_enabled' => true,
            'mute_on_join' => true,
            'camera_on_join' => true,
        ];
    }

    /**
     * Check if a user can manage the meeting (abstract method implementation)
     */
    public function canUserManageMeeting(User $user): bool
    {
        // Super admin can manage any meeting
        if ($user->user_type === 'super_admin') {
            return true;
        }

        // Academy admin can manage meetings in their academy
        if ($user->user_type === 'admin' && $this->course && $user->academy_id === $this->course->academy_id) {
            return true;
        }

        // Course teacher can manage their sessions
        if ($user->user_type === 'academic_teacher' && $this->course && $this->course->assignedTeacher && $this->course->assignedTeacher->user_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user is a participant in this session (abstract method implementation)
     */
    public function isUserParticipant(User $user): bool
    {
        // Teacher is a participant
        if ($this->course && $this->course->assignedTeacher && $this->course->assignedTeacher->user_id === $user->id) {
            return true;
        }

        // Enrolled students are participants
        if ($this->course && $user->user_type === 'student' && $user->studentProfile) {
            return $this->course->enrollments()->where('student_id', $user->studentProfile->id)->exists();
        }

        return false;
    }

    /**
     * Get all participants who should have access to this meeting (abstract method implementation)
     */
    public function getMeetingParticipants(): \Illuminate\Database\Eloquent\Collection
    {
        $participants = collect();

        // Add course teacher
        if ($this->course && $this->course->academicTeacher && $this->course->academicTeacher->user) {
            $participants->push($this->course->academicTeacher->user);
        }

        // Add all enrolled students (get User objects from StudentProfile)
        if ($this->course) {
            $enrolledUsers = $this->course->enrollments()
                ->with('student.user')
                ->get()
                ->map(fn($enrollment) => $enrollment->student?->user)
                ->filter();
            $participants = $participants->merge($enrolledUsers);
        }

        // Remove duplicates and null values
        return $participants->filter()->unique('id');
    }

    // ========================================
    // OVERRIDE BASESESSION TIMING METHODS TO USE ACADEMY SETTINGS
    // ========================================

    /**
     * Get preparation minutes before session from academy settings
     * Overrides BaseSession hardcoded value
     */
    protected function getPreparationMinutes(): int
    {
        if ($this->academy && $this->academy->settings) {
            return $this->academy->settings->default_preparation_minutes ?? 10;
        }

        return 10; // Fallback default
    }

    /**
     * Get ending buffer minutes after session from academy settings
     * Overrides BaseSession hardcoded value
     */
    protected function getEndingBufferMinutes(): int
    {
        if ($this->academy && $this->academy->settings) {
            return $this->academy->settings->default_buffer_minutes ?? 5;
        }

        return 5; // Fallback default
    }

    /**
     * Get grace period minutes for late joins from academy settings
     * Overrides BaseSession hardcoded value
     */
    protected function getGracePeriodMinutes(): int
    {
        if ($this->academy && $this->academy->settings) {
            return $this->academy->settings->default_late_tolerance_minutes ?? 15;
        }

        return 15; // Fallback default
    }

    // ========================================
    // RECORDING CAPABILITY IMPLEMENTATION
    // ========================================

    /**
     * Override: Check if recording is enabled for this session
     *
     * For InteractiveCourseSession, recording is controlled by course's recording_enabled field
     */
    public function isRecordingEnabled(): bool
    {
        return $this->course && (bool) $this->course->recording_enabled;
    }

    /**
     * Provide extended metadata for recordings specific to Interactive Course sessions
     * This is merged with base metadata from HasRecording trait
     */
    protected function getExtendedRecordingMetadata(): array
    {
        $metadata = [
            'course_id' => $this->course_id,
            'course_title' => $this->course?->title,
            'session_number' => $this->session_number,
            'teacher_id' => $this->course?->assigned_teacher_id,
            'teacher_name' => $this->course?->assignedTeacher?->user?->full_name,
        ];

        // Add enrollment count
        if ($this->course) {
            $metadata['enrolled_students_count'] = $this->course->enrollments()->count();
        }

        return $metadata;
    }
}
