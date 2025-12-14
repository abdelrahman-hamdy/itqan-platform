<?php

namespace App\Filament\Resources\QuranSessionResource\Pages;

use App\Filament\Resources\QuranSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuranSession extends CreateRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
