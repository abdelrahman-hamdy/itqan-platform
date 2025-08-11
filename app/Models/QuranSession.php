<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QuranSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'academy_id',
        'quran_teacher_id',
        'quran_subscription_id',
        'circle_id',
        'individual_circle_id',
        'student_id',
        'trial_request_id',
        'session_code',
        'session_type',
        'status',
        'title',
        'description',
        'lesson_objectives',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_minutes',
        'actual_duration_minutes',
        'location_type',
        'location_details',
        'meeting_link',
        'meeting_id',
        'meeting_password',
        'recording_url',
        'recording_enabled',
        'attendance_status',
        'participants_count',
        'attendance_notes',
        'current_surah',
        'current_verse',
        'verses_covered_start',
        'verses_covered_end',
        'verses_memorized_today',
        'recitation_quality',
        'tajweed_accuracy',
        'mistakes_count',
        'common_mistakes',
        'areas_for_improvement',
        'homework_assigned',
        'homework_details',
        'next_session_plan',
        'session_notes',
        'teacher_feedback',
        'student_feedback',
        'parent_feedback',
        'overall_rating',
        'technical_issues',
        'makeup_session_for',
        'is_makeup_session',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at',
        'reschedule_reason',
        'rescheduled_from',
        'rescheduled_to',
        'materials_used',
        'learning_outcomes',
        'assessment_results',
        'follow_up_required',
        'follow_up_notes',
        'created_by',
        'updated_by',
        // New fields for individual circles and templates
        'individual_circle_id',

        'teacher_scheduled_at',
        'scheduled_by',
        // New meeting platform fields
        'meeting_source',
        'meeting_platform',
        'meeting_data',
        'meeting_room_name',
        'meeting_auto_generated',
        'meeting_expires_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'rescheduled_from' => 'datetime',
        'rescheduled_to' => 'datetime',
        'teacher_scheduled_at' => 'datetime',
        'meeting_expires_at' => 'datetime',
        'duration_minutes' => 'integer',
        'actual_duration_minutes' => 'integer',
        'participants_count' => 'integer',
        'current_surah' => 'integer',
        'current_verse' => 'integer',
        'verses_covered_start' => 'integer',
        'verses_covered_end' => 'integer',
        'verses_memorized_today' => 'integer',

        'recitation_quality' => 'decimal:1',
        'tajweed_accuracy' => 'decimal:1',
        'mistakes_count' => 'integer',
        'overall_rating' => 'integer',
        'recording_enabled' => 'boolean',
        'is_makeup_session' => 'boolean',
        'follow_up_required' => 'boolean',

        'meeting_auto_generated' => 'boolean',
        'lesson_objectives' => 'array',
        'common_mistakes' => 'array',
        'areas_for_improvement' => 'array',
        'homework_assigned' => 'array',
        'materials_used' => 'array',
        'learning_outcomes' => 'array',
        'assessment_results' => 'array',
        'meeting_data' => 'array'
    ];

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
        return $this->belongsTo(QuranCircle::class);
    }

    public function individualCircle(): BelongsTo
    {
        return $this->belongsTo(QuranIndividualCircle::class, 'individual_circle_id');
    }

    public function generatedFromSchedule(): BelongsTo
    {
        return $this->belongsTo(QuranCircleSchedule::class, 'generated_from_schedule_id');
    }

    public function scheduledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function trialRequest(): BelongsTo
    {
        return $this->belongsTo(QuranTrialRequest::class, 'trial_request_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function makeupFor(): BelongsTo
    {
        return $this->belongsTo(QuranSession::class, 'makeup_session_for');
    }

    public function makeupSessions(): HasMany
    {
        return $this->hasMany(QuranSession::class, 'makeup_session_for');
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
    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeMissed($query)
    {
        return $query->where('status', 'missed');
    }

    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                    ->where('status', 'scheduled');
    }

    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('scheduled_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    public function scopeBySessionType($query, $type)
    {
        return $query->where('session_type', $type);
    }

    public function scopeIndividual($query)
    {
        return $query->where('session_type', 'individual');
    }

    public function scopeCircle($query)
    {
        return $query->where('session_type', 'circle');
    }

    public function scopeMakeupSessions($query)
    {
        return $query->where('is_makeup_session', true);
    }

    public function scopeRegularSessions($query)
    {
        return $query->where('is_makeup_session', false);
    }

    public function scopeAttended($query)
    {
        return $query->where('attendance_status', 'attended');
    }

    public function scopeAbsent($query)
    {
        return $query->where('attendance_status', 'absent');
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('quran_teacher_id', $teacherId);
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('overall_rating', '>=', $minRating);
    }

    // Accessors
    public function getSessionTypeTextAttribute(): string
    {
        $types = [
            'individual' => 'جلسة فردية',
            'circle' => 'حلقة جماعية',
            'makeup' => 'جلسة تعويضية',
            'trial' => 'جلسة تجريبية',
            'assessment' => 'جلسة تقييم'
        ];

        return $types[$this->session_type] ?? $this->session_type;
    }

    public function getStatusTextAttribute(): string
    {
        $statuses = [
            'scheduled' => 'مجدولة',
            'ongoing' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            'missed' => 'متغيب',
            'rescheduled' => 'تم إعادة جدولتها',
            'pending' => 'في الانتظار'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getAttendanceStatusTextAttribute(): string
    {
        $statuses = [
            'attended' => 'حضر',
            'absent' => 'غائب',
            'late' => 'متأخر',
            'left_early' => 'غادر مبكراً',
            'partial' => 'حضور جزئي'
        ];

        return $statuses[$this->attendance_status] ?? $this->attendance_status;
    }

    public function getLocationTypeTextAttribute(): string
    {
        $types = [
            'online' => 'عبر الإنترنت',
            'physical' => 'حضوري',
            'hybrid' => 'مختلط'
        ];

        return $types[$this->location_type] ?? $this->location_type;
    }

    public function getFormattedScheduledTimeAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('Y-m-d H:i') : 'غير محدد';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('Y-m-d') : 'غير محدد';
    }

    public function getFormattedTimeAttribute(): string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('H:i') : 'غير محدد';
    }

    public function getDurationTextAttribute(): string
    {
        $duration = $this->actual_duration_minutes ?? $this->duration_minutes;
        
        if ($duration < 60) {
            return $duration . ' دقيقة';
        }
        
        $hours = floor($duration / 60);
        $minutes = $duration % 60;
        
        if ($minutes === 0) {
            return $hours . ' ساعة';
        }
        
        return $hours . ' ساعة و ' . $minutes . ' دقيقة';
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isFuture();
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isToday();
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getCanStartAttribute(): bool
    {
        return $this->status === 'scheduled' && 
               $this->scheduled_at &&
               $this->scheduled_at->diffInMinutes(now()) <= 15;
    }

    public function getCanCancelAttribute(): bool
    {
        return in_array($this->status, ['scheduled', 'ongoing']) &&
               $this->scheduled_at &&
               $this->scheduled_at->diffInHours(now()) >= 2;
    }

    public function getCanRescheduleAttribute(): bool
    {
        return $this->status === 'scheduled' &&
               $this->scheduled_at &&
               $this->scheduled_at->diffInHours(now()) >= 24;
    }

    public function getProgressSummaryAttribute(): string
    {
        if (!$this->current_surah || !$this->current_verse) {
            return 'لم يتم تحديد التقدم';
        }

        $surahName = $this->getSurahName($this->current_surah);
        $versesMemorized = $this->verses_memorized_today ?? 0;
        
        $summary = "سورة {$surahName} - آية {$this->current_verse}";
        
        if ($versesMemorized > 0) {
            $summary .= " (حُفظت {$versesMemorized} آيات)";
        }

        return $summary;
    }

    public function getPerformanceSummaryAttribute(): array
    {
        return [
            'recitation_quality' => $this->recitation_quality,
            'tajweed_accuracy' => $this->tajweed_accuracy,
            'mistakes_count' => $this->mistakes_count,
            'overall_rating' => $this->overall_rating,
            'verses_memorized' => $this->verses_memorized_today
        ];
    }

    public function getTimeDurationAttribute(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffInMinutes($endTime);
    }

    // Methods
    public function start(): self
    {
        if ($this->status !== 'scheduled') {
            throw new \Exception('لا يمكن بدء الجلسة. الحالة الحالية: ' . $this->status_text);
        }

        $this->update([
            'status' => 'ongoing',
            'started_at' => now()
        ]);

        return $this;
    }

    public function complete(array $sessionData = []): self
    {
        if (!in_array($this->status, ['ongoing', 'scheduled'])) {
            throw new \Exception('لا يمكن إنهاء الجلسة. الحالة الحالية: ' . $this->status_text);
        }

        $endTime = now();
        $actualDuration = $this->started_at ? $this->started_at->diffInMinutes($endTime) : $this->duration_minutes;

        $updateData = array_merge([
            'status' => 'completed',
            'ended_at' => $endTime,
            'actual_duration_minutes' => $actualDuration,
            'attendance_status' => 'attended'
        ], $sessionData);

        $this->update($updateData);

        // Update subscription session count
        if ($this->subscription) {
            $this->subscription->useSession();
        }

        // Update circle session count
        if ($this->circle) {
            $this->circle->increment('sessions_completed');
        }

        // Create progress record if progress data is provided
        if (isset($sessionData['verses_memorized_today']) && $sessionData['verses_memorized_today'] > 0) {
            $this->recordProgress($sessionData);
        }

        return $this;
    }

    public function cancel(string $reason, ?User $cancelledBy = null): self
    {
        if (!$this->can_cancel) {
            throw new \Exception('لا يمكن إلغاء الجلسة في هذا الوقت');
        }

        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_by' => $cancelledBy?->id,
            'cancelled_at' => now()
        ]);

        return $this;
    }

    public function reschedule(\Carbon\Carbon $newDateTime, string $reason = null): self
    {
        if (!$this->can_reschedule) {
            throw new \Exception('لا يمكن إعادة جدولة الجلسة في هذا الوقت');
        }

        $this->update([
            'rescheduled_from' => $this->scheduled_at,
            'scheduled_at' => $newDateTime,
            'reschedule_reason' => $reason,
            'status' => 'rescheduled'
        ]);

        return $this;
    }

    public function markAsNoShow(): self
    {
        $this->update([
            'status' => 'missed',
            'attendance_status' => 'absent',
            'ended_at' => $this->scheduled_at->addMinutes($this->duration_minutes)
        ]);

        return $this;
    }

    public function createMakeupSession(\Carbon\Carbon $scheduledAt, array $additionalData = []): self
    {
        $makeupData = array_merge([
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'quran_subscription_id' => $this->quran_subscription_id,
            'circle_id' => $this->circle_id,
            'student_id' => $this->student_id,
            'session_code' => self::generateSessionCode($this->academy_id),
            'session_type' => $this->session_type,
            'status' => 'scheduled',
            'title' => 'جلسة تعويضية - ' . $this->title,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $this->duration_minutes,
            'location_type' => $this->location_type,
            'is_makeup_session' => true,
            'makeup_session_for' => $this->id
        ], $additionalData);

        return self::create($makeupData);
    }

    public function recordProgress(array $progressData): QuranProgress
    {
        return QuranProgress::create([
            'academy_id' => $this->academy_id,
            'student_id' => $this->student_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'quran_subscription_id' => $this->quran_subscription_id,
            'circle_id' => $this->circle_id,
            'session_id' => $this->id,
            'progress_date' => now(),
            'current_surah' => $this->current_surah,
            'current_verse' => $this->current_verse,
            'verses_memorized' => $this->verses_memorized_today,
            'recitation_quality' => $this->recitation_quality,
            'tajweed_accuracy' => $this->tajweed_accuracy,
            'areas_for_improvement' => $this->areas_for_improvement,
            'notes' => $this->session_notes
        ]);
    }

    public function assignHomework(array $homeworkData): QuranHomework
    {
        return QuranHomework::create(array_merge($homeworkData, [
            'academy_id' => $this->academy_id,
            'quran_teacher_id' => $this->quran_teacher_id,
            'student_id' => $this->student_id,
            'session_id' => $this->id,
            'subscription_id' => $this->quran_subscription_id,
            'circle_id' => $this->circle_id,
            'assigned_at' => now(),
            'status' => 'assigned'
        ]));
    }

    public function generateMeetingLink(array $options = []): string
    {
        $livekitService = app(\App\Services\LiveKitService::class);
        
        // Set default options
        $defaultOptions = [
            'recording_enabled' => $this->recording_enabled ?? false,
            'max_participants' => $options['max_participants'] ?? 50,
            'max_duration' => $this->duration_minutes ?? 120, // Use session duration
            'session_type' => $this->session_type,
        ];
        
        $mergedOptions = array_merge($defaultOptions, $options);
        
        // Generate meeting using LiveKit service
        $meetingInfo = $livekitService->createMeeting(
            $this->academy,
            $this->session_type ?? 'quran',
            $this->id,
            $this->scheduled_at ?? now(),
            $mergedOptions
        );
        
        // Update session with meeting info
        $this->update([
            'meeting_link' => $meetingInfo['meeting_url'],
            'meeting_id' => $meetingInfo['meeting_id'],
            'meeting_platform' => $meetingInfo['platform'],
            'meeting_source' => $meetingInfo['platform'],
            'meeting_data' => $meetingInfo,
            'meeting_room_name' => $meetingInfo['room_name'],
            'meeting_auto_generated' => true,
            'meeting_expires_at' => $meetingInfo['expires_at'],
        ]);

        return $meetingInfo['meeting_url'];
    }

    /**
     * Get meeting join information
     */
    public function getMeetingInfo(): ?array
    {
        if (!$this->meeting_data) {
            return null;
        }

        return $this->meeting_data;
    }

    /**
     * Check if meeting is still valid
     */
    public function isMeetingValid(): bool
    {
        if (!$this->meeting_link) {
            return false;
        }

        if ($this->meeting_expires_at && $this->meeting_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Get meeting join URL for display
     */
    public function getMeetingJoinUrl(): ?string
    {
        if (!$this->isMeetingValid()) {
            return null;
        }

        return $this->meeting_link;
    }

    /**
     * Generate participant access token for LiveKit room
     */
    public function generateParticipantToken(User $user, array $permissions = []): string
    {
        if (!$this->meeting_room_name) {
            throw new \Exception('Meeting room not created yet');
        }

        $livekitService = app(\App\Services\LiveKitService::class);
        
        // Set permissions based on user role
        $defaultPermissions = [
            'can_publish' => true,
            'can_subscribe' => true,
            'can_update_metadata' => in_array($user->user_type, ['quran_teacher', 'academic_teacher']),
        ];
        
        $mergedPermissions = array_merge($defaultPermissions, $permissions);
        
        return $livekitService->generateParticipantToken(
            $this->meeting_room_name,
            $user,
            $mergedPermissions
        );
    }

    /**
     * Start recording for this session
     */
    public function startRecording(array $options = []): array
    {
        if (!$this->meeting_room_name) {
            throw new \Exception('Meeting room not created yet');
        }

        $livekitService = app(\App\Services\LiveKitService::class);
        
        $recordingOptions = [
            'layout' => $options['layout'] ?? 'grid',
            'video_quality' => $options['video_quality'] ?? 'high',
            'audio_only' => $options['audio_only'] ?? false,
        ];
        
        $recordingInfo = $livekitService->startRecording($this->meeting_room_name, $recordingOptions);
        
        // Update session with recording info
        $meetingData = $this->meeting_data ?? [];
        $meetingData['recording'] = $recordingInfo;
        
        $this->update([
            'recording_url' => $recordingInfo['recording_id'], // Temporary until file is ready
            'meeting_data' => $meetingData,
        ]);
        
        return $recordingInfo;
    }

    /**
     * Stop recording for this session
     */
    public function stopRecording(): array
    {
        if (!$this->meeting_data || !isset($this->meeting_data['recording'])) {
            throw new \Exception('No active recording found');
        }

        $livekitService = app(\App\Services\LiveKitService::class);
        $recordingId = $this->meeting_data['recording']['recording_id'];
        
        $result = $livekitService->stopRecording($recordingId);
        
        // Update session with final recording info
        $meetingData = $this->meeting_data;
        $meetingData['recording'] = array_merge($meetingData['recording'], $result);
        
        $this->update([
            'recording_url' => $result['file_info']['download_url'] ?? null,
            'meeting_data' => $meetingData,
        ]);
        
        return $result;
    }

    /**
     * Get current room information and participants
     */
    public function getRoomInfo(): ?array
    {
        if (!$this->meeting_room_name) {
            return null;
        }

        $livekitService = app(\App\Services\LiveKitService::class);
        
        return $livekitService->getRoomInfo($this->meeting_room_name);
    }

    /**
     * End the meeting and clean up room
     */
    public function endMeeting(): bool
    {
        if (!$this->meeting_room_name) {
            return false;
        }

        $livekitService = app(\App\Services\LiveKitService::class);
        
        $success = $livekitService->endMeeting($this->meeting_room_name);
        
        if ($success) {
            $this->update([
                'ended_at' => now(),
                'status' => 'completed',
            ]);
        }
        
        return $success;
    }

    /**
     * Set meeting duration limit
     */
    public function setMeetingDuration(int $durationMinutes): bool
    {
        if (!$this->meeting_room_name) {
            return false;
        }

        $livekitService = app(\App\Services\LiveKitService::class);
        
        $success = $livekitService->setMeetingDuration($this->meeting_room_name, $durationMinutes);
        
        if ($success) {
            $this->update(['duration_minutes' => $durationMinutes]);
        }
        
        return $success;
    }

    /**
     * Check if user is currently in the meeting room
     */
    public function isUserInMeeting(User $user): bool
    {
        $roomInfo = $this->getRoomInfo();
        
        if (!$roomInfo || !isset($roomInfo['participants'])) {
            return false;
        }
        
        $userIdentity = $user->id . '_' . Str::slug($user->first_name . '_' . $user->last_name);
        
        foreach ($roomInfo['participants'] as $participant) {
            if ($participant['id'] === $userIdentity) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get meeting statistics
     */
    public function getMeetingStats(): array
    {
        $roomInfo = $this->getRoomInfo();
        $meetingData = $this->meeting_data ?? [];
        
        return [
            'is_active' => $roomInfo ? $roomInfo['is_active'] : false,
            'participant_count' => $roomInfo ? $roomInfo['participant_count'] : 0,
            'participants' => $roomInfo ? $roomInfo['participants'] : [],
            'duration_so_far' => $this->started_at ? now()->diffInMinutes($this->started_at) : 0,
            'scheduled_duration' => $this->duration_minutes,
            'recording_active' => isset($meetingData['recording']) && $meetingData['recording']['status'] === 'recording',
            'room_created_at' => $roomInfo ? $roomInfo['created_at'] : null,
        ];
    }

    public function addFeedback(string $feedbackType, string $feedback, User $feedbackBy = null): self
    {
        $feedbackField = $feedbackType . '_feedback';
        
        if (!in_array($feedbackField, ['teacher_feedback', 'student_feedback', 'parent_feedback'])) {
            throw new \Exception('نوع التعليق غير صحيح');
        }

        $this->update([
            $feedbackField => $feedback
        ]);

        return $this;
    }

    public function rate(int $rating): self
    {
        if ($rating < 1 || $rating > 5) {
            throw new \Exception('التقييم يجب أن يكون بين 1 و 5');
        }

        $this->update(['overall_rating' => $rating]);

        return $this;
    }

    private function getSurahName(int $surahNumber): string
    {
        $surahNames = [
            1 => 'الفاتحة', 2 => 'البقرة', 3 => 'آل عمران', 4 => 'النساء',
            5 => 'المائدة', 6 => 'الأنعام', 7 => 'الأعراف', 8 => 'الأنفال',
            9 => 'التوبة', 10 => 'يونس', 11 => 'هود', 12 => 'يوسف',
            13 => 'الرعد', 14 => 'إبراهيم', 15 => 'الحجر', 16 => 'النحل',
            17 => 'الإسراء', 18 => 'الكهف', 19 => 'مريم', 20 => 'طه',
            // Add all 114 surahs as needed
        ];

        return $surahNames[$surahNumber] ?? "سورة رقم {$surahNumber}";
    }

    // Static methods
    public static function createSession(array $data): self
    {
        return self::create(array_merge($data, [
            'session_code' => self::generateSessionCode($data['academy_id']),
            'status' => 'scheduled',
            'is_makeup_session' => false
        ]));
    }

    private static function generateSessionCode(int $academyId): string
    {
        $attempt = 0;
        do {
            $attempt++;
            $count = self::where('academy_id', $academyId)->count() + $attempt;
            $sessionCode = 'QSE-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
        } while (
            self::where('academy_id', $academyId)
                ->where('session_code', $sessionCode)
                ->exists() && $attempt < 100
        );
        
        return $sessionCode;
    }

    // Boot method to handle model events
    protected static function booted()
    {
        // Handle session deletion - check if group circle needs re-scheduling
        static::deleted(function ($session) {
            if ($session->circle_id && $session->generatedFromSchedule) {
                // This was a group circle session generated from a schedule
                $schedule = $session->generatedFromSchedule;
                
                if ($schedule && $schedule->is_active) {
                    // Check if we need to generate more sessions for this month
                    $currentMonth = now()->format('Y-m');
                    $currentMonthSessions = self::where('circle_id', $session->circle_id)
                        ->whereRaw("DATE_FORMAT(scheduled_at, '%Y-%m') = ?", [$currentMonth])
                        ->count();
                    
                    $monthlyLimit = $schedule->circle->monthly_sessions_count ?? 4;
                    
                    if ($currentMonthSessions < $monthlyLimit) {
                        // Try to generate replacement sessions
                        $schedule->generateUpcomingSessions();
                    }
                }
            }
            
            // Update individual circle counts if needed
            if ($session->individual_circle_id && $session->individualCircle) {
                $session->individualCircle->updateSessionCounts();
            }
        });
    }

    public static function getTodaysSessions(int $academyId, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = self::where('academy_id', $academyId)
            ->today()
            ->with(['quranTeacher.user', 'student', 'subscription', 'circle']);

        if (isset($filters['teacher_id'])) {
            $query->where('quran_teacher_id', $filters['teacher_id']);
        }

        if (isset($filters['student_id'])) {
            $query->where('student_id', $filters['student_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['session_type'])) {
            $query->where('session_type', $filters['session_type']);
        }

        return $query->orderBy('scheduled_at', 'asc')->get();
    }

    public static function getUpcomingSessions(int $teacherId, int $days = 7): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('quran_teacher_id', $teacherId)
            ->upcoming()
            ->whereBetween('scheduled_at', [now(), now()->addDays($days)])
            ->with(['student', 'subscription', 'circle'])
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    public static function getSessionsNeedingFollowUp(int $academyId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('academy_id', $academyId)
            ->completed()
            ->where('follow_up_required', true)
            ->with(['quranTeacher.user', 'student'])
            ->get();
    }
} 