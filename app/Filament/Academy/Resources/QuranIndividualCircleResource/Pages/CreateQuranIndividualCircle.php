<?php

namespace App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Academy\Resources\QuranIndividualCircleResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateQuranIndividualCircle extends CreateRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['academy_id'] = auth()->user()->academy_id;

        return $data;
    }
}
