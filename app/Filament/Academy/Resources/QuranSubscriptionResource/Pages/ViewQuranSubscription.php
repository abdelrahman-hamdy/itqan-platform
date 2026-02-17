<?php

namespace App\Filament\Academy\Resources\QuranSubscriptionResource\Pages;

use App\Filament\Academy\Resources\QuranSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranSubscription extends ViewRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
