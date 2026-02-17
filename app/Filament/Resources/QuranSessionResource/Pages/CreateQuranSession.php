<?php

namespace App\Filament\Resources\QuranSessionResource\Pages;

use App\Filament\Resources\QuranSessionResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateQuranSession extends CreateRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
