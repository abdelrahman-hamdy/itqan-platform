<?php

namespace App\Filament\Teacher\Widgets;

use App\Filament\Shared\Traits\FormatsCalendarData;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class ColorIndicatorsWidget extends Widget
{
    use FormatsCalendarData;

    protected static string $view = 'filament.teacher.widgets.color-indicators';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2; // Render after calendar widget (sort 1)

    /**
     * Get session type color indicators based on teacher type
     */
    public function getSessionTypeIndicators(): array
    {
        $user = Auth::user();

        if ($user?->user_type === 'quran_teacher') {
            return $this->getQuranSessionTypeIndicators();
        } elseif ($user?->user_type === 'academic_teacher') {
            return $this->getAcademicSessionTypeIndicators();
        }

        return [];
    }

    /**
     * Get status color indicators
     */
    public function getStatusIndicators(): array
    {
        return $this->getStatusColorIndicators();
    }

    /**
     * Get session type indicators for Quran teachers
     */
    protected function getQuranSessionTypeIndicators(): array
    {
        $colorScheme = $this->getColorScheme('quran_teacher');

        return [
            [
                'color' => $colorScheme['group']['color'],
                'label' => $colorScheme['group']['label'],
                'icon' => $colorScheme['group']['icon'],
            ],
            [
                'color' => $colorScheme['individual']['color'],
                'label' => $colorScheme['individual']['label'],
                'icon' => $colorScheme['individual']['icon'],
            ],
            [
                'color' => $colorScheme['trial']['color'],
                'label' => $colorScheme['trial']['label'],
                'icon' => $colorScheme['trial']['icon'],
            ],
        ];
    }

    /**
     * Get session type indicators for Academic teachers
     */
    protected function getAcademicSessionTypeIndicators(): array
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
}
