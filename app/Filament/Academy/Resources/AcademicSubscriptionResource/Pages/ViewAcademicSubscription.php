<?php

namespace App\Filament\Academy\Resources\AcademicSubscriptionResource\Pages;

use App\Filament\Academy\Resources\AcademicSubscriptionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewAcademicSubscription extends ViewRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            ...AcademicSubscriptionResource::getSubscriptionViewActions(),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
