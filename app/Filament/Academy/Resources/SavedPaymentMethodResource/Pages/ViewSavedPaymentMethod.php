<?php

namespace App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\SavedPaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

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
