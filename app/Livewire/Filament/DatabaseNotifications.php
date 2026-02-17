<?php

namespace App\Livewire\Filament;

use App\Enums\NotificationCategory;
use Filament\Facades\Filament;
use Filament\Livewire\DatabaseNotifications as BaseDatabaseNotifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Custom DatabaseNotifications component with per-panel category filtering.
 *
 * Each Filament panel only shows notifications relevant to that panel's role:
 * - Admin: All categories
 * - Academy: All categories
 * - Teacher: Session, Attendance, Homework, Meeting, Payment, Progress, Review, Trial, Alert
 * - AcademicTeacher: Session, Attendance, Homework, Meeting, Payment, Progress, Alert
 * - Supervisor: Session, Attendance, Homework, Progress, Review, Alert
 */
class DatabaseNotifications extends BaseDatabaseNotifications
{
    public function getNotificationsQuery(): Builder|Relation
    {
        $query = parent::getNotificationsQuery();

        $categories = $this->getRelevantCategories();

        // If null, show all (no filter needed for admin/academy)
        if ($categories !== null) {
            $categoryValues = array_map(fn (NotificationCategory $c) => $c->value, $categories);
            $query->whereIn('data->category', $categoryValues);
        }

        return $query;
    }

    /**
     * Get relevant notification categories for the current panel.
     *
     * @return NotificationCategory[]|null Null means show all categories
     */
    private function getRelevantCategories(): ?array
    {
        $panelId = Filament::getCurrentOrDefaultPanel()?->getId();

        return match ($panelId) {
            // Admin and Academy see everything
            'admin', 'academy' => null,

            // Quran Teacher: sessions, attendance, homework, meeting, payment (payouts), progress, review, trial, alert
            'teacher' => [
                NotificationCategory::SESSION,
                NotificationCategory::ATTENDANCE,
                NotificationCategory::HOMEWORK,
                NotificationCategory::MEETING,
                NotificationCategory::PAYMENT,
                NotificationCategory::PROGRESS,
                NotificationCategory::REVIEW,
                NotificationCategory::TRIAL,
                NotificationCategory::ALERT,
            ],

            // Academic Teacher: same as teacher minus trial and review
            'academic-teacher' => [
                NotificationCategory::SESSION,
                NotificationCategory::ATTENDANCE,
                NotificationCategory::HOMEWORK,
                NotificationCategory::MEETING,
                NotificationCategory::PAYMENT,
                NotificationCategory::PROGRESS,
                NotificationCategory::ALERT,
            ],

            // Supervisor: monitoring-focused categories
            'supervisor' => [
                NotificationCategory::SESSION,
                NotificationCategory::ATTENDANCE,
                NotificationCategory::HOMEWORK,
                NotificationCategory::PROGRESS,
                NotificationCategory::REVIEW,
                NotificationCategory::ALERT,
            ],

            // Default: show all
            default => null,
        };
    }
}
