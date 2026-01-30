<?php

namespace App\Filament\Resources\SavedPaymentMethodResource\Pages;

use App\Filament\Resources\SavedPaymentMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSavedPaymentMethods extends ListRecords
{
    protected static string $resource = SavedPaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action - payment methods are created via checkout/tokenization
        ];
    }
}
