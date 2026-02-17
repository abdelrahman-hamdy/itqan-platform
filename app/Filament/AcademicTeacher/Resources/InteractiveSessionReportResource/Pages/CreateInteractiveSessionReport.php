<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveSessionReportResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;

class CreateInteractiveSessionReport extends CreateRecord
{
    protected static string $resource = InteractiveSessionReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
