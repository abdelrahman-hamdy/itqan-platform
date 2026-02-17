<?php

namespace App\Filament\Academy\Resources\StudentSessionReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Academy\Resources\StudentSessionReportResource;
use Filament\Resources\Pages\EditRecord;

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
