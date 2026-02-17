<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

abstract class BaseCreateRecord extends CreateRecord
{
    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function defaultForm(Schema $schema): Schema
    {
        return parent::defaultForm($schema)->columns(1);
    }
}
