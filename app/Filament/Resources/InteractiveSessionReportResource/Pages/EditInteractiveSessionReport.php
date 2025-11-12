<?php

namespace App\Filament\Resources\InteractiveSessionReportResource\Pages;

use App\Filament\Resources\InteractiveSessionReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInteractiveSessionReport extends EditRecord
{
    protected static string $resource = InteractiveSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
