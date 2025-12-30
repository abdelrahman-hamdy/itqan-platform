<?php

namespace App\Filament\Resources\TeacherPayoutResource\Pages;

use App\Filament\Resources\TeacherPayoutResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTeacherPayout extends ViewRecord
{
    protected static string $resource = TeacherPayoutResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
