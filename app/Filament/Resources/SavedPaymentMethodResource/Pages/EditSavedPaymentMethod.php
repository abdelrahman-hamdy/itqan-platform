<?php

namespace App\Filament\Resources\SavedPaymentMethodResource\Pages;

use App\Filament\Resources\SavedPaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSavedPaymentMethod extends EditRecord
{
    protected static string $resource = SavedPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض'),

            Actions\DeleteAction::make()
                ->label('حذف'),

            Actions\RestoreAction::make()
                ->label('استعادة'),
        ];
    }
}
