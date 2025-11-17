<?php

namespace App\Models;

use App\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuranTrialRequest extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'student_id',
        'teacher_id',
        'request_code',
        'student_name',
        'student_age',
        'phone',
        'email',
        'current_level',
        'learning_goals',
        'preferred_time',
        'notes',
        'status',
        // Removed: scheduled_at, meeting_link, meeting_password
        // These are now managed via QuranSession and BaseSessionMeeting
        'trial_session_id',
        'completed_at',
        'rating',
        'feedback',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'learning_goals' => 'array',
        'student_age' => 'integer',
        // Removed: scheduled_at - now in QuranSession
        'completed_at' => 'datetime',
        'rating' => 'integer',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_NO_SHOW = 'no_show';

    const STATUSES = [
        self::STATUS_PENDING => 'في الانتظار',
        self::STATUS_APPROVED => 'موافق عليه',
        self::STATUS_REJECTED => 'مرفوض',
        self::STATUS_SCHEDULED => 'مجدول',
        self::STATUS_COMPLETED => 'مكتمل',
        self::STATUS_CANCELLED => 'ملغي',
        self::STATUS_NO_SHOW => 'غياب',
    ];

    // Learning level constants
    const LEVEL_BEGINNER = 'beginner';

    const LEVEL_ELEMENTARY = 'elementary';

    const LEVEL_INTERMEDIATE = 'intermediate';

    const LEVEL_ADVANCED = 'advanced';

    const LEVEL_EXPERT = 'expert';

    const LEVEL_HAFIZ = 'hafiz';

    const LEVELS = [
        self::LEVEL_BEGINNER => 'مبتدئ (لا أعرف القراءة)',
        self::LEVEL_ELEMENTARY => 'أساسي (أقرأ ببطء)',
        self::LEVEL_INTERMEDIATE => 'متوسط (أقرأ بطلاقة)',
        self::LEVEL_ADVANCED => 'متقدم (أحفظ أجزاء من القرآن)',
        self::LEVEL_EXPERT => 'متمكن (أحفظ أكثر من 10 أجزاء)',
        self::LEVEL_HAFIZ => 'حافظ (أحفظ القرآن كاملاً)',
    ];

    // Preferred time constants
    const TIME_MORNING = 'morning';

    const TIME_AFTERNOON = 'afternoon';

    const TIME_EVENING = 'evening';

    const TIMES = [
        self::TIME_MORNING => 'صباحاً (6:00 - 12:00)',
        self::TIME_AFTERNOON => 'بعد الظهر (12:00 - 18:00)',
        self::TIME_EVENING => 'مساءً (18:00 - 22:00)',
    ];

    /**
     * Generate a unique request code for the academy
     */
    public static function generateRequestCode($academyId)
    {
        $academyId = $academyId ?: 1;
        $prefix = 'QTR-'.str_pad($academyId, 2, '0', STR_PAD_LEFT).'-';

        // Get the highest existing sequence number for this academy
        // Include soft-deleted records to avoid code duplication
        $maxNumber = static::withTrashed()
            ->where('academy_id', $academyId)
            ->where('request_code', 'LIKE', $prefix.'%')
            ->selectRaw('MAX(CAST(SUBSTRING(request_code, -6) AS UNSIGNED)) as max_num')
            ->value('max_num') ?: 0;

        $nextNumber = $maxNumber + 1;

        return $prefix.str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Boot method to auto-generate request code
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->request_code)) {
                $model->request_code = static::generateRequestCode($model->academy_id);
            }
        });
    }

    /**
     * Academy relationship path for trait
     */
    protected static function getAcademyRelationshipPath(): string
    {
        return 'academy';
    }

    /**
     * Relationships
     */
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacherProfile::class, 'teacher_id');
    }

    public function trialSession(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'trial_session_id');
    }

    /**
     * Get all trial sessions created from this request
     * Note: Usually only one, but relationship handles edge cases
     */
    public function trialSessions()
    {
        return $this->hasMany(QuranSession::class, 'trial_request_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Helper methods
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->current_level] ?? $this->current_level;
    }

    public function getTimeLabelAttribute(): string
    {
        if (! $this->preferred_time) {
            return 'غير محدد';
        }

        return self::TIMES[$this->preferred_time] ?? $this->preferred_time;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeScheduled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    /**
     * Actions
     */
    public function approve(): bool
    {
        return $this->update([
            'status' => self::STATUS_APPROVED,
        ]);
    }

    public function reject(): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
        ]);
    }

    /**
     * Mark trial request as scheduled
     * Note: Scheduling details are now stored in QuranSession, not here
     */
    public function schedule(): bool
    {
        return $this->update([
            'status' => self::STATUS_SCHEDULED,
        ]);
    }

    public function complete(?int $rating = null, ?string $feedback = null): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'rating' => $rating,
            'feedback' => $feedback,
        ]);
    }
}
