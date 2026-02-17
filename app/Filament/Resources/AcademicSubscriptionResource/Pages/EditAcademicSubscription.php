<?php

namespace App\Filament\Resources\AcademicSubscriptionResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditAcademicSubscription extends EditRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
