<?php

namespace App\Filament\Resources\SupervisorProfileResource\Pages;

use App\Filament\Resources\SupervisorProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSupervisorProfile extends ViewRecord
{
    protected static string $resource = SupervisorProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
} 