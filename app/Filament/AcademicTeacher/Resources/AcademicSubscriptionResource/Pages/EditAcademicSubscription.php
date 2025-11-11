<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicSubscriptionResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSubscription extends EditRecord
{
    protected static string $resource = AcademicSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
