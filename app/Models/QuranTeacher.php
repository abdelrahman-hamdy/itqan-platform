<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuranTeacher extends Model
{
    use HasFactory;

    protected $table = 'quran_teacher_profiles';

    protected $fillable = [
        'academy_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'teacher_code',
        'specialization',
        'has_ijazah',
        'ijazah_type',
        'ijazah_chain',
        'memorization_level',
        'teaching_experience_years',
        'educational_qualification',
        'hourly_rate_individual',
        'hourly_rate_group',
        'currency',
        'available_days',
        'available_time_start',
        'available_time_end',
        'bio_ar',
        'bio_en',
        'achievements',
        'status',
        'approval_status',
        'approved_at',
        'approved_by',
        'rating',
        'total_reviews',
        'total_sessions',
        'total_students',
        'created_by',
        'updated_by',
        'notes'
    ];

    protected $casts = [
        'has_ijazah' => 'boolean',
        'teaching_experience_years' => 'integer',
        'hourly_rate_individual' => 'decimal:2',
        'hourly_rate_group' => 'decimal:2',
        'available_days' => 'array',
        'available_time_start' => 'string',
        'available_time_end' => 'string',
        'achievements' => 'array',
        'rating' => 'decimal:1',
        'total_reviews' => 'integer',
        'total_sessions' => 'integer',
        'total_students' => 'integer',
        'approved_at' => 'datetime'
    ];

    // Constants
    const EDUCATIONAL_QUALIFICATIONS = [
        'bachelor' => 'بكالوريوس',
        'master' => 'ماجستير',
        'phd' => 'دكتوراه',
        'other' => 'أخرى'
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(QuranSubscription::class);
    }

    public function circles(): HasMany
    {
        return $this->hasMany(QuranCircle::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class);
    }

    public function homework(): HasMany
    {
        return $this->hasMany(QuranHomework::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(QuranProgress::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where('approval_status', 'approved');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeWithIjazah($query)
    {
        return $query->where('has_ijazah', true);
    }

    public function scopeBySpecialization($query, $specialization)
    {
        return $query->where('specialization', $specialization);
    }

    public function scopeAvailableForCircles($query)
    {
        return $query->where('status', 'active')
                    ->where('approval_status', 'approved')
                    ->where('max_students_per_circle', '>', 1);
    }

    public function scopeByRating($query, $minRating = 4.0)
    {
        return $query->where('rating', '>=', $minRating);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name) ?: 'Unknown Teacher';
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'suspended' => 'معلق',
            'pending' => 'في الانتظار'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getApprovalStatusTextAttribute(): string
    {
        $statuses = [
            'pending' => 'في انتظار الموافقة',
            'approved' => 'تم الاعتماد',
            'rejected' => 'مرفوض',
            'under_review' => 'قيد المراجعة'
        ];

        return $statuses[$this->approval_status] ?? $this->approval_status;
    }

    public function getSpecializationTextAttribute(): string
    {
        $specializations = [
            'memorization' => 'حفظ القرآن',
            'recitation' => 'تلاوة وتجويد',
            'interpretation' => 'تفسير القرآن',
            'arabic_language' => 'اللغة العربية القرآنية',
            'general' => 'عام'
        ];

        return $specializations[$this->specialization] ?? $this->specialization;
    }

    public function getIjazahTypeTextAttribute(): string
    {
        if (!$this->has_ijazah) {
            return 'لا يوجد إجازة';
        }

        $types = [
            'memorization' => 'إجازة حفظ',
            'recitation' => 'إجازة قراءة',
            'ten_readings' => 'إجازة القراءات العشر',
            'teaching' => 'إجازة تدريس',
            'general' => 'إجازة عامة'
        ];

        return $types[$this->ijazah_type] ?? $this->ijazah_type;
    }

    public function getFormattedRatesAttribute(): array
    {
        return [
            'individual' => number_format($this->hourly_rate_individual, 2) . ' ' . $this->currency,
            'group' => number_format($this->hourly_rate_group, 2) . ' ' . $this->currency
        ];
    }

    public function getAvailabilityTextAttribute(): string
    {
        $days = [
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت'
        ];

        $availableDays = array_map(function($day) use ($days) {
            return $days[$day] ?? $day;
        }, $this->available_days ?? []);

        return implode(', ', $availableDays);
    }

    public function getExperienceLevelAttribute(): string
    {
        $years = $this->teaching_experience_years;
        
        if ($years < 1) return 'مبتدئ';
        if ($years < 3) return 'متوسط الخبرة';
        if ($years < 5) return 'خبير';
        if ($years < 10) return 'خبير متقدم';
        return 'خبير محنك';
    }

    // Methods
    public function approve(User $approver): self
    {
        $this->update([
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approver->id,
            'status' => 'active'
        ]);

        return $this;
    }

    public function reject(User $rejector, ?string $reason = null): self
    {
        $this->update([
            'approval_status' => 'rejected',
            'status' => 'inactive',
            'notes' => $reason
        ]);

        return $this;
    }

    public function suspend(?string $reason = null): self
    {
        $this->update([
            'status' => 'suspended',
            'notes' => $reason
        ]);

        return $this;
    }

    public function activate(): self
    {
        $this->update([
            'status' => 'active'
        ]);

        return $this;
    }

    public function updateRating(): self
    {
        // Calculate average rating from all related entities
        $totalRating = 0;
        $totalReviews = 0;

        // Get ratings from subscriptions
        $subscriptionRatings = $this->subscriptions()
            ->whereNotNull('rating')
            ->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')
            ->first();

        if ($subscriptionRatings && $subscriptionRatings->count > 0) {
            $totalRating += $subscriptionRatings->avg_rating * $subscriptionRatings->count;
            $totalReviews += $subscriptionRatings->count;
        }

        // Get ratings from circles
        $circleRatings = $this->circles()
            ->whereNotNull('avg_rating')
            ->selectRaw('AVG(avg_rating) as avg_rating, COUNT(*) as count')
            ->first();

        if ($circleRatings && $circleRatings->count > 0) {
            $totalRating += $circleRatings->avg_rating * $circleRatings->count;
            $totalReviews += $circleRatings->count;
        }

        $avgRating = $totalReviews > 0 ? $totalRating / $totalReviews : 0;

        $this->update([
            'rating' => round($avgRating, 1),
            'total_reviews' => $totalReviews
        ]);

        return $this;
    }

    public function updateStats(): self
    {
        $totalSessions = $this->sessions()->count();
        $totalStudents = $this->subscriptions()
            ->distinct('student_id')
            ->count('student_id');

        $this->update([
            'total_sessions' => $totalSessions,
            'total_students' => $totalStudents
        ]);

        return $this;
    }

    public function canTeachGradeLevel(string $gradeLevel): bool
    {
        return in_array($gradeLevel, $this->available_grade_levels ?? []);
    }

    public function isAvailableOnDay(string $day): bool
    {
        return in_array($day, $this->available_days ?? []);
    }

    public function isAvailableAtTime(string $time): bool
    {
        return in_array($time, $this->available_times ?? []);
    }

    public function hasTeachingMethod(string $method): bool
    {
        return in_array($method, $this->teaching_methods ?? []);
    }

    public function canAcceptNewStudents(): bool
    {
        return $this->status === 'active' 
            && $this->approval_status === 'approved';
    }

    // Static methods
    public static function createFromRegistration(array $data): self
    {
        return self::create(array_merge($data, [
            'teacher_code' => self::generateTeacherCode($data['academy_id']),
            'status' => 'inactive',
            'approval_status' => 'pending',
            'rating' => 0,
            'total_reviews' => 0,
            'total_sessions' => 0,
            'total_students' => 0
        ]));
    }

    private static function generateTeacherCode(int $academyId): string
    {
        $count = self::where('academy_id', $academyId)->count() + 1;
        return 'QT-' . $academyId . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public static function getAvailableTeachers(int $academyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('academy_id', $academyId)
            ->active()
            ->with(['user', 'academy']);

        if (isset($filters['specialization'])) {
            $query->where('specialization', $filters['specialization']);
        }

        if (isset($filters['has_ijazah']) && $filters['has_ijazah']) {
            $query->where('has_ijazah', true);
        }

        if (isset($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        if (isset($filters['grade_level'])) {
            $query->whereJsonContains('available_grade_levels', $filters['grade_level']);
        }

        if (isset($filters['day'])) {
            $query->whereJsonContains('available_days', $filters['day']);
        }

        return $query->orderBy('rating', 'desc')
                    ->orderBy('total_students', 'desc')
                    ->get();
    }
} 