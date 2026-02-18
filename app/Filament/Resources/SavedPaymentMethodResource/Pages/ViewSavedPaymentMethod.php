<?php

namespace App\Filament\Resources\SavedPaymentMethodResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\SavedPaymentMethodResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewSavedPaymentMethod extends ViewRecord
{
    protected static string $resource = SavedPaymentMethodResource::class;

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
