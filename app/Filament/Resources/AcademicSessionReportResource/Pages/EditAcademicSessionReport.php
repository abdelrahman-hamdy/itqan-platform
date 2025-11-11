<?php

namespace App\Filament\Resources\AcademicSessionReportResource\Pages;

use App\Filament\Resources\AcademicSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSessionReport extends EditRecord
{
    protected static string $resource = AcademicSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
