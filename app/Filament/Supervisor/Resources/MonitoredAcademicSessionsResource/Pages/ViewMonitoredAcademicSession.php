<?php

namespace App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Supervisor\Resources\MonitoredAcademicSessionsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewMonitoredAcademicSession extends ViewRecord
{
    protected static string $resource = MonitoredAcademicSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()
                ->label('حذف')
                ->successRedirectUrl(fn () => MonitoredAcademicSessionsResource::getUrl('index')),
        ];
    }
}
