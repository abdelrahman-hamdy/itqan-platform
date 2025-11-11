<?php

namespace App\Models;

use App\Traits\ScopedToAcademy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'current_surah',
        'current_verse',
        'current_page',
        'current_face',
        'verses_memorized',
        'papers_memorized',
        'papers_memorized_precise',
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
        'notes',

        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'total_sessions' => 'integer',
        'sessions_scheduled' => 'integer',
        'sessions_completed' => 'integer',
        'sessions_remaining' => 'integer',
        'current_surah' => 'integer',
        'current_verse' => 'integer',
        'current_page' => 'integer',
        'current_face' => 'integer',
        'verses_memorized' => 'integer',
        'papers_memorized' => 'integer',
        'papers_memorized_precise' => 'decimal:2',
        'progress_percentage' => 'decimal:2',
        'default_duration_minutes' => 'integer',
        'preferred_times' => 'array',
        'recording_enabled' => 'boolean',
        'materials_used' => 'array',
        'learning_objectives' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_session_at' => 'datetime',
    ];

    // Constants
    const SPECIALIZATIONS = [
        'memorization' => 'الحفظ',
        'recitation' => 'التلاوة',
        'interpretation' => 'التفسير',
        'arabic_language' => 'اللغة العربية',
        'complete' => 'متكامل',
    ];

    const MEMORIZATION_LEVELS = [
        'beginner' => 'مبتدئ',
        'elementary' => 'ابتدائي',
        'intermediate' => 'متوسط',
        'advanced' => 'متقدم',
        'expert' => 'خبير',
    ];

    const STATUSES = [
        'pending' => 'في الانتظار',
        'active' => 'نشط',
        'completed' => 'مكتمل',
        'suspended' => 'معلق',
        'cancelled' => 'ملغي',
    ];

    // Relationships
    public function academy(): BelongsTo
    {
        return $this->belongsTo(Academy::class);
    }

    public function quranTeacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quran_teacher_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(QuranSubscription::class, 'subscription_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'individual_circle_id');
    }

    public function scheduledSessions(): HasMany
    {
        return $this->sessions()->whereIn('status', ['scheduled', 'in_progress']);
    }

    public function completedSessions(): HasMany
    {
        return $this->sessions()->whereIn('status', ['completed', 'absent']);
    }

    public function templateSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'individual_circle_id');
    }

    public function homework(): HasMany
    {
        return $this->hasMany(QuranHomework::class, 'circle_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(QuranProgress::class, 'circle_id');
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
        return $query->where('status', 'active');
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
        return $query->where('progress_percentage', '>', 0);
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

    public function createTemplateSessions(): void
    {
        // Only create if no sessions exist yet (prevent duplication)
        if ($this->sessions()->count() > 0) {
            return;
        }

        // Create all sessions as unscheduled when circle is created
        for ($i = 1; $i <= $this->total_sessions; $i++) {
            QuranSession::create([
                'academy_id' => $this->academy_id,
                'quran_teacher_id' => $this->quran_teacher_id,
                'individual_circle_id' => $this->id,
                'student_id' => $this->student_id,
                'session_code' => $this->generateSessionCode($i),
                'session_type' => 'individual',
                'session_sequence' => $i,
                'status' => 'unscheduled', // Changed from 'template'
                'is_template' => false, // Changed from true
                'is_scheduled' => false,
                'title' => "الجلسة رقم {$i}",
                'description' => "جلسة فردية للقرآن الكريم - الحلقة {$i}",
                'duration_minutes' => $this->default_duration_minutes,
                'created_by' => $this->created_by,
            ]);
        }

        $this->updateSessionCounts();
    }

    private function generateSessionCode(int $sequence): string
    {
        $baseCode = "{$this->circle_code}-S{$sequence}";

        // Check if this session code already exists for this academy (including soft deleted)
        $attempt = 0;
        $sessionCode = $baseCode;
        while (
            QuranSession::withTrashed()
                ->where('academy_id', $this->academy_id)
                ->where('session_code', $sessionCode)
                ->exists() && $attempt < 10
        ) {
            $attempt++;
            // Add timestamp suffix if collision occurs for better uniqueness
            $sessionCode = $baseCode.'-'.now()->format('His')."-A{$attempt}";
        }

        return $sessionCode;
    }

    public function updateSessionCounts(): void
    {
        $scheduled = $this->scheduledSessions()->count();
        $completed = $this->completedSessions()->count();
        $unscheduled = $this->sessions()->where('status', 'unscheduled')->count();

        $this->update([
            'sessions_scheduled' => $scheduled,
            'sessions_completed' => $completed,
            'sessions_remaining' => $unscheduled, // Sessions that can still be scheduled
        ]);
    }

    public function updateProgress(): void
    {
        $completedSessions = $this->completedSessions()->count();
        $progressPercentage = $this->total_sessions > 0
            ? ($completedSessions / $this->total_sessions) * 100
            : 0;

        $this->update([
            'progress_percentage' => round($progressPercentage, 2),
            'last_session_at' => $this->completedSessions()->latest('ended_at')->first()?->ended_at,
        ]);

        // Update status based on progress
        if ($completedSessions >= $this->total_sessions) {
            $this->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        } elseif ($completedSessions > 0 && $this->status === 'pending') {
            $this->update([
                'status' => 'active',
                'started_at' => $this->completedSessions()->oldest('started_at')->first()?->started_at,
            ]);
        }
    }

    public function canScheduleSession(): bool
    {
        return $this->status === 'active' &&
               $this->sessions_remaining > 0 &&
               $this->templateSessions()->whereNull('scheduled_at')->exists();
    }

    public function getNextTemplateSession(): ?QuranSession
    {
        return $this->templateSessions()
            ->whereNull('scheduled_at')
            ->orderBy('id')
            ->first();
    }

    public function getAvailableSessionsCount(): int
    {
        return $this->templateSessions()->whereNull('scheduled_at')->count();
    }

    // Paper-based helper methods

    /**
     * Convert verses to papers (وجه)
     * Each paper (وجه) = approximately 15 verses
     */
    public function convertVersesToPapers(int $verses): float
    {
        return round($verses / 15, 2);
    }

    /**
     * Convert papers to verses
     * Each paper (وجه) = approximately 15 verses
     */
    public function convertPapersToVerses(float $papers): int
    {
        return (int) round($papers * 15);
    }

    /**
     * Get current position in paper format
     */
    public function getCurrentPaperPosition(): array
    {
        return [
            'page' => $this->current_page,
            'face' => $this->current_face,
            'papers_count' => $this->papers_memorized,
            'papers_precise' => $this->papers_memorized_precise,
        ];
    }

    /**
     * Update progress using paper count
     */
    public function updateProgressByPapers(float $papersMemorized): void
    {
        $this->update([
            'papers_memorized' => (int) floor($papersMemorized),
            'papers_memorized_precise' => $papersMemorized,
            'verses_memorized' => $this->convertPapersToVerses($papersMemorized),
        ]);
    }

    /**
     * Get progress summary in Arabic using papers
     */
    public function getProgressSummaryInPapers(): string
    {
        if (! $this->current_page || ! $this->current_face) {
            return 'لم يتم تحديد التقدم';
        }

        $papersCount = $this->papers_memorized_precise ?? $this->papers_memorized;
        $faceName = $this->current_face == 1 ? 'الوجه الأول' : 'الوجه الثاني';

        return "الصفحة {$this->current_page} - {$faceName} ({$papersCount} وجه محفوظ)";
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

        static::created(function ($circle) {
            // Create template sessions after circle is created
            $circle->createTemplateSessions();
        });

        static::updating(function ($circle) {
            if ($circle->isDirty(['sessions_completed', 'total_sessions'])) {
                $circle->sessions_remaining = $circle->total_sessions - $circle->sessions_completed;
            }

            // Auto-sync paper and verse fields when one changes
            if ($circle->isDirty('papers_memorized_precise') && ! $circle->isDirty('verses_memorized')) {
                $circle->verses_memorized = $circle->convertPapersToVerses($circle->papers_memorized_precise);
            } elseif ($circle->isDirty('verses_memorized') && ! $circle->isDirty('papers_memorized_precise')) {
                $circle->papers_memorized_precise = $circle->convertVersesToPapers($circle->verses_memorized);
                $circle->papers_memorized = (int) floor($circle->papers_memorized_precise);
            }
        });
    }
}
