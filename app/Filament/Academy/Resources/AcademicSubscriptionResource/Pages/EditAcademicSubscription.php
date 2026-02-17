<?php

namespace App\Filament\Academy\Resources\AcademicSubscriptionResource\Pages;

use App\Filament\Academy\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSubscription extends EditRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }
}
