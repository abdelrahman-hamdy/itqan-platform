<?php

namespace App\Models;

use Exception;
use Log;
use App\Enums\SessionStatus;
use App\Models\Traits\ScopedToAcademy;
use App\Services\AcademyContextService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionSchedule extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'quran_teacher_id',
        'quran_subscription_id',
        'quran_circle_id',
        'schedule_code',
        'schedule_type',
        'title',
        'description',
        'recurrence_pattern',
        'schedule_data',
        'session_templates',
        'start_date',
        'end_date',
        'max_sessions',
        'status',
        'auto_generate',
        'allow_rescheduling',
        'reschedule_hours_notice',
        'sessions_generated',
        'sessions_completed',
        'sessions_cancelled',
        'last_generated_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'schedule_data' => 'array',
        'session_templates' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'last_generated_at' => 'datetime',
        'auto_generate' => 'boolean',
        'allow_rescheduling' => 'boolean',
        'max_sessions' => 'integer',
        'sessions_generated' => 'integer',
        'sessions_completed' => 'integer',
        'sessions_cancelled' => 'integer',
        'reschedule_hours_notice' => 'integer',
    ];

    // Constants
    const STATUS_ACTIVE = 'active';

    const STATUS_PAUSED = 'paused';

    const STATUS_COMPLETED = 'completed';

    const STATUS_CANCELLED = 'cancelled';

    const TYPE_SUBSCRIPTION = 'subscription';

    const TYPE_CIRCLE = 'circle';

    const TYPE_COURSE = 'course';

    const PATTERN_WEEKLY = 'weekly';

    const PATTERN_BI_WEEKLY = 'bi-weekly';

    const PATTERN_MONTHLY = 'monthly';

    const PATTERN_CUSTOM = 'custom';

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(QuranTeacherProfile::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class, 'quran_subscription_id');
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'quran_circle_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class);
    }

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
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeAutoGenerate($query)
    {
        return $query->where('auto_generate', true);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeForAcademy($query, $academyId)
    {
        return $query->where('academy_id', $academyId);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('schedule_type', $type);
    }

    public function scopeNeedingGeneration($query)
    {
        return $query->active()
            ->autoGenerate()
            ->where(function ($q) {
                $q->whereNull('last_generated_at')
                    ->orWhere('last_generated_at', '<', now()->subDays(7));
            });
    }

    // Accessors
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'نشط',
            self::STATUS_PAUSED => 'متوقف مؤقتاً',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_CANCELLED => 'ملغي',
            default => $this->status
        };
    }

    public function getTypeTextAttribute(): string
    {
        return match ($this->schedule_type) {
            self::TYPE_SUBSCRIPTION => 'اشتراك فردي',
            self::TYPE_CIRCLE => 'حلقة جماعية',
            self::TYPE_COURSE => 'دورة تعليمية',
            default => $this->schedule_type
        };
    }

    public function getPatternTextAttribute(): string
    {
        return match ($this->recurrence_pattern) {
            self::PATTERN_WEEKLY => 'أسبوعي',
            self::PATTERN_BI_WEEKLY => 'كل أسبوعين',
            self::PATTERN_MONTHLY => 'شهري',
            self::PATTERN_CUSTOM => 'مخصص',
            default => $this->recurrence_pattern
        };
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->max_sessions && $this->max_sessions > 0) {
            return round(($this->sessions_completed / $this->max_sessions) * 100, 2);
        }

        // For schedules without max sessions, calculate based on date range
        if ($this->end_date) {
            $totalDays = $this->start_date->diffInDays($this->end_date);
            $passedDays = $this->start_date->diffInDays(today());

            return $totalDays > 0 ? round(($passedDays / $totalDays) * 100, 2) : 0;
        }

        return 0;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED ||
               ($this->max_sessions && $this->sessions_completed >= $this->max_sessions) ||
               ($this->end_date && $this->end_date->isPast());
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE && ! $this->is_completed;
    }

    public function getNextSessionDateAttribute(): ?Carbon
    {
        if (! $this->is_active) {
            return null;
        }

        $now = AcademyContextService::nowInAcademyTimezone();
        $nextSession = $this->sessions()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', $now)
            ->orderBy('scheduled_at')
            ->first();

        return $nextSession?->scheduled_at;
    }

    public function getRemainingSessionsAttribute(): int
    {
        if (! $this->max_sessions) {
            return 0;
        }

        return max(0, $this->max_sessions - $this->sessions_completed);
    }

    // Methods
    public function generateSessions(int $weeks = 4): int
    {
        if (! $this->auto_generate || ! $this->is_active) {
            return 0;
        }

        $generatedCount = 0;
        $startDate = $this->getNextGenerationDate();
        $endDate = min(
            $startDate->copy()->addWeeks($weeks),
            $this->end_date ?? $startDate->copy()->addYear()
        );

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            foreach ($this->session_templates as $template) {
                if ($this->shouldCreateSessionOnDate($currentDate, $template)) {
                    if ($this->createSessionFromTemplate($currentDate, $template)) {
                        $generatedCount++;
                    }
                }
            }
            $currentDate->addDay();
        }

        $this->update([
            'sessions_generated' => $this->sessions_generated + $generatedCount,
            'last_generated_at' => now(),
        ]);

        return $generatedCount;
    }

    private function getNextGenerationDate(): Carbon
    {
        // Start from last generated date or schedule start date
        $baseDate = $this->last_generated_at ?
            $this->last_generated_at->startOfDay() :
            $this->start_date;

        // Don't generate sessions in the past
        return $baseDate->max(today());
    }

    private function shouldCreateSessionOnDate(Carbon $date, array $template): bool
    {
        // Check if this day matches the template
        $dayName = strtolower($date->format('l'));
        if ($template['day_of_week'] !== $dayName) {
            return false;
        }

        // Check if session already exists
        $sessionTime = $date->copy()->setTimeFromTimeString($template['start_time']);
        $existingSession = $this->sessions()
            ->where('scheduled_at', $sessionTime)
            ->exists();

        if ($existingSession) {
            return false;
        }

        // Check max sessions limit
        if ($this->max_sessions && $this->sessions_generated >= $this->max_sessions) {
            return false;
        }

        // Check subscription remaining sessions
        if ($this->subscription && $this->subscription->sessions_remaining <= 0) {
            return false;
        }

        return true;
    }

    private function createSessionFromTemplate(Carbon $date, array $template): bool
    {
        try {
            $sessionTime = $date->copy()->setTimeFromTimeString($template['start_time']);

            $sessionData = [
                'academy_id' => $this->academy_id,
                'quran_teacher_id' => $this->quran_teacher_id,
                'session_schedule_id' => $this->id,
                'session_code' => $this->generateSessionCode(),
                'status' => 'scheduled',
                'is_auto_generated' => true,
                'scheduled_at' => $sessionTime,
                'duration_minutes' => $template['duration_minutes'],
            ];

            // Add type-specific data
            if ($this->schedule_type === self::TYPE_SUBSCRIPTION) {
                $sessionData['quran_subscription_id'] = $this->quran_subscription_id;
                $sessionData['student_id'] = $this->subscription->student_id;
                $sessionData['session_type'] = 'individual';
                $sessionData['title'] = "جلسة قرآن - {$this->subscription->student->name}";
            } elseif ($this->schedule_type === self::TYPE_CIRCLE) {
                $sessionData['quran_circle_id'] = $this->quran_circle_id;
                $sessionData['session_type'] = 'group';
                $sessionData['title'] = "حلقة {$this->circle->name}";
            }

            QuranSession::create($sessionData);

            // Update subscription remaining sessions
            if ($this->subscription) {
                $this->subscription->decrement('sessions_remaining');
            }

            return true;

        } catch (Exception $e) {
            Log::error('Failed to create session from template', [
                'schedule_id' => $this->id,
                'date' => $date->toDateString(),
                'template' => $template,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function generateSessionCode(): string
    {
        $prefix = match ($this->schedule_type) {
            self::TYPE_SUBSCRIPTION => 'SUB',
            self::TYPE_CIRCLE => 'CIR',
            self::TYPE_COURSE => 'COU',
            default => 'SES'
        };

        return $prefix.'-'.$this->id.'-'.uniqid();
    }

    public function pause(): self
    {
        $this->update(['status' => self::STATUS_PAUSED]);

        return $this;
    }

    public function resume(): self
    {
        $this->update(['status' => self::STATUS_ACTIVE]);

        return $this;
    }

    public function complete(): self
    {
        $this->update(['status' => self::STATUS_COMPLETED]);

        return $this;
    }

    public function cancel(): self
    {
        $this->update(['status' => self::STATUS_CANCELLED]);

        // Cancel all future sessions (using academy timezone)
        $now = AcademyContextService::nowInAcademyTimezone();
        $this->sessions()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', $now)
            ->update(['status' => 'cancelled']);

        return $this;
    }

    public function updateProgress(): self
    {
        $completed = $this->sessions()->where('status', 'completed')->count();
        $cancelled = $this->sessions()->where('status', 'cancelled')->count();

        $this->update([
            'sessions_completed' => $completed,
            'sessions_cancelled' => $cancelled,
        ]);

        // Auto-complete if max sessions reached or end date passed
        if ($this->is_completed && $this->status === self::STATUS_ACTIVE) {
            $this->complete();
        }

        return $this;
    }

    /**
     * Get schedule statistics
     */
    public function getStats(): array
    {
        return [
            'total_generated' => $this->sessions_generated,
            'completed' => $this->sessions_completed,
            'cancelled' => $this->sessions_cancelled,
            'pending' => $this->sessions()->where('status', SessionStatus::SCHEDULED->value)->count(),
            'progress_percentage' => $this->progress_percentage,
            'remaining_sessions' => $this->remaining_sessions,
            'next_session' => $this->next_session_date?->format('Y-m-d H:i'),
        ];
    }
}
