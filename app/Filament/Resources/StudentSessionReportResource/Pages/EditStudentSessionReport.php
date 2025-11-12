<?php

namespace App\Filament\Resources\StudentSessionReportResource\Pages;

use App\Filament\Resources\StudentSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStudentSessionReport extends EditRecord
{
    protected static string $resource = StudentSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
