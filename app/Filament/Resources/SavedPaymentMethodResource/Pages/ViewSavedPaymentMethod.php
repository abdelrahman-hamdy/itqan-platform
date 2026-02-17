<?php

namespace App\Filament\Resources\SavedPaymentMethodResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Resources\SavedPaymentMethodResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewSavedPaymentMethod extends ViewRecord
{
    protected static string $resource = SavedPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
        ];
    }
}
