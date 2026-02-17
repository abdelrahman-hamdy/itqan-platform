<?php

namespace App\Filament\Academy\Resources\SavedPaymentMethodResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\SavedPaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSavedPaymentMethod extends EditRecord
{
    protected static string $resource = SavedPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
