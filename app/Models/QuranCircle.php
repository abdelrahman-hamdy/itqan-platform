<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranCircle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'quran_teacher_id',
        'supervisor_id',
        'circle_code',
        'name_ar',
        'name_en',
        'description_ar',
        'description_en',
        'circle_type',
        'specialization',
        'memorization_level',
        'grade_levels',
        'age_range_min',
        'age_range_max',
        'max_students',
        'current_students',
        'min_students_to_start',
        'session_duration_minutes',
        'weekly_schedule',
        'schedule_days',
        'schedule_times',
        'timezone',
        'price_per_student',
        'monthly_fee',
        'currency',
        'enrollment_fee',
        'materials_fee',
        'total_sessions_planned',
        'sessions_completed',
        'current_surah',
        'current_verse',
        'teaching_method',
        'materials_used',
        'requirements',
        'learning_objectives',
        'assessment_methods',
        'status',
        'enrollment_status',
        'start_date',
        'end_date',
        'registration_deadline',
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
        'notes',
        'special_instructions',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'grade_levels' => 'array',
        'schedule_days' => 'array',
        'schedule_times' => 'array',
        'weekly_schedule' => 'array',
        'materials_used' => 'array',
        'requirements' => 'array',
        'learning_objectives' => 'array',
        'assessment_methods' => 'array',
        'age_range_min' => 'integer',
        'age_range_max' => 'integer',
        'max_students' => 'integer',
        'current_students' => 'integer',
        'min_students_to_start' => 'integer',
        'session_duration_minutes' => 'integer',
        'total_sessions_planned' => 'integer',
        'sessions_completed' => 'integer',
        'price_per_student' => 'decimal:2',
        'monthly_fee' => 'decimal:2',
        'enrollment_fee' => 'decimal:2',
        'materials_fee' => 'decimal:2',
        'avg_rating' => 'decimal:1',
        'total_reviews' => 'integer',
        'completion_rate' => 'decimal:2',
        'dropout_rate' => 'decimal:2',
        'recording_enabled' => 'boolean',
        'attendance_required' => 'boolean',
        'makeup_sessions_allowed' => 'boolean',
        'certificates_enabled' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'registration_deadline' => 'date',
        'last_session_at' => 'datetime',
        'next_session_at' => 'datetime'
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacher::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quran_circle_students', 'circle_id', 'student_id')
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
                        'certificate_issued'
                    ])
                    ->withTimestamps();
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'circle_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(QuranCircleEnrollment::class);
    }

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
        return $query->where('status', 'active')
                    ->where('enrollment_status', 'open');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing')
                    ->where('start_date', '<=', now())
                    ->where(function($q) {
                        $q->where('end_date', '>=', now())
                          ->orWhereNull('end_date');
                    });
    }

    public function scopeOpenForEnrollment($query)
    {
        return $query->where('enrollment_status', 'open')
                    ->where('registration_deadline', '>=', now())
                    ->where('current_students', '<', 'max_students');
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
        return $query->whereRaw('current_students < max_students');
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
        return app()->getLocale() === 'ar' ? $this->name_ar : $this->name_en;
    }

    public function getDescriptionAttribute(): string
    {
        return app()->getLocale() === 'ar' ? $this->description_ar : $this->description_en;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'planning' => 'قيد التخطيط',
            'pending' => 'في انتظار البداية',
            'active' => 'نشط',
            'ongoing' => 'جاري',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            'suspended' => 'معلق'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getEnrollmentStatusTextAttribute(): string
    {
        $statuses = [
            'open' => 'مفتوح للتسجيل',
            'closed' => 'مغلق',
            'full' => 'مكتمل العدد',
            'waitlist' => 'قائمة انتظار'
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
            'beginners' => 'حلقة مبتدئين'
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
            'complete' => 'شامل'
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
            'expert' => 'متقن'
        ];

        return $levels[$this->memorization_level] ?? $this->memorization_level;
    }

    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price_per_student, 2) . ' ' . $this->currency;
    }

    public function getFormattedMonthlyFeeAttribute(): string
    {
        return number_format($this->monthly_fee, 2) . ' ' . $this->currency;
    }

    public function getTotalCostAttribute(): float
    {
        return $this->monthly_fee + $this->enrollment_fee + $this->materials_fee;
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return number_format($this->total_cost, 2) . ' ' . $this->currency;
    }

    public function getAvailableSpotsAttribute(): int
    {
        return max(0, $this->max_students - $this->current_students);
    }

    public function getIsFullAttribute(): bool
    {
        return $this->current_students >= $this->max_students;
    }

    public function getCanStartAttribute(): bool
    {
        return $this->current_students >= $this->min_students_to_start;
    }

    public function getScheduleTextAttribute(): string
    {
        if (empty($this->schedule_days) || empty($this->schedule_times)) {
            return 'لم يتم تحديد الجدول بعد';
        }

        $days = [
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت'
        ];

        $scheduleDays = array_map(fn($day) => $days[$day] ?? $day, $this->schedule_days);
        $times = implode(', ', $this->schedule_times);

        return implode(', ', $scheduleDays) . ' - ' . $times;
    }

    public function getAgeRangeTextAttribute(): string
    {
        if (!$this->age_range_min || !$this->age_range_max) {
            return 'جميع الأعمار';
        }

        return $this->age_range_min . ' - ' . $this->age_range_max . ' سنة';
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
        if (!$this->start_date) {
            return 0;
        }

        return max(0, now()->diffInDays($this->start_date, false));
    }

    public function getDaysUntilRegistrationDeadlineAttribute(): int
    {
        if (!$this->registration_deadline) {
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

        if (!$this->canEnrollStudent($student)) {
            throw new \Exception('لا يمكن تسجيل هذا الطالب في الحلقة');
        }

        $pivotData = array_merge([
            'enrolled_at' => now(),
            'status' => 'enrolled',
            'attendance_count' => 0,
            'missed_sessions' => 0,
            'makeup_sessions_used' => 0,
            'current_level' => $this->memorization_level
        ], $additionalData);

        $this->students()->attach($student->id, $pivotData);
        $this->increment('current_students');

        // Update enrollment status if full
        if ($this->current_students >= $this->max_students) {
            $this->update(['enrollment_status' => 'full']);
        }

        return $this;
    }

    public function unenrollStudent(User $student, string $reason = null): self
    {
        $this->students()->detach($student->id);
        $this->decrement('current_students');

        // Update enrollment status
        if ($this->enrollment_status === 'full' && $this->current_students < $this->max_students) {
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
        if (!empty($this->grade_levels) && $student->grade_level) {
            if (!in_array($student->grade_level, $this->grade_levels)) {
                return false;
            }
        }

        // Check enrollment status and capacity
        return $this->enrollment_status === 'open' && 
               !$this->is_full && 
               $this->registration_deadline >= now();
    }

    public function start(): self
    {
        if (!$this->can_start) {
            throw new \Exception('عدد الطلاب غير كافي لبدء الحلقة');
        }

        $this->update([
            'status' => 'ongoing',
            'enrollment_status' => 'closed',
            'start_date' => now()
        ]);

        return $this;
    }

    public function suspend(string $reason = null): self
    {
        $this->update([
            'status' => 'suspended',
            'notes' => $reason
        ]);

        return $this;
    }

    public function resume(): self
    {
        $this->update([
            'status' => 'ongoing'
        ]);

        return $this;
    }

    public function complete(): self
    {
        $this->update([
            'status' => 'completed',
            'enrollment_status' => 'closed',
            'end_date' => now()
        ]);

        // Issue certificates if enabled
        if ($this->certificates_enabled) {
            $this->issueCertificates();
        }

        return $this;
    }

    public function cancel(string $reason = null): self
    {
        $this->update([
            'status' => 'cancelled',
            'enrollment_status' => 'closed',
            'notes' => $reason
        ]);

        return $this;
    }

    public function recordSession(array $sessionData): QuranSession
    {
        $session = $this->sessions()->create(array_merge($sessionData, [
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'session_type' => 'circle',
            'participants_count' => $this->current_students
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
            'total_reviews' => $ratingCount
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
            'dropout_rate' => round($dropoutRate, 2)
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
                        'status' => 'scheduled'
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
        if (!$this->certificates_enabled) {
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
                'level' => $this->memorization_level_text
            ];

            // Update pivot to mark certificate as issued
            $this->students()->updateExistingPivot($student->id, [
                'certificate_issued' => true,
                'completion_date' => now()
            ]);
        }

        return $this;
    }

    // Static methods
    public static function createCircle(array $data): self
    {
        return self::create(array_merge($data, [
            'circle_code' => self::generateCircleCode($data['academy_id']),
            'current_students' => 0,
            'sessions_completed' => 0,
            'avg_rating' => 0,
            'total_reviews' => 0,
            'completion_rate' => 0,
            'dropout_rate' => 0,
            'status' => 'planning',
            'enrollment_status' => 'closed'
        ]));
    }

    private static function generateCircleCode(int $academyId): string
    {
        $count = self::where('academy_id', $academyId)->count() + 1;
        return 'QC-' . $academyId . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
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