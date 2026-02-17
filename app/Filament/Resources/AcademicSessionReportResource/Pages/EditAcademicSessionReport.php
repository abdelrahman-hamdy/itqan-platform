<?php

namespace App\Filament\Resources\AcademicSessionReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\AcademicSessionReportResource;
use Filament\Actions;
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
