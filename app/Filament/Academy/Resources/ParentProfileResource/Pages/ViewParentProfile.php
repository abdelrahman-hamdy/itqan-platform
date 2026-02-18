<?php

namespace App\Filament\Academy\Resources\ParentProfileResource\Pages;

use App\Filament\Academy\Resources\ParentProfileResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewParentProfile extends ViewRecord
{
    protected static string $resource = ParentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
