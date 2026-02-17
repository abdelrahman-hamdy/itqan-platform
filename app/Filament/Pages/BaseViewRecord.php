<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

abstract class BaseViewRecord extends ViewRecord
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)->columns(1);
    }

    public function defaultInfolist(Schema $schema): Schema
    {
        return parent::defaultInfolist($schema)->columns(1);
    }
}
