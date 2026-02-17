<?php

namespace App\Filament\Academy\Resources\StudentSessionReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Academy\Resources\StudentSessionReportResource;
use App\Filament\Pages\BaseEditRecord as EditRecord;

class EditStudentSessionReport extends EditRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
