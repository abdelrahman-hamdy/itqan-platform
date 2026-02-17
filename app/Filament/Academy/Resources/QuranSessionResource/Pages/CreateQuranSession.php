<?php

namespace App\Filament\Academy\Resources\QuranSessionResource\Pages;

use App\Filament\Academy\Resources\QuranSessionResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateQuranSession extends CreateRecord
{
    protected static string $resource = QuranSessionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = auth()->user()->academy_id;

        return $data;
    }
}
