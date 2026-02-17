<?php

namespace App\Filament\Academy\Resources\QuranSubscriptionResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\QuranSubscriptionResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

class ViewQuranSubscription extends ViewRecord
{
    protected static string $resource = QuranSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
