<?php

namespace App\Filament\Academy\Resources\QuranSubscriptionResource\Pages;

use App\Filament\Academy\Resources\QuranSubscriptionResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewQuranSubscription extends ViewRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            ...QuranSubscriptionResource::getSubscriptionViewActions(),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
