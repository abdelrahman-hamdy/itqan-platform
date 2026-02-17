<?php

namespace App\Filament\Pages;

use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

abstract class BaseEditRecord extends EditRecord
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
