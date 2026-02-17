<?php

namespace App\Filament\Academy\Resources\AcademicSessionReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Academy\Resources\AcademicSessionReportResource;
use Filament\Resources\Pages\EditRecord;

class EditAcademicSessionReport extends EditRecord
{
    protected static string $resource = AcademicSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
