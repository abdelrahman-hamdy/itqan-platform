<?php

namespace App\Filament\Academy\Resources\InteractiveSessionReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Academy\Resources\InteractiveSessionReportResource;
use Filament\Resources\Pages\EditRecord;

class EditInteractiveSessionReport extends EditRecord
{
    protected static string $resource = InteractiveSessionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
