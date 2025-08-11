<?php

namespace App\Services;

use App\Models\QuranSession;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SessionManagementService
{
    /**
     * Create individual session for a specific date/time
     */
    public function createIndividualSession(
        QuranIndividualCircle $circle,
        Carbon $scheduledAt,
        int $durationMinutes = 45,
        ?string $title = null,
        ?string $description = null
    ): QuranSession {
        
        // Validate subscription has remaining sessions
        $remainingSessions = $this->getRemainingIndividualSessions($circle);
        if ($remainingSessions <= 0) {
            throw new \Exception('لا توجد جلسات متبقية في الاشتراك');
        }
        
        // Check for conflicts
        $this->validateTimeSlotAvailable($circle->quran_teacher_id, $scheduledAt, $durationMinutes);
        
        // Calculate session month and number
        $sessionMonth = $scheduledAt->format('Y-m-01');
        $monthlySessionNumber = $this->getNextSessionNumberForMonth($circle, $sessionMonth);
        
        // Auto-populate if not provided
        if (!$title) {
            $title = "جلسة فردية - {$circle->student->name} (جلسة {$monthlySessionNumber})";
        }
        
        return QuranSession::create([
            'academy_id' => $circle->academy_id,
            'quran_teacher_id' => $circle->quran_teacher_id,
            'individual_circle_id' => $circle->id,
            'student_id' => $circle->student_id,
            'quran_subscription_id' => $circle->subscription_id,
            'session_code' => $this->generateSessionCode('IND', $circle->id, $scheduledAt),
            'session_type' => 'individual',
            'status' => 'scheduled',
            'title' => $title ?? "جلسة فردية - {$circle->student->name}",
            'description' => $description ?? 'جلسة تحفيظ قرآن فردية',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'session_month' => $sessionMonth,
            'monthly_session_number' => $monthlySessionNumber,
            'counts_toward_subscription' => true,
            'created_by' => Auth::id(),
        ]);
    }
    
    /**
     * Create group session for a specific date/time
     */
    public function createGroupSession(
        QuranCircle $circle,
        Carbon $scheduledAt,
        int $durationMinutes = 60,
        ?string $title = null,
        ?string $description = null
    ): QuranSession {
        
        // Check for conflicts
        $this->validateTimeSlotAvailable($circle->quran_teacher_id, $scheduledAt, $durationMinutes);
        
        // Calculate session month and number
        $sessionMonth = $scheduledAt->format('Y-m-01');
        $monthlySessionNumber = $this->getNextSessionNumberForMonth($circle, $sessionMonth);
        
        // Allow flexible scheduling - teachers can schedule additional sessions as needed
        // Remove the hard monthly limit to support cases where teachers need extra sessions
        // The circle's monthly_sessions_count serves as a guideline/recommendation, not a strict limit
        
        // Auto-populate if not provided
        if (!$title) {
            $title = "جلسة جماعية - {$circle->name} (جلسة {$monthlySessionNumber})";
        }
        
        return QuranSession::create([
            'academy_id' => $circle->academy_id,
            'quran_teacher_id' => $circle->quran_teacher_id,
            'circle_id' => $circle->id,
            'session_code' => $this->generateSessionCode('GRP', $circle->id, $scheduledAt),
            'session_type' => 'group',
            'status' => 'scheduled',
            'title' => $title ?? "جلسة جماعية - {$circle->name}",
            'description' => $description ?? 'جلسة تحفيظ قرآن جماعية',
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => $durationMinutes,
            'session_month' => $sessionMonth,
            'monthly_session_number' => $monthlySessionNumber,
            'counts_toward_subscription' => true,
            'created_by' => Auth::id(),
        ]);
    }
    
    /**
     * Bulk create sessions for a circle using pattern
     */
    public function bulkCreateSessions(
        $circle, // QuranCircle or QuranIndividualCircle
        array $timeSlots, // [['day' => 'monday', 'time' => '10:00'], ...]
        Carbon $startDate,
        Carbon $endDate,
        int $durationMinutes = 60
    ): Collection {
        
        $sessions = collect();
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            foreach ($timeSlots as $slot) {
                if (strtolower($currentDate->format('l')) === $slot['day']) {
                    $scheduledAt = $currentDate->copy()->setTimeFromTimeString($slot['time']);
                    
                    // Skip past dates
                    if ($scheduledAt->isPast()) {
                        continue;
                    }
                    
                    try {
                        if ($circle instanceof QuranIndividualCircle) {
                            $session = $this->createIndividualSession($circle, $scheduledAt, $durationMinutes);
                        } else {
                            $session = $this->createGroupSession($circle, $scheduledAt, $durationMinutes);
                        }
                        $sessions->push($session);
                    } catch (\Exception $e) {
                        // Log error but continue with other sessions
                        \Log::warning("Failed to create session", [
                            'circle_id' => $circle->id,
                            'scheduled_at' => $scheduledAt,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            $currentDate->addDay();
        }
        
        return $sessions;
    }
    
    /**
     * Delete session with business logic
     */
    public function deleteSession(QuranSession $session): bool
    {
        DB::beginTransaction();
        
        try {
            // For individual sessions, ensure subscription integrity
            if ($session->individual_circle_id) {
                $this->handleIndividualSessionDeletion($session);
            }
            
            // For group sessions, update monthly counts
            if ($session->circle_id) {
                $this->handleGroupSessionDeletion($session);
            }
            
            $session->delete();
            
            DB::commit();
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    /**
     * Reset all sessions for a circle
     */
    public function resetCircleSessions($circle): int
    {
        if ($circle instanceof QuranIndividualCircle) {
            $deletedCount = $circle->sessions()->delete();
        } else {
            $deletedCount = $circle->sessions()->delete();
        }
        
        return $deletedCount;
    }
    
    /**
     * Get remaining individual sessions count
     */
    public function getRemainingIndividualSessions(QuranIndividualCircle $circle): int
    {
        $totalSessions = $circle->total_sessions;
        $usedSessions = $circle->sessions()
            ->where('counts_toward_subscription', true)
            ->whereIn('status', ['completed', 'scheduled', 'in_progress'])
            ->count();
            
        return max(0, $totalSessions - $usedSessions);
    }
    
    /**
     * Get used sessions for group circle in a month
     */
    public function getGroupSessionsForMonth(QuranCircle $circle, string $month): int
    {
        return $circle->sessions()
            ->where('session_month', $month . '-01')
            ->where('counts_toward_subscription', true)
            ->count();
    }
    
    /**
     * Get session statistics for teacher dashboard
     */
    public function getTeacherSessionStats(int $teacherId): array
    {
        $currentMonth = now()->format('Y-m-01');
        
        return [
            'total_sessions_this_month' => QuranSession::where('quran_teacher_id', $teacherId)
                ->where('session_month', $currentMonth)
                ->count(),
                
            'completed_sessions_this_month' => QuranSession::where('quran_teacher_id', $teacherId)
                ->where('session_month', $currentMonth)
                ->where('status', 'completed')
                ->count(),
                
            'scheduled_sessions_this_week' => QuranSession::where('quran_teacher_id', $teacherId)
                ->where('status', 'scheduled')
                ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
                
            'individual_circles_active' => QuranIndividualCircle::where('quran_teacher_id', $teacherId)
                ->whereIn('status', ['pending', 'active'])
                ->count(),
                
            'group_circles_active' => QuranCircle::where('quran_teacher_id', $teacherId)
                ->where('status', 'active')
                ->count(),
        ];
    }
    
    /**
     * Get monthly progress for a circle
     */
    public function getCircleMonthlyProgress($circle, string $month): array
    {
        $monthStart = Carbon::parse($month . '-01');
        $monthEnd = $monthStart->copy()->endOfMonth();
        
        $sessions = $circle->sessions()
            ->where('session_month', $month . '-01')
            ->where('counts_toward_subscription', true)
            ->get();
            
        $maxSessions = $circle instanceof QuranCircle 
            ? $circle->monthly_sessions_count 
            : 8; // Default for individual circles
            
        return [
            'month' => $monthStart->format('Y-m'),
            'total_sessions' => $sessions->count(),
            'max_sessions' => $maxSessions,
            'completed_sessions' => $sessions->where('status', 'completed')->count(),
            'scheduled_sessions' => $sessions->where('status', 'scheduled')->count(),
            'cancelled_sessions' => $sessions->where('status', 'cancelled')->count(),
            'progress_percentage' => $maxSessions > 0 ? round(($sessions->where('status', 'completed')->count() / $maxSessions) * 100, 1) : 0,
        ];
    }
    
    // Private helper methods
    
    private function validateTimeSlotAvailable(int $teacherId, Carbon $scheduledAt, int $duration): void
    {
        $endTime = $scheduledAt->copy()->addMinutes($duration);
        
        $conflict = QuranSession::where('quran_teacher_id', $teacherId)
            ->where(function ($query) use ($scheduledAt, $endTime) {
                $query->whereBetween('scheduled_at', [$scheduledAt, $endTime])
                      ->orWhereBetween(
                          DB::raw('DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE)'),
                          [$scheduledAt, $endTime]
                      );
            })
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->exists();
            
        if ($conflict) {
            throw new \Exception('يوجد تعارض مع جلسة أخرى في هذا التوقيت');
        }
    }
    
    private function getNextSessionNumberForMonth($circle, string $sessionMonth): int
    {
        if ($circle instanceof QuranIndividualCircle) {
            $existingCount = $circle->sessions()
                ->where('session_month', $sessionMonth)
                ->count();
        } else {
            $existingCount = $circle->sessions()
                ->where('session_month', $sessionMonth)
                ->count();
        }
        
        return $existingCount + 1;
    }
    
    private function generateSessionCode(string $type, int $circleId, Carbon $scheduledAt): string
    {
        return sprintf(
            '%s-%d-%s-%s',
            $type,
            $circleId,
            $scheduledAt->format('Ymd'),
            substr(uniqid(), -4)
        );
    }
    
    private function handleIndividualSessionDeletion(QuranSession $session): void
    {
        // Business logic: Individual sessions must maintain subscription integrity
        // Option 1: Allow deletion but teacher must reschedule
        // Option 2: Mark as cancelled but don't delete
        // Option 3: Create makeup session requirement
        
        // We'll go with Option 1 for flexibility
        \Log::info('Individual session deleted', [
            'session_id' => $session->id,
            'circle_id' => $session->individual_circle_id,
            'remaining_sessions' => $this->getRemainingIndividualSessions($session->individualCircle) - 1
        ]);
    }
    
    private function handleGroupSessionDeletion(QuranSession $session): void
    {
        // Business logic: Group sessions can be deleted but teacher should maintain monthly count
        \Log::info('Group session deleted', [
            'session_id' => $session->id,
            'circle_id' => $session->circle_id,
            'session_month' => $session->session_month,
            'monthly_number' => $session->monthly_session_number
        ]);
    }
}
