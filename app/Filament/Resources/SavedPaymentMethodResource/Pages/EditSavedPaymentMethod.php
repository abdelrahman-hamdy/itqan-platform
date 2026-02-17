<?php

namespace App\Filament\Resources\SavedPaymentMethodResource\Pages;

use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use App\Filament\Resources\SavedPaymentMethodResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditSavedPaymentMethod extends EditRecord
{
    protected static string $resource = SavedPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),

            DeleteAction::make()
                ->label('حذف'),

            RestoreAction::make()
                ->label('استعادة'),
        ];
    }
}
