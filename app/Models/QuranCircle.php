<?php

namespace App\Models;

use App\Enums\WeekDays;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranCircle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'quran_teacher_id',
        'circle_code',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'circle_type',
        'specialization',
        'memorization_level',
        'age_group',
        'gender_type',
        'max_students',
        'enrolled_students',
        'min_students_to_start',
        'session_duration_minutes',
        'monthly_sessions_count',
        'monthly_fee',
        'teacher_monthly_revenue', // Teacher's monthly salary from this circle
        'sessions_completed',

        'status',
        'enrollment_status',
        'learning_objectives', // Re-added for circle goals

        'last_session_at',
        'next_session_at',
        'room_link',
        'meeting_id',
        'meeting_password',
        'recording_enabled',
        'attendance_required',
        'makeup_sessions_allowed',
        'certificates_enabled',
        'avg_rating',
        'total_reviews',
        'completion_rate',
        'dropout_rate',

        'preparation_minutes',
        'ending_buffer_minutes',
        'late_join_grace_period_minutes',
        'schedule_time',
        'schedule_days',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'learning_objectives' => 'array', // Cast for circle goals

        'max_students' => 'integer',
        'enrolled_students' => 'integer',
        'min_students_to_start' => 'integer',
        'session_duration_minutes' => 'integer',
        'sessions_completed' => 'integer',
        'monthly_fee' => 'decimal:2',
        'teacher_monthly_revenue' => 'decimal:2',
        'avg_rating' => 'decimal:1',
        'total_reviews' => 'integer',
        'completion_rate' => 'decimal:2',
        'dropout_rate' => 'decimal:2',
        'status' => 'boolean',
        'recording_enabled' => 'boolean',
        'attendance_required' => 'boolean',
        'makeup_sessions_allowed' => 'boolean',
        'certificates_enabled' => 'boolean',

        'last_session_at' => 'datetime',
        'next_session_at' => 'datetime',
        'preparation_minutes' => 'integer',
        'ending_buffer_minutes' => 'integer',
        'late_join_grace_period_minutes' => 'integer',
        'schedule_days' => 'array',
    ];

    // Constants
    const LEVELS = [
        'beginner' => 'مبتدئ',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
    ];

    const AGE_GROUPS = [
        'children' => 'أطفال',
        'youth' => 'شباب',
        'adults' => 'كبار',
        'all_ages' => 'كل الفئات',
    ];

    const GENDER_TYPES = [
        'male' => 'رجال',
        'female' => 'نساء',
        'mixed' => 'مختلط',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'quran_teacher_id');
    }

    public function teacherUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'quran_teacher_id');
    }

    // Fixed relationship pointing to User model
    public function quranTeacher()
    {
        return $this->belongsTo('\App\Models\User', 'quran_teacher_id', 'id');
    }

    // Test new relationship name
    public function circleTeacher()
    {
        return $this->belongsTo('\App\Models\User', 'quran_teacher_id', 'id');
    }

    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'quran_teacher_id');
    }

    // Temporary workaround for quranTeacher relationship
    public function getQuranTeacherAttribute()
    {
        if (! $this->quran_teacher_id) {
            return null;
        }

        // Create a mock object that matches the expected structure
        $user = \App\Models\User::find($this->quran_teacher_id);
        if (! $user) {
            return null;
        }

        // Get teacher profile if exists
        $teacherProfile = $user->quranTeacherProfile;

        // Return an object that has both user properties and the user relationship
        return (object) [
            'id' => $teacherProfile ? $teacherProfile->id : $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'user_type' => $user->user_type,
            'full_name' => $user->name,
            'user' => $user,
            // Add other properties that views might expect
            'teaching_experience_years' => $teacherProfile->teaching_experience_years ?? null,
            'bio' => $teacherProfile->bio ?? null,
            'qualification' => $teacherProfile->educational_qualification ?? null,
            'avatar' => $user->avatar,
        ];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\User::class, 'quran_circle_students', 'circle_id', 'student_id')
            ->withPivot([
                'enrolled_at',
                'status',
                'attendance_count',
                'missed_sessions',
                'makeup_sessions_used',
                'current_level',
                'progress_notes',
                'parent_rating',
                'student_rating',
                'completion_date',
                'certificate_issued',
            ])
            ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'circle_id');
    }

    public function schedule(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QuranCircleSchedule::class, 'circle_id');
    }

    // Note: QuranCircleEnrollment model doesn't exist, using students pivot relationship instead
    // public function enrollments(): HasMany
    // {
    //     return $this->hasMany(QuranCircleEnrollment::class);
    // }

    public function homework(): HasMany
    {
        return $this->hasMany(QuranHomework::class, 'circle_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(QuranProgress::class, 'circle_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'circle_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', true)
            ->where('enrollment_status', 'open');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', true);
    }

    public function scopeOpenForEnrollment($query)
    {
        return $query->where('enrollment_status', 'open')
            ->where('status', true)
            ->whereRaw('(SELECT COUNT(*) FROM quran_circle_students WHERE circle_id = quran_circles.id) < max_students');
    }

    public function scopeStartingSoon($query, $days = 7)
    {
        return $query->where('status', 'pending')
            ->whereBetween('start_date', [now(), now()->addDays($days)]);
    }

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    public function scopeByGradeLevel($query, $grade)
    {
        return $query->whereJsonContains('grade_levels', $grade);
    }

    public function scopeByAgeRange($query, $age)
    {
        return $query->where('age_range_min', '<=', $age)
            ->where('age_range_max', '>=', $age);
    }

    public function scopeByDay($query, $day)
    {
        return $query->whereJsonContains('schedule_days', $day);
    }

    public function scopeWithAvailableSpots($query)
    {
        return $query->whereRaw('(SELECT COUNT(*) FROM quran_circle_students WHERE circle_id = quran_circles.id) < max_students');
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeHighRated($query, $minRating = 4.0)
    {
        return $query->where('avg_rating', '>=', $minRating);
    }

    // Accessors
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'ar') {
            return $this->name_ar ?? $this->name_en ?? 'حلقة غير محددة';
        }

        return $this->name_en ?? $this->name_ar ?? 'Unnamed Circle';
    }

    public function getDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        if ($locale === 'ar') {
            return $this->description_ar ?? $this->description_en ?? 'لا يوجد وصف';
        }

        return $this->description_en ?? $this->description_ar ?? 'No description';
    }

    public function getStatusTextAttribute(): string
    {
        // Handle boolean status values
        if (is_bool($this->status) || is_numeric($this->status)) {
            return $this->status ? 'نشط' : 'غير نشط';
        }

        // Handle string status values (for backward compatibility)
        $statuses = [
            'planning' => 'قيد التخطيط',
            'pending' => 'في انتظار البداية',
            'active' => 'نشط',
            'ongoing' => 'جاري',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق',
            'inactive' => 'غير نشط',
        ];

        return $statuses[$this->status] ?? ($this->status ? 'نشط' : 'غير نشط');
    }

    public function getEnrollmentStatusTextAttribute(): string
    {
        $statuses = [
            'open' => 'مفتوح للتسجيل',
            'closed' => 'مغلق',
            'full' => 'مكتمل العدد',
            'waitlist' => 'قائمة انتظار',
        ];

        return $statuses[$this->enrollment_status] ?? $this->enrollment_status;
    }

    public function getCircleTypeTextAttribute(): string
    {
        $types = [
            'memorization' => 'حلقة حفظ',
            'recitation' => 'حلقة تلاوة وتجويد',
            'mixed' => 'حلقة مختلطة',
            'advanced' => 'حلقة متقدمة',
            'beginners' => 'حلقة مبتدئين',
        ];

        return $types[$this->circle_type] ?? $this->circle_type;
    }

    public function getSpecializationTextAttribute(): string
    {
        $specializations = [
            'memorization' => 'حفظ القرآن',
            'recitation' => 'تلاوة وتجويد',
            'interpretation' => 'تفسير',
            'arabic_language' => 'اللغة العربية القرآنية',
            'complete' => 'شامل',
        ];

        return $specializations[$this->specialization] ?? $this->specialization;
    }

    public function getMemorizationLevelTextAttribute(): string
    {
        $levels = [
            'beginner' => 'مبتدئ',
            'elementary' => 'أساسي',
            'intermediate' => 'متوسط',
            'advanced' => 'متقدم',
            'expert' => 'متقن',
        ];

        return $levels[$this->memorization_level] ?? $this->memorization_level;
    }

    public function getFormattedMonthlyFeeAttribute(): string
    {
        return number_format((float) $this->monthly_fee, 2).' ريال';
    }

    public function getFormattedTeacherRevenueAttribute(): string
    {
        return number_format((float) $this->teacher_monthly_revenue, 2).' ريال';
    }

    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_students - $this->students()->count());
    }

    public function getIsFullAttribute(): bool
    {
        return $this->students()->count() >= $this->max_students;
    }

    public function getCanStartAttribute(): bool
    {
        return $this->students()->count() >= ($this->min_students_to_start ?? 3);
    }

    public function getScheduleTextAttribute(): string
    {
        if (! $this->schedule || ! $this->schedule->weekly_schedule) {
            return 'لم يتم تحديد الجدول بعد';
        }

        $days = [
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
        ];

        $scheduleText = collect($this->schedule->weekly_schedule)
            ->map(function ($item) use ($days) {
                $day = $days[strtolower($item['day'])] ?? $item['day'];
                $time = $item['time'] ?? 'غير محدد';

                return "{$day} - {$time}";
            })
            ->join('، ');

        return $scheduleText ?: 'لم يتم تحديد الجدول بعد';
    }

    public function getScheduleDaysTextAttribute(): string
    {
        // First, check if we have direct schedule_days attribute
        if (! empty($this->schedule_days) && is_array($this->schedule_days)) {
            return WeekDays::getDisplayNames($this->schedule_days);
        }

        // Fallback to the schedule relationship if available
        if ($this->schedule && $this->schedule->weekly_schedule) {
            $scheduleDays = collect($this->schedule->weekly_schedule)
                ->pluck('day')
                ->unique()
                ->filter()
                ->map(function ($day) {
                    try {
                        return WeekDays::from(strtolower($day))->label();
                    } catch (\ValueError $e) {
                        return $day; // Fallback to original value if not a valid enum
                    }
                });

            return implode('، ', $scheduleDays->toArray());
        }

        return 'لم يتم تحديد الأيام بعد';
    }

    public function getAgeRangeTextAttribute(): string
    {
        if (! $this->age_range_min || ! $this->age_range_max) {
            return 'جميع الأعمار';
        }

        return $this->age_range_min.' - '.$this->age_range_max.' سنة';
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_sessions_planned <= 0) {
            return 0;
        }

        return ($this->sessions_completed / $this->total_sessions_planned) * 100;
    }

    public function getDaysUntilStartAttribute(): int
    {
        if (! $this->start_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->start_date, false));
    }

    public function getDaysUntilRegistrationDeadlineAttribute(): int
    {
        if (! $this->registration_deadline) {
            return 999; // No deadline
        }

        return max(0, now()->diffInDays($this->registration_deadline, false));
    }

    // Methods
    public function enrollStudent(User $student, array $additionalData = []): self
    {
        if ($this->is_full) {
            throw new \Exception('الحلقة مكتملة العدد');
        }

        if (! $this->canEnrollStudent($student)) {
            throw new \Exception('لا يمكن تسجيل هذا الطالب في الحلقة');
        }

        $pivotData = array_merge([
            'enrolled_at' => now(),
            'status' => 'enrolled',
            'attendance_count' => 0,
            'missed_sessions' => 0,
            'makeup_sessions_used' => 0,
            'current_level' => $this->memorization_level,
        ], $additionalData);

        $this->students()->attach($student->id, $pivotData);

        // Update enrollment status if full
        if ($this->students()->count() >= $this->max_students) {
            $this->update(['enrollment_status' => 'full']);
        }

        return $this;
    }

    public function unenrollStudent(User $student, ?string $reason = null): self
    {
        $this->students()->detach($student->id);

        // Update enrollment status
        if ($this->enrollment_status === 'full' && $this->students()->count() < $this->max_students) {
            $this->update(['enrollment_status' => 'open']);
        }

        return $this;
    }

    public function canEnrollStudent(User $student): bool
    {
        // Check if already enrolled
        if ($this->students()->where('user_id', $student->id)->exists()) {
            return false;
        }

        // Check age range
        if ($this->age_range_min && $this->age_range_max) {
            $studentAge = $student->age ?? 0;
            if ($studentAge < $this->age_range_min || $studentAge > $this->age_range_max) {
                return false;
            }
        }

        // Check grade level
        if (! empty($this->grade_levels) && $student->grade_level) {
            if (! in_array($student->grade_level, $this->grade_levels)) {
                return false;
            }
        }

        // Check enrollment status and capacity
        return $this->enrollment_status === 'open' &&
            ! $this->is_full &&
            $this->status === true;
    }

    public function start(): self
    {
        if (! $this->can_start) {
            throw new \Exception('عدد الطلاب غير كافي لبدء الحلقة');
        }

        $this->update([
            'status' => 'ongoing',
            'enrollment_status' => 'closed',
            'start_date' => now(),
        ]);

        return $this;
    }

    public function suspend(?string $reason = null): self
    {
        $this->update([
            'status' => 'suspended',
            'notes' => $reason,
        ]);

        return $this;
    }

    public function resume(): self
    {
        $this->update([
            'status' => 'ongoing',
        ]);

        return $this;
    }

    public function complete(): self
    {
        $this->update([
            'status' => 'completed',
            'enrollment_status' => 'closed',
            'end_date' => now(),
        ]);

        // Issue certificates if enabled
        if ($this->certificates_enabled) {
            $this->issueCertificates();
        }

        return $this;
    }

    public function cancel(?string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'enrollment_status' => 'closed',
            'notes' => $reason,
        ]);

        return $this;
    }

    public function recordSession(array $sessionData): QuranSession
    {
        $session = $this->sessions()->create(array_merge($sessionData, [
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'session_type' => 'circle',
            'participants_count' => $this->current_students,
        ]));

        $this->increment('sessions_completed');
        $this->update(['last_session_at' => now()]);

        return $session;
    }

    public function updateRating(): self
    {
        $ratings = $this->students()
            ->wherePivotNotNull('parent_rating')
            ->orWherePivotNotNull('student_rating')
            ->get();

        if ($ratings->isEmpty()) {
            return $this;
        }

        $totalRating = 0;
        $ratingCount = 0;

        foreach ($ratings as $student) {
            if ($student->pivot->parent_rating) {
                $totalRating += $student->pivot->parent_rating;
                $ratingCount++;
            }
            if ($student->pivot->student_rating) {
                $totalRating += $student->pivot->student_rating;
                $ratingCount++;
            }
        }

        $avgRating = $ratingCount > 0 ? $totalRating / $ratingCount : 0;

        $this->update([
            'avg_rating' => round($avgRating, 1),
            'total_reviews' => $ratingCount,
        ]);

        // Update teacher's rating
        $this->quranTeacher->updateRating();

        return $this;
    }

    public function calculateStatistics(): self
    {
        $totalEnrolled = $this->students()->count();
        $stillEnrolled = $this->students()->wherePivot('status', 'enrolled')->count();
        $completed = $this->students()->wherePivot('status', 'completed')->count();
        $dropped = $this->students()->wherePivot('status', 'dropped')->count();

        $completionRate = $totalEnrolled > 0 ? ($completed / $totalEnrolled) * 100 : 0;
        $dropoutRate = $totalEnrolled > 0 ? ($dropped / $totalEnrolled) * 100 : 0;

        $this->update([
            'completion_rate' => round($completionRate, 2),
            'dropout_rate' => round($dropoutRate, 2),
        ]);

        return $this;
    }

    public function generateSchedule(): self
    {
        if (empty($this->schedule_days) || empty($this->schedule_times)) {
            return $this;
        }

        // Generate recurring sessions based on schedule
        $schedule = [];
        $currentDate = $this->start_date;
        $endDate = $this->end_date ?? $currentDate->copy()->addMonths(3);

        while ($currentDate <= $endDate) {
            $dayName = strtolower($currentDate->format('l'));

            if (in_array($dayName, $this->schedule_days)) {
                foreach ($this->schedule_times as $time) {
                    $sessionDateTime = $currentDate->copy()->setTimeFromTimeString($time);
                    $schedule[] = [
                        'scheduled_at' => $sessionDateTime,
                        'duration_minutes' => $this->session_duration_minutes,
                        'status' => 'scheduled',
                    ];
                }
            }

            $currentDate->addDay();
        }

        $this->update(['weekly_schedule' => $schedule]);

        return $this;
    }

    public function issueCertificates(): self
    {
        if (! $this->certificates_enabled) {
            return $this;
        }

        $completedStudents = $this->students()
            ->wherePivot('status', 'completed')
            ->wherePivot('certificate_issued', false)
            ->get();

        foreach ($completedStudents as $student) {
            // Generate certificate
            $certificateData = [
                'student_name' => $student->name,
                'circle_name' => $this->name,
                'teacher_name' => $this->quranTeacher->user->name,
                'academy_name' => $this->academy->name,
                'completion_date' => now(),
                'specialization' => $this->specialization_text,
                'level' => $this->memorization_level_text,
            ];

            // Update pivot to mark certificate as issued
            $this->students()->updateExistingPivot($student->id, [
                'certificate_issued' => true,
                'completion_date' => now(),
            ]);
        }

        return $this;
    }

    // Static methods
    public static function createCircle(array $data): self
    {
        return self::create(array_merge($data, [
            'circle_code' => self::generateCircleCode($data['academy_id']),
            'enrolled_students' => 0,
            'sessions_completed' => 0,
            'avg_rating' => 0,
            'total_reviews' => 0,
            'completion_rate' => 0,
            'dropout_rate' => 0,
            'status' => 'planning',
            'enrollment_status' => 'closed',
        ]));
    }

    public static function generateCircleCode(int $academyId): string
    {
        $prefix = 'QC';
        $academyPart = $academyId;

        // Get all existing circle codes for this academy
        $existingCodes = self::withTrashed()
            ->where('academy_id', $academyId)
            ->pluck('circle_code')
            ->toArray();

        $maxSequence = 0;

        // Parse existing codes to find the highest sequence number
        foreach ($existingCodes as $code) {
            // Only process codes that match the standard format: QC-{academy}-{sequence}
            if (preg_match("/^{$prefix}-{$academyPart}-(\d+)$/", $code, $matches)) {
                $sequence = (int) $matches[1];
                $maxSequence = max($maxSequence, $sequence);
            }
        }

        $nextSequence = $maxSequence + 1;
        $code = "{$prefix}-{$academyPart}-".str_pad($nextSequence, 6, '0', STR_PAD_LEFT);

        // Double-check for uniqueness (fallback safety)
        $attempt = 0;
        while (in_array($code, $existingCodes) && $attempt < 100) {
            $nextSequence++;
            $code = "{$prefix}-{$academyPart}-".str_pad($nextSequence, 6, '0', STR_PAD_LEFT);
            $attempt++;
        }

        return $code;
    }

    // Boot method to handle model events
    protected static function booted()
    {
        static::creating(function ($circle) {
            if (empty($circle->circle_code)) {
                $circle->circle_code = self::generateCircleCode($circle->academy_id);
            }
        });

        // Clean up related data when circle is deleted
        static::deleting(function ($circle) {
            // Delete all sessions for this circle
            $circle->sessions()->delete();

            // Delete schedule if exists
            if ($circle->schedule) {
                $circle->schedule->delete();
            }

            // Clean up pivot table entries (students)
            $circle->students()->detach();

            // Delete related homework
            $circle->homework()->delete();

            // Delete related progress records
            $circle->progress()->delete();
        });

        // Clean up when force deleting (hard delete)
        static::forceDeleting(function ($circle) {
            // Force delete all sessions for this circle
            $circle->sessions()->forceDelete();

            // Force delete schedule if exists
            if ($circle->schedule) {
                $circle->schedule->forceDelete();
            }

            // Clean up pivot table entries (students)
            $circle->students()->detach();

            // Force delete related homework
            $circle->homework()->forceDelete();

            // Force delete related progress records
            $circle->progress()->forceDelete();
        });
    }

    public static function getOpenForEnrollment(int $academyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('academy_id', $academyId)
            ->openForEnrollment()
            ->with(['quranTeacher.user', 'academy']);

        if (isset($filters['specialization'])) {
            $query->where('specialization', $filters['specialization']);
        }

        if (isset($filters['grade_level'])) {
            $query->byGradeLevel($filters['grade_level']);
        }

        if (isset($filters['age'])) {
            $query->byAgeRange($filters['age']);
        }

        if (isset($filters['day'])) {
            $query->byDay($filters['day']);
        }

        if (isset($filters['min_rating'])) {
            $query->highRated($filters['min_rating']);
        }

        return $query->orderBy('avg_rating', 'desc')
            ->orderBy('start_date', 'asc')
            ->get();
    }

    public static function getStartingSoon(int $academyId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->startingSoon($days)
            ->with(['quranTeacher.user', 'students'])
            ->get();
    }
}
