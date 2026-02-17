<?php

namespace App\Filament\Academy\Resources\AcademicSubscriptionResource\Pages;

use Filament\Actions\ViewAction;
use App\Filament\Academy\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditAcademicSubscription extends EditRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
