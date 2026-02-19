<?php

namespace App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Supervisor\Resources\MonitoredQuranSessionsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewMonitoredQuranSession extends ViewRecord
{
    protected static string $resource = MonitoredQuranSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()
                ->label('حذف')
                ->successRedirectUrl(fn () => MonitoredQuranSessionsResource::getUrl('index')),
        ];
    }
}
