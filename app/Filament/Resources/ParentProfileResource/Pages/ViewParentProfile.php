<?php

namespace App\Filament\Resources\ParentProfileResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\ParentProfileResource;
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
