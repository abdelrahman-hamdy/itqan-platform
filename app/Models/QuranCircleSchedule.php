<?php

namespace App\Models;

use App\Traits\ScopedToAcademy;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class QuranCircleSchedule extends Model
{
    use HasFactory, ScopedToAcademy, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'circle_id',
        'quran_teacher_id',
        'weekly_schedule',
        'timezone',
        'default_duration_minutes',
        'is_active',
        'schedule_starts_at',
        'schedule_ends_at',
        'last_generated_at',
        'generate_ahead_days',
        'generate_before_hours',
        'session_title_template',
        'session_description_template',
        'default_lesson_objectives',
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'recording_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weekly_schedule' => 'array',
        'default_duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'schedule_starts_at' => 'datetime',
        'schedule_ends_at' => 'datetime',
        'last_generated_at' => 'datetime',
        'generate_ahead_days' => 'integer',
        'generate_before_hours' => 'integer',
        'default_lesson_objectives' => 'array',
        'recording_enabled' => 'boolean',
    ];

    // Constants
    const WEEKDAYS = [
        'sunday' => 'الأحد',
        'monday' => 'الاثنين',
        'tuesday' => 'الثلاثاء',
        'wednesday' => 'الأربعاء',
        'thursday' => 'الخميس',
        'friday' => 'الجمعة',
        'saturday' => 'السبت',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function circle(): BelongsTo
    {
        return $this->belongsTo(QuranCircle::class, 'circle_id');
    }

    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quran_teacher_id');
    }

    public function generatedSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'generated_from_schedule_id');
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
        return $query->where('is_active', true);
    }

    public function scopeForTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeReadyForGeneration($query)
    {
        return $query->where('is_active', true)
            ->where('schedule_starts_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('schedule_ends_at')
                    ->orWhere('schedule_ends_at', '>=', now());
            });
    }

    // Methods
    public function generateUpcomingSessions(): int
    {
        if (! $this->is_active) {
            return 0;
        }

        $generatedCount = 0;

        // Fix start date logic - start from now if last generated is in the future or null
        $startDate = now();
        if ($this->last_generated_at && Carbon::parse($this->last_generated_at)->isPast()) {
            $startDate = Carbon::parse($this->last_generated_at)->addDay();
        }

        // Calculate end date based on circle's schedule period
        $schedulePeriod = $this->circle->schedule_period ?? 'month';
        $daysToGenerate = match ($schedulePeriod) {
            'week' => 7,
            'month' => 30,
            'two_months' => 60,
            default => 30
        };

        $endDate = $startDate->copy()->addDays($daysToGenerate);

        // Don't generate beyond schedule end date if set
        if ($this->schedule_ends_at) {
            $endDate = $endDate->min(Carbon::parse($this->schedule_ends_at));
        }

        // Allow flexible session generation - teachers can schedule additional sessions as needed
        // We'll generate sessions for the time period without strict monthly limits
        // This supports cases where teachers need to add extra sessions beyond the standard monthly count

        // Set a reasonable maximum to prevent excessive generation (e.g., 3 sessions per week max)
        $maxSessionsPerGeneration = match ($schedulePeriod) {
            'week' => 21, // 3 sessions per day max for a week
            'month' => 90, // 3 sessions per day max for a month
            'two_months' => 180, // 3 sessions per day max for two months
            default => 90
        };

        // Count existing sessions in the generation period to avoid duplicates
        $existingSessionsInPeriod = QuranSession::where('circle_id', $this->circle_id)
            ->whereBetween('scheduled_at', [$startDate, $endDate])
            ->count();

        $sessionsToGenerate = min($maxSessionsPerGeneration, $maxSessionsPerGeneration - $existingSessionsInPeriod);

        if ($sessionsToGenerate <= 0) {
            return 0; // No sessions to generate in this period
        }

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate && $generatedCount < $sessionsToGenerate) {
            if ($this->shouldGenerateSessionForDate($currentDate)) {
                $sessionTime = $this->getSessionTimeForDate($currentDate);

                if ($sessionTime) {
                    $this->createSessionForDateTime($currentDate->copy()->setTimeFromTimeString($sessionTime));
                    $generatedCount++;
                }
            }

            $currentDate->addDay();
        }

        if ($generatedCount > 0) {
            $this->update(['last_generated_at' => now()]);
        }

        return $generatedCount;
    }

    private function shouldGenerateSessionForDate(Carbon $date): bool
    {
        $dayName = strtolower($date->format('l'));

        foreach ($this->weekly_schedule as $schedule) {
            if (isset($schedule['day']) && $schedule['day'] === $dayName) {
                return true;
            }
        }

        return false;
    }

    private function getSessionTimeForDate(Carbon $date): ?string
    {
        $dayName = strtolower($date->format('l'));

        foreach ($this->weekly_schedule as $schedule) {
            if (isset($schedule['day']) && $schedule['day'] === $dayName) {
                return $schedule['time'] ?? null;
            }
        }

        return null;
    }

    private function createSessionForDateTime(Carbon $datetime): QuranSession
    {
        // Check if session already exists to prevent duplicates
        $existing = QuranSession::where([
            'circle_id' => $this->circle_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'scheduled_at' => $datetime,
        ])->first();

        if ($existing) {
            return $existing;
        }

        // CRITICAL FIX: Load circle relationship if not already loaded to get correct duration
        if (!$this->relationLoaded('circle')) {
            $this->load('circle');
        }

        // Get duration from circle, fallback to schedule default
        // Group circles should ALWAYS use circle's session_duration_minutes
        $duration = $this->circle->session_duration_minutes ?? $this->default_duration_minutes ?? 60;

        return QuranSession::create([
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'circle_id' => $this->circle_id,
            'generated_from_schedule_id' => $this->id,
            'session_code' => $this->generateSessionCode($datetime),
            'session_type' => 'group',
            'status' => 'scheduled',
            'is_template' => false,
            'is_scheduled' => true,
            'title' => $this->generateSessionTitle($datetime),
            'description' => $this->generateSessionDescription($datetime),
            'lesson_objectives' => $this->default_lesson_objectives,
            'scheduled_at' => $datetime,
            'duration_minutes' => $duration,
            'meeting_link' => $this->meeting_link,
            'meeting_id' => $this->meeting_id,
            'meeting_password' => $this->meeting_password,
            'recording_enabled' => $this->recording_enabled,
            'created_by' => $this->created_by,
            'scheduled_by' => $this->created_by,
            'teacher_scheduled_at' => now(),
            'location_type' => 'online',
        ]);
    }

    private function generateSessionCode(Carbon $datetime): string
    {
        $circleCode = $this->circle->circle_code ?? 'QC';
        $dateCode = $datetime->format('Ymd-Hi');

        return "{$circleCode}-{$dateCode}";
    }

    private function generateSessionTitle(Carbon $datetime): string
    {
        if ($this->session_title_template) {
            return str_replace([
                '{circle_name}',
                '{date}',
                '{time}',
                '{day}',
            ], [
                $this->circle->name_ar ?? 'الحلقة',
                $datetime->format('Y-m-d'),
                $datetime->format('H:i'),
                self::WEEKDAYS[strtolower($datetime->format('l'))] ?? $datetime->format('l'),
            ], $this->session_title_template);
        }

        $dayName = self::WEEKDAYS[strtolower($datetime->format('l'))] ?? $datetime->format('l');
        $circleName = $this->circle->name_ar ?? 'الحلقة';

        return "{$circleName} - {$dayName} {$datetime->format('H:i')}";
    }

    private function generateSessionDescription(Carbon $datetime): string
    {
        if ($this->session_description_template) {
            return str_replace([
                '{circle_name}',
                '{date}',
                '{time}',
                '{day}',
                '{teacher_name}',
            ], [
                $this->circle->name_ar ?? 'الحلقة',
                $datetime->format('Y-m-d'),
                $datetime->format('H:i'),
                self::WEEKDAYS[strtolower($datetime->format('l'))] ?? $datetime->format('l'),
                $this->quranTeacher->name ?? 'المعلم',
            ], $this->session_description_template);
        }

        return 'جلسة حلقة القرآن المجدولة تلقائياً';
    }

    public function activateSchedule(): int
    {
        // Update circle status and enrollment when schedule is activated
        $updated = $this->update(['is_active' => true]);

        if ($updated) {
            $this->circle->update([
                'status' => 'active',
                'enrollment_status' => 'open',
                'schedule_configured' => true,
                'schedule_configured_at' => now(),
                'schedule_configured_by' => $this->quran_teacher_id,
            ]);

            // Generate initial sessions and return count
            return $this->generateUpcomingSessions();
        }

        return 0;
    }

    public function deactivateSchedule(): bool
    {
        $updated = $this->update(['is_active' => false]);

        if ($updated) {
            $this->circle->update([
                'status' => 'inactive',
                'enrollment_status' => 'closed',
                'schedule_configured' => false,
            ]);

            // Cancel future generated sessions
            $this->generatedSessions()
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>', now())
                ->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => 'تم إلغاء الجدول الزمني للحلقة',
                    'cancelled_by' => Auth::id(),
                    'cancelled_at' => now(),
                ]);
        }

        return $updated;
    }

    public function getUpcomingSessionsForRange(Carbon $start, Carbon $end): array
    {
        $sessions = [];
        $currentDate = $start->copy();

        while ($currentDate <= $end) {
            if ($this->shouldGenerateSessionForDate($currentDate)) {
                $sessionTime = $this->getSessionTimeForDate($currentDate);

                if ($sessionTime) {
                    $datetime = $currentDate->copy()->setTimeFromTimeString($sessionTime);
                    $sessions[] = [
                        'date' => $datetime->format('Y-m-d'),
                        'time' => $datetime->format('H:i'),
                        'datetime' => $datetime,
                        'title' => $this->generateSessionTitle($datetime),
                        'duration' => $this->default_duration_minutes,
                    ];
                }
            }

            $currentDate->addDay();
        }

        return $sessions;
    }

    public function validateSchedule(): array
    {
        $errors = [];

        if (empty($this->weekly_schedule)) {
            $errors[] = 'الجدول الأسبوعي مطلوب';
        } else {
            foreach ($this->weekly_schedule as $index => $schedule) {
                if (empty($schedule['day'])) {
                    $errors[] = 'اليوم مطلوب في الجدول رقم '.($index + 1);
                }

                if (empty($schedule['time'])) {
                    $errors[] = 'الوقت مطلوب في الجدول رقم '.($index + 1);
                }

                if (! in_array($schedule['day'], array_keys(self::WEEKDAYS))) {
                    $errors[] = 'يوم غير صحيح في الجدول رقم '.($index + 1);
                }
            }
        }

        if (! $this->schedule_starts_at) {
            $errors[] = 'تاريخ بداية الجدول مطلوب';
        }

        if ($this->schedule_ends_at && $this->schedule_ends_at <= $this->schedule_starts_at) {
            $errors[] = 'تاريخ نهاية الجدول يجب أن يكون بعد تاريخ البداية';
        }

        return $errors;
    }

    // Boot method
    protected static function booted()
    {
        static::creating(function ($schedule) {
            if (! isset($schedule->generate_ahead_days)) {
                $schedule->generate_ahead_days = 30;
            }

            if (! isset($schedule->generate_before_hours)) {
                $schedule->generate_before_hours = 1;
            }

            if (! isset($schedule->default_duration_minutes)) {
                $schedule->default_duration_minutes = 60;
            }
        });
    }
}
