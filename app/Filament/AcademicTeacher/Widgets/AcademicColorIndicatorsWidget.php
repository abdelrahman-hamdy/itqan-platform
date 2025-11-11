<?php

namespace App\Filament\AcademicTeacher\Widgets;

use Filament\Widgets\Widget;

class AcademicColorIndicatorsWidget extends Widget
{
    protected static string $view = 'filament.academic-teacher.widgets.academic-color-indicators';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2; // Render after calendar widget (sort 1)
}
