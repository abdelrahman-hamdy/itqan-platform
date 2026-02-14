<?php

namespace App\Observers;

use App\Enums\NotificationType;
use App\Enums\PayoutStatus;
use App\Models\TeacherPayout;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Observer for TeacherPayout model.
 *
 * Sends notifications to teachers when payout status changes.
 */
class TeacherPayoutObserver
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle payout status changes.
     */
    public function updated(TeacherPayout $payout): void
    {
        if (! $payout->isDirty('status')) {
            return;
        }

        $newStatus = $payout->status;

        match ($newStatus) {
            PayoutStatus::APPROVED => $this->notifyPayoutApproved($payout),
            PayoutStatus::REJECTED => $this->notifyPayoutRejected($payout),
            default => null,
        };
    }

    /**
     * Send notification when payout is approved.
     */
    private function notifyPayoutApproved(TeacherPayout $payout): void
    {
        try {
            $teacher = $payout->teacher;
            $user = $teacher?->user;

            if (! $user) {
                return;
            }

            $data = [
                'payout_code' => $payout->payout_code,
                'total_amount' => number_format($payout->total_amount, 2),
                'month_name' => $payout->month_name ?? '',
                'sessions_count' => $payout->sessions_count,
                'approval_notes' => $payout->approval_notes,
            ];

            $this->notificationService->send(
                $user,
                NotificationType::PAYOUT_APPROVED,
                $data,
                $this->getTeacherEarningsUrl($payout),
                ['payout_id' => $payout->id],
                true
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payout approved notification', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification when payout is rejected.
     */
    private function notifyPayoutRejected(TeacherPayout $payout): void
    {
        try {
            $teacher = $payout->teacher;
            $user = $teacher?->user;

            if (! $user) {
                return;
            }

            $data = [
                'payout_code' => $payout->payout_code,
                'total_amount' => number_format($payout->total_amount, 2),
                'month_name' => $payout->month_name ?? '',
                'rejection_reason' => $payout->rejection_reason ?? __('notifications.payout.no_reason'),
            ];

            $this->notificationService->send(
                $user,
                NotificationType::PAYOUT_REJECTED,
                $data,
                $this->getTeacherEarningsUrl($payout),
                ['payout_id' => $payout->id],
                true
            );
        } catch (\Exception $e) {
            Log::error('Failed to send payout rejected notification', [
                'payout_id' => $payout->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get the teacher earnings URL based on teacher type.
     */
    private function getTeacherEarningsUrl(TeacherPayout $payout): string
    {
        $teacherType = class_basename($payout->teacher_type);

        return match ($teacherType) {
            'QuranTeacherProfile' => '/teacher-panel/teacher-earnings',
            'AcademicTeacherProfile' => '/academic-teacher-panel/teacher-earnings',
            default => '/teacher-panel/teacher-earnings',
        };
    }
}
