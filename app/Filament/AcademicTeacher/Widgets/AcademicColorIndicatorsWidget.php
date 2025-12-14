<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Filament\Shared\Traits\FormatsCalendarData;
use Filament\Widgets\Widget;

class AcademicColorIndicatorsWidget extends Widget
{
    use FormatsCalendarData;

    // Prevent auto-discovery - only used on calendar page
    protected static bool $isDiscoverable = false;

    protected static string $view = 'filament.academic-teacher.widgets.academic-color-indicators';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2; // Render after calendar widget (sort 1)

    /**
     * Get session type color indicators for academic teacher
     */
    public function getSessionTypeIndicators(): array
    {
        $colorScheme = $this->getColorScheme('academic_teacher');

        return [
            [
                'color' => $colorScheme['private_lesson']['color'],
                'label' => $colorScheme['private_lesson']['label'],
                'icon' => $colorScheme['private_lesson']['icon'],
            ],
            [
                'color' => $colorScheme['interactive_course']['color'],
                'label' => $colorScheme['interactive_course']['label'],
                'icon' => $colorScheme['interactive_course']['icon'],
            ],
        ];
    }

    /**
     * Get status color indicators
     */
    public function getStatusIndicators(): array
    {
        return $this->getStatusColorIndicators();
    }
}
