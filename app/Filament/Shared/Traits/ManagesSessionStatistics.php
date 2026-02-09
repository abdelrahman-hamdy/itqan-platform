<?php

namespace App\Filament\Shared\Traits;

use App\Enums\SessionStatus;
use App\Enums\UserType;
use App\Models\AcademicSession;
use App\Models\QuranSession;
use Illuminate\Support\Facades\Auth;

/**
 * Trait ManagesSessionStatistics
 *
 * Provides session statistics calculation for calendar pages.
 * Works for both Quran and Academic teachers.
 */
trait ManagesSessionStatistics
{
    /**
     * Get session statistics for the top 4 boxes
     *
     * Automatically detects teacher type and returns appropriate statistics.
     *
     * @return array Array of statistics with title, value, icon, and color
     */
    public function getSessionStatistics(): array
    {
        $user = Auth::user();
        if (! $user) {
            return $this->getEmptyStatistics();
        }

        // Detect teacher type
        if ($user->user_type === UserType::QURAN_TEACHER->value) {
            return $this->getQuranSessionStatistics();
        } elseif ($user->user_type === UserType::ACADEMIC_TEACHER->value) {
            return $this->getAcademicSessionStatistics();
        }

        return $this->getEmptyStatistics();
    }

    /**
     * Get statistics for Quran teachers
     */
    protected function getQuranSessionStatistics(): array
    {
        $userId = Auth::id();

        // Get today's sessions
        $todaySessions = QuranSession::where('quran_teacher_id', $userId)
            ->whereDate('scheduled_at', today())
            ->count();

        // Get upcoming sessions (next 7 days)
        $upcomingSessions = QuranSession::where('quran_teacher_id', $userId)
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();

        // Get completed sessions this month
        $completedThisMonth = QuranSession::where('quran_teacher_id', $userId)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        // Get pending/unscheduled sessions
        $pendingSessions = QuranSession::where('quran_teacher_id', $userId)
            ->where('status', SessionStatus::UNSCHEDULED->value)
            ->count();

        return $this->formatStatistics($todaySessions, $upcomingSessions, $completedThisMonth, $pendingSessions);
    }

    /**
     * Get statistics for Academic teachers
     */
    protected function getAcademicSessionStatistics(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return $this->getEmptyStatistics();
        }

        // Get today's sessions
        $todaySessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereDate('scheduled_at', today())
            ->count();

        // Get upcoming sessions (next 7 days)
        $upcomingSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('scheduled_at', '>', now())
            ->where('scheduled_at', '<=', now()->addDays(7))
            ->count();

        // Get completed sessions this month
        $completedThisMonth = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->whereMonth('scheduled_at', now()->month)
            ->whereYear('scheduled_at', now()->year)
            ->where('status', SessionStatus::COMPLETED->value)
            ->count();

        // Get pending/unscheduled sessions
        $pendingSessions = AcademicSession::where('academic_teacher_id', $teacherProfile->id)
            ->where('status', SessionStatus::UNSCHEDULED->value)
            ->count();

        return $this->formatStatistics($todaySessions, $upcomingSessions, $completedThisMonth, $pendingSessions);
    }

    /**
     * Format statistics array
     */
    protected function formatStatistics(int $today, int $upcoming, int $completed, int $pending): array
    {
        return [
            [
                'title' => 'جلسات اليوم',
                'value' => $today,
                'icon' => 'heroicon-o-calendar-days',
                'color' => 'primary',
            ],
            [
                'title' => 'الجلسات القادمة',
                'value' => $upcoming,
                'icon' => 'heroicon-o-clock',
                'color' => 'warning',
            ],
            [
                'title' => 'مكتملة هذا الشهر',
                'value' => $completed,
                'icon' => 'heroicon-o-check-circle',
                'color' => 'success',
            ],
            [
                'title' => 'في الانتظار',
                'value' => $pending,
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
            ],
        ];
    }

    /**
     * Get empty statistics (when no user or unsupported type)
     */
    protected function getEmptyStatistics(): array
    {
        return $this->formatStatistics(0, 0, 0, 0);
    }
}
