<?php

namespace App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages;

use App\Filament\Academy\Resources\SavedPaymentMethodResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
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
