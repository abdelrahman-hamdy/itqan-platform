<?php

namespace App\Filament\Teacher\Widgets;

use Filament\Widgets\Widget;

class ColorIndicatorsWidget extends Widget
{
    protected static string $view = 'filament.teacher.widgets.color-indicators';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 2; // Render after calendar widget (sort 1)
}
