<?php

namespace App\Filament\Academy\Resources\AcademicSubscriptionResource\Pages;

use Filament\Actions\EditAction;
use App\Filament\Academy\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicSubscription extends ViewRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
