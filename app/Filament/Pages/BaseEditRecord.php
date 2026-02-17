<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

abstract class BaseEditRecord extends EditRecord
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
