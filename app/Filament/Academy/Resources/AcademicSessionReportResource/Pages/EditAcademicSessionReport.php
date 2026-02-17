<?php

namespace App\Filament\Academy\Resources\AcademicSessionReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Academy\Resources\AcademicSessionReportResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;

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
