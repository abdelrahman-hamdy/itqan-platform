<?php

namespace App\Filament\Resources\SuperAdminQuranTrialRequestResource\Pages;

use App\Filament\Resources\SuperAdminQuranTrialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSuperAdminQuranTrialRequest extends ViewRecord
{
    protected static string $resource = SuperAdminQuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}