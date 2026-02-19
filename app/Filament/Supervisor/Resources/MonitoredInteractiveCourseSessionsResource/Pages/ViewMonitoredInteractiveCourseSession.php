<?php

namespace App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Supervisor\Resources\MonitoredInteractiveCourseSessionsResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewMonitoredInteractiveCourseSession extends ViewRecord
{
    protected static string $resource = MonitoredInteractiveCourseSessionsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('تعديل'),
            DeleteAction::make()
                ->label('حذف')
                ->successRedirectUrl(fn () => MonitoredInteractiveCourseSessionsResource::getUrl('index')),
        ];
    }
}
