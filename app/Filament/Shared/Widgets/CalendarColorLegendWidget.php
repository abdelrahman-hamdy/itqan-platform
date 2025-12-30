<?php

declare(strict_types=1);

namespace App\Filament\Shared\Widgets;

use App\Enums\CalendarSessionType;
use App\Enums\SessionStatus;
use App\Services\Calendar\CalendarConfiguration;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

/**
 * Calendar Color Legend Widget
 *
 * Displays a color legend for the unified calendar.
 * Shows session type colors and status indicators dynamically
 * based on the user's role.
 *
 * @see \App\Filament\Shared\Widgets\UnifiedCalendarWidget
 */
class CalendarColorLegendWidget extends Widget
{
    protected static string $view = 'filament.shared.widgets.calendar-color-legend';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    /**
     * Get session type colors based on user's role
     *
     * @return array<int, array{value: string, color: string, label: string, icon: string}>
     */
    public function getSessionTypes(): array
    {
        $configuration = $this->getConfiguration();

        return collect($configuration->getSessionTypes())
            ->map(fn (CalendarSessionType $type) => [
                'value' => $type->value,
                'color' => $type->hexColor(),
                'label' => $type->fallbackLabel(),
                'icon' => $type->icon(),
                'tailwindColor' => $type->tailwindColor(),
            ])
            ->toArray();
    }

    /**
     * Get status color indicators
     *
     * @return array<int, array{status: string, color: string, label: string}>
     */
    public function getStatusIndicators(): array
    {
        // Only show relevant statuses
        $relevantStatuses = [
            SessionStatus::SCHEDULED,
            SessionStatus::READY,
            SessionStatus::ONGOING,
            SessionStatus::COMPLETED,
            SessionStatus::CANCELLED,
            SessionStatus::ABSENT,
        ];

        return collect($relevantStatuses)
            ->map(fn (SessionStatus $status) => [
                'status' => $status->value,
                'color' => $status->hexColor(),
                'label' => $status->label(),
                'icon' => $status->icon(),
            ])
            ->toArray();
    }

    /**
     * Get the calendar configuration for the current user
     */
    protected function getConfiguration(): CalendarConfiguration
    {
        $user = Auth::user();

        if (! $user) {
            // Return a default configuration if no user
            return CalendarConfiguration::forQuranTeacher();
        }

        return CalendarConfiguration::forUser($user);
    }

    /**
     * Get data to pass to the view
     */
    protected function getViewData(): array
    {
        return [
            'sessionTypes' => $this->getSessionTypes(),
            'statusIndicators' => $this->getStatusIndicators(),
            'showSessionTypes' => true,
            'showStatusIndicators' => true,
        ];
    }
}
