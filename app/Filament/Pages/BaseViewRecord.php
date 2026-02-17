<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

abstract class BaseViewRecord extends ViewRecord
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
