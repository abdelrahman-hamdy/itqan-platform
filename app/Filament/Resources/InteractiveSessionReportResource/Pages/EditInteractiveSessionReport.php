<?php

namespace App\Filament\Resources\InteractiveSessionReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\InteractiveSessionReportResource;
use Filament\Actions;
use App\Filament\Pages\BaseEditRecord as EditRecord;

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
