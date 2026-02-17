<?php

namespace App\Filament\Resources\PaymentSettingsResource\Pages;

use App\Filament\Resources\PaymentSettingsResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditPaymentSettings extends EditRecord
{
    protected static string $resource = PaymentSettingsResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
