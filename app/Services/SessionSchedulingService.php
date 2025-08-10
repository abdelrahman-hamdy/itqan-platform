<?php

namespace App\Services;

use App\Models\SessionSchedule;
use App\Models\QuranSubscription;
use App\Models\QuranCircle;
use App\Models\QuranSession;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SessionSchedulingService
{
    private GoogleCalendarService $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Create schedule for individual subscription
     */
    public function createSubscriptionSchedule(
        QuranSubscription $subscription, 
        array $scheduleData,
        User $createdBy
    ): SessionSchedule {
        
        DB::beginTransaction();
        
        try {
            // Validate schedule data
            $this->validateSubscriptionScheduleData($subscription, $scheduleData);
            
            // Check for conflicts
            $this->validateScheduleConflicts($subscription->quranTeacher, $scheduleData);
            
            // Create schedule record
            $schedule = $this->createScheduleRecord($subscription, $scheduleData, $createdBy);
            
            // Generate initial sessions
            $generatedCount = $schedule->generateSessions(8); // Generate 8 weeks ahead
            
            Log::info('Subscription schedule created', [
                'schedule_id' => $schedule->id,
                'subscription_id' => $subscription->id,
                'sessions_generated' => $generatedCount,
                'created_by' => $createdBy->id
            ]);
            
            DB::commit();
            
            return $schedule;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create subscription schedule', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'schedule_data' => $scheduleData
            ]);
            
            throw $e;
        }
    }

    /**
     * Create schedule for group circle
     */
    public function createCircleSchedule(
        QuranCircle $circle,
        array $scheduleData,
        User $createdBy
    ): SessionSchedule {
        
        DB::beginTransaction();
        
        try {
            // Validate schedule data
            $this->validateCircleScheduleData($circle, $scheduleData);
            
            // Check for conflicts
            $this->validateScheduleConflicts($circle->quranTeacher, $scheduleData);
            
            // Create schedule record
            $schedule = SessionSchedule::create([
                'academy_id' => $circle->academy_id,
                'quran_teacher_id' => $circle->quran_teacher_id,
                'quran_circle_id' => $circle->id,
                'schedule_code' => $this->generateScheduleCode('CIR', $circle->academy_id),
                'schedule_type' => SessionSchedule::TYPE_CIRCLE,
                'title' => "جدول حلقة {$circle->name_ar}",
                'description' => $scheduleData['description'] ?? "جدول منتظم لحلقة {$circle->name_ar}",
                'recurrence_pattern' => $scheduleData['pattern'],
                'schedule_data' => $scheduleData,
                'session_templates' => $this->buildSessionTemplates($scheduleData),
                'start_date' => Carbon::parse($scheduleData['start_date']),
                'end_date' => isset($scheduleData['end_date']) ? Carbon::parse($scheduleData['end_date']) : null,
                'status' => SessionSchedule::STATUS_ACTIVE,
                'auto_generate' => true,
                'allow_rescheduling' => $scheduleData['allow_rescheduling'] ?? true,
                'reschedule_hours_notice' => $scheduleData['reschedule_hours_notice'] ?? 24,
                'created_by' => $createdBy->id,
            ]);
            
            // Generate initial sessions
            $generatedCount = $schedule->generateSessions(8);
            
            // Update circle with schedule info
            $circle->update([
                'schedule_days' => collect($scheduleData['sessions'])->pluck('day')->toArray(),
                'schedule_time' => $scheduleData['sessions'][0]['time'] ?? null,
                'next_session_at' => $schedule->next_session_date,
            ]);
            
            Log::info('Circle schedule created', [
                'schedule_id' => $schedule->id,
                'circle_id' => $circle->id,
                'sessions_generated' => $generatedCount,
                'created_by' => $createdBy->id
            ]);
            
            DB::commit();
            
            return $schedule;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to create circle schedule', [
                'circle_id' => $circle->id,
                'error' => $e->getMessage(),
                'schedule_data' => $scheduleData
            ]);
            
            throw $e;
        }
    }

    /**
     * Update existing schedule
     */
    public function updateSchedule(
        SessionSchedule $schedule,
        array $scheduleData,
        User $updatedBy
    ): SessionSchedule {
        
        DB::beginTransaction();
        
        try {
            // Validate new schedule data
            if ($schedule->schedule_type === SessionSchedule::TYPE_SUBSCRIPTION) {
                $this->validateSubscriptionScheduleData($schedule->subscription, $scheduleData);
            } else {
                $this->validateCircleScheduleData($schedule->circle, $scheduleData);
            }
            
            // Check for conflicts (excluding existing sessions)
            $this->validateScheduleConflicts(
                $schedule->quranTeacher, 
                $scheduleData, 
                $schedule->id
            );
            
            // Cancel future sessions if schedule significantly changed
            if ($this->hasSignificantChanges($schedule, $scheduleData)) {
                $this->cancelFutureSessions($schedule);
            }
            
            // Update schedule
            $schedule->update([
                'title' => $scheduleData['title'] ?? $schedule->title,
                'description' => $scheduleData['description'] ?? $schedule->description,
                'recurrence_pattern' => $scheduleData['pattern'],
                'schedule_data' => $scheduleData,
                'session_templates' => $this->buildSessionTemplates($scheduleData),
                'end_date' => isset($scheduleData['end_date']) ? Carbon::parse($scheduleData['end_date']) : $schedule->end_date,
                'allow_rescheduling' => $scheduleData['allow_rescheduling'] ?? $schedule->allow_rescheduling,
                'reschedule_hours_notice' => $scheduleData['reschedule_hours_notice'] ?? $schedule->reschedule_hours_notice,
                'updated_by' => $updatedBy->id,
            ]);
            
            // Generate new sessions
            $generatedCount = $schedule->generateSessions(4);
            
            Log::info('Schedule updated', [
                'schedule_id' => $schedule->id,
                'sessions_generated' => $generatedCount,
                'updated_by' => $updatedBy->id
            ]);
            
            DB::commit();
            
            return $schedule;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update schedule', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Reschedule a single session
     */
    public function rescheduleSession(
        QuranSession $session,
        Carbon $newDateTime,
        ?string $reason = null,
        User $rescheduledBy
    ): QuranSession {
        
        // Validate rescheduling is allowed
        if (!$this->canRescheduleSession($session, $newDateTime)) {
            throw new \Exception('لا يمكن إعادة جدولة هذه الجلسة في الوقت المحدد');
        }
        
        // Check for conflicts
        $conflicts = $this->checkSessionConflicts($session->quranTeacher->user, $newDateTime, $session->duration_minutes, $session->id);
        if ($conflicts->count() > 0) {
            throw new \Exception('يوجد تعارض مع جلسة أخرى في الوقت المحدد');
        }
        
        DB::beginTransaction();
        
        try {
            // Update session
            $session->update([
                'rescheduled_from' => $session->scheduled_at,
                'scheduled_at' => $newDateTime,
                'reschedule_reason' => $reason,
                'status' => 'rescheduled',
                'updated_by' => $rescheduledBy->id,
            ]);
            
            // Update Google Calendar event if exists
            if ($session->google_event_id && $session->meeting_source === 'google') {
                try {
                    // This would update the Google Calendar event
                    // Implementation depends on GoogleCalendarService enhancement
                } catch (\Exception $e) {
                    Log::warning('Failed to update Google Calendar event', [
                        'session_id' => $session->id,
                        'google_event_id' => $session->google_event_id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('Session rescheduled', [
                'session_id' => $session->id,
                'old_time' => $session->rescheduled_from,
                'new_time' => $newDateTime,
                'rescheduled_by' => $rescheduledBy->id
            ]);
            
            DB::commit();
            
            return $session;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Generate sessions for all active schedules
     */
    public function generateAllScheduleSessions(int $weeksAhead = 4): array
    {
        $results = [
            'processed' => 0,
            'generated' => 0,
            'errors' => []
        ];
        
        $activeSchedules = SessionSchedule::active()
            ->autoGenerate()
            ->with(['quranTeacher', 'subscription', 'circle'])
            ->get();
            
        foreach ($activeSchedules as $schedule) {
            try {
                $generated = $schedule->generateSessions($weeksAhead);
                $results['processed']++;
                $results['generated'] += $generated;
                
                Log::info('Generated sessions for schedule', [
                    'schedule_id' => $schedule->id,
                    'sessions_generated' => $generated
                ]);
                
            } catch (\Exception $e) {
                $results['errors'][] = [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage()
                ];
                
                Log::error('Failed to generate sessions for schedule', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        Log::info('Batch session generation completed', $results);
        
        return $results;
    }

    /**
     * Validate subscription schedule data
     */
    private function validateSubscriptionScheduleData(QuranSubscription $subscription, array $scheduleData): void
    {
        if (!isset($scheduleData['sessions']) || empty($scheduleData['sessions'])) {
            throw new \Exception('يجب تحديد أوقات الجلسات');
        }
        
        if (!isset($scheduleData['pattern'])) {
            throw new \Exception('يجب تحديد نمط التكرار');
        }
        
        if (!isset($scheduleData['start_date'])) {
            throw new \Exception('يجب تحديد تاريخ البداية');
        }
        
        // Validate sessions per week doesn't exceed package limit
        $sessionsPerWeek = count($scheduleData['sessions']);
        $packageLimit = $subscription->package->sessions_per_month;
        
        if ($sessionsPerWeek * 4 > $packageLimit) {
            throw new \Exception("عدد الجلسات الأسبوعية ({$sessionsPerWeek}) يتجاوز حد الباقة ({$packageLimit} جلسة شهرياً)");
        }
        
        // Validate remaining sessions
        if ($subscription->sessions_remaining <= 0) {
            throw new \Exception('لا توجد جلسات متبقية في الاشتراك');
        }
        
        // Validate session times
        foreach ($scheduleData['sessions'] as $session) {
            if (!isset($session['day']) || !isset($session['time'])) {
                throw new \Exception('يجب تحديد اليوم والوقت لكل جلسة');
            }
            
            if (!in_array($session['day'], ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'])) {
                throw new \Exception('يوم غير صحيح: ' . $session['day']);
            }
            
            if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $session['time'])) {
                throw new \Exception('وقت غير صحيح: ' . $session['time']);
            }
        }
    }

    /**
     * Validate circle schedule data
     */
    private function validateCircleScheduleData(QuranCircle $circle, array $scheduleData): void
    {
        if (!isset($scheduleData['sessions']) || empty($scheduleData['sessions'])) {
            throw new \Exception('يجب تحديد أوقات الجلسات');
        }
        
        if (!isset($scheduleData['pattern'])) {
            throw new \Exception('يجب تحديد نمط التكرار');
        }
        
        if (!isset($scheduleData['start_date'])) {
            throw new \Exception('يجب تحديد تاريخ البداية');
        }
        
        // Validate circle is active and has students
        if ($circle->status !== 'active') {
            throw new \Exception('الحلقة غير نشطة');
        }
        
        if ($circle->enrolled_students < $circle->min_students_to_start) {
            throw new \Exception("عدد الطلاب المسجلين ({$circle->enrolled_students}) أقل من الحد الأدنى ({$circle->min_students_to_start})");
        }
        
        // Validate session times
        foreach ($scheduleData['sessions'] as $session) {
            if (!isset($session['day']) || !isset($session['time'])) {
                throw new \Exception('يجب تحديد اليوم والوقت لكل جلسة');
            }
        }
    }

    /**
     * Validate schedule conflicts
     */
    private function validateScheduleConflicts(
        QuranTeacherProfile $teacher, 
        array $scheduleData, 
        ?int $excludeScheduleId = null
    ): void {
        
        foreach ($scheduleData['sessions'] as $sessionData) {
            $conflicts = $this->checkTeacherConflicts(
                $teacher, 
                $sessionData['day'], 
                $sessionData['time'], 
                $sessionData['duration'] ?? 60,
                $excludeScheduleId
            );
            
            if ($conflicts->count() > 0) {
                $dayText = $this->getDayText($sessionData['day']);
                throw new \Exception("يوجد تعارض في الجدولة يوم {$dayText} الساعة {$sessionData['time']}");
            }
        }
    }

    /**
     * Check teacher schedule conflicts
     */
    private function checkTeacherConflicts(
        QuranTeacherProfile $teacher,
        string $day,
        string $time,
        int $duration,
        ?int $excludeScheduleId = null
    ): Collection {
        
        // Get all future sessions for this teacher on this day
        $conflicts = QuranSession::where('quran_teacher_id', $teacher->id)
            ->where('status', '!=', 'cancelled')
            ->where('scheduled_at', '>=', now())
            ->whereRaw('DAYNAME(scheduled_at) = ?', [ucfirst($day)])
            ->when($excludeScheduleId, function ($query) use ($excludeScheduleId) {
                return $query->where('session_schedule_id', '!=', $excludeScheduleId);
            })
            ->get()
            ->filter(function ($session) use ($time, $duration) {
                return $this->timesOverlap(
                    $session->scheduled_at->format('H:i'),
                    $time,
                    $session->duration_minutes,
                    $duration
                );
            });
        
        return $conflicts;
    }

    /**
     * Check if two time slots overlap
     */
    private function timesOverlap(string $time1, string $time2, int $duration1, int $duration2): bool
    {
        $start1 = Carbon::createFromFormat('H:i', $time1);
        $end1 = $start1->copy()->addMinutes($duration1);
        
        $start2 = Carbon::createFromFormat('H:i', $time2);
        $end2 = $start2->copy()->addMinutes($duration2);
        
        return $start1->lt($end2) && $start2->lt($end1);
    }

    /**
     * Check session-specific conflicts
     */
    private function checkSessionConflicts(
        User $user, 
        Carbon $dateTime, 
        int $duration, 
        ?int $excludeSessionId = null
    ): Collection {
        
        $endTime = $dateTime->copy()->addMinutes($duration);
        
        // Check teacher conflicts
        $teacherConflicts = collect();
        if ($user->isQuranTeacher()) {
            $teacherConflicts = QuranSession::where('quran_teacher_id', $user->quranTeacherProfile->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($query) use ($dateTime, $endTime) {
                    $query->whereBetween('scheduled_at', [$dateTime, $endTime])
                          ->orWhere(function ($q) use ($dateTime, $endTime) {
                              $q->where('scheduled_at', '<', $dateTime)
                                ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$dateTime]);
                          });
                })
                ->when($excludeSessionId, fn($q) => $q->where('id', '!=', $excludeSessionId))
                ->get();
        }
        
        // Check student conflicts
        $studentConflicts = QuranSession::where('student_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($dateTime, $endTime) {
                $query->whereBetween('scheduled_at', [$dateTime, $endTime])
                      ->orWhere(function ($q) use ($dateTime, $endTime) {
                          $q->where('scheduled_at', '<', $dateTime)
                            ->whereRaw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > ?', [$dateTime]);
                      });
            })
            ->when($excludeSessionId, fn($q) => $q->where('id', '!=', $excludeSessionId))
            ->get();
        
        return $teacherConflicts->merge($studentConflicts);
    }

    /**
     * Create schedule record
     */
    private function createScheduleRecord(
        QuranSubscription $subscription,
        array $scheduleData,
        User $createdBy
    ): SessionSchedule {
        
        return SessionSchedule::create([
            'academy_id' => $subscription->academy_id,
            'quran_teacher_id' => $subscription->quran_teacher_id,
            'quran_subscription_id' => $subscription->id,
            'schedule_code' => $this->generateScheduleCode('SUB', $subscription->academy_id),
            'schedule_type' => SessionSchedule::TYPE_SUBSCRIPTION,
            'title' => $scheduleData['title'] ?? "جدول اشتراك {$subscription->student->name}",
            'description' => $scheduleData['description'] ?? "جدول منتظم لاشتراك الطالب {$subscription->student->name}",
            'recurrence_pattern' => $scheduleData['pattern'],
            'schedule_data' => $scheduleData,
            'session_templates' => $this->buildSessionTemplates($scheduleData),
            'start_date' => Carbon::parse($scheduleData['start_date']),
            'end_date' => $subscription->expires_at,
            'max_sessions' => $subscription->total_sessions,
            'status' => SessionSchedule::STATUS_ACTIVE,
            'auto_generate' => true,
            'allow_rescheduling' => $scheduleData['allow_rescheduling'] ?? true,
            'reschedule_hours_notice' => $scheduleData['reschedule_hours_notice'] ?? 24,
            'created_by' => $createdBy->id,
        ]);
    }

    /**
     * Build session templates from schedule data
     */
    private function buildSessionTemplates(array $scheduleData): array
    {
        $templates = [];
        
        foreach ($scheduleData['sessions'] as $session) {
            $templates[] = [
                'day_of_week' => $session['day'],
                'start_time' => $session['time'],
                'duration_minutes' => $session['duration'] ?? 60,
                'metadata' => $session['metadata'] ?? null,
            ];
        }
        
        return $templates;
    }

    /**
     * Generate unique schedule code
     */
    private function generateScheduleCode(string $prefix, int $academyId): string
    {
        $count = SessionSchedule::where('academy_id', $academyId)->count() + 1;
        return $prefix . '-' . $academyId . '-' . str_pad($count, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Check if session can be rescheduled
     */
    private function canRescheduleSession(QuranSession $session, Carbon $newDateTime): bool
    {
        // Check if session is in valid status
        if (!in_array($session->status, ['scheduled', 'rescheduled'])) {
            return false;
        }
        
        // Check if new time is in the future
        if ($newDateTime->isPast()) {
            return false;
        }
        
        // Check schedule's rescheduling policy
        if ($session->sessionSchedule) {
            $schedule = $session->sessionSchedule;
            
            if (!$schedule->allow_rescheduling) {
                return false;
            }
            
            $hoursNotice = $schedule->reschedule_hours_notice;
            if ($session->scheduled_at->diffInHours(now()) < $hoursNotice) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check if schedule has significant changes
     */
    private function hasSignificantChanges(SessionSchedule $schedule, array $newData): bool
    {
        $oldTemplates = $schedule->session_templates;
        $newTemplates = $this->buildSessionTemplates($newData);
        
        return json_encode($oldTemplates) !== json_encode($newTemplates);
    }

    /**
     * Cancel future sessions
     */
    private function cancelFutureSessions(SessionSchedule $schedule): int
    {
        return $schedule->sessions()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '>', now())
            ->update([
                'status' => 'cancelled',
                'cancellation_reason' => 'تم تحديث الجدول',
                'cancelled_at' => now(),
            ]);
    }

    /**
     * Get Arabic day text
     */
    private function getDayText(string $day): string
    {
        return match(strtolower($day)) {
            'saturday' => 'السبت',
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            default => $day
        };
    }
}