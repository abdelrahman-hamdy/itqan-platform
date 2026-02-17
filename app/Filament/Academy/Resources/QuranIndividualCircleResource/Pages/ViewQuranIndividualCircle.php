<?php

namespace App\Filament\Academy\Resources\QuranIndividualCircleResource\Pages;

use App\Filament\Academy\Resources\QuranIndividualCircleResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\EditAction;

class ViewQuranIndividualCircle extends ViewRecord
{
    protected static string $resource = QuranIndividualCircleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
