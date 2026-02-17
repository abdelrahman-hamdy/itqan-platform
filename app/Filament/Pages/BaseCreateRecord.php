<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

abstract class BaseCreateRecord extends CreateRecord
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
