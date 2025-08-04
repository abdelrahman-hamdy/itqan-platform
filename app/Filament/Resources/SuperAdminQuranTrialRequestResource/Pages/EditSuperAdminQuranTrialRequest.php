<?php

namespace App\Filament\Resources\SuperAdminQuranTrialRequestResource\Pages;

use App\Filament\Resources\SuperAdminQuranTrialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSuperAdminQuranTrialRequest extends EditRecord
{
    protected static string $resource = SuperAdminQuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}