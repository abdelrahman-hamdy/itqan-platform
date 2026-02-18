<?php

namespace App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource\Pages;

use App\Enums\TrialRequestStatus;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Supervisor\Resources\MonitoredTrialRequestsResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;

class ViewMonitoredTrialRequest extends ViewRecord
{
    protected static string $resource = MonitoredTrialRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            MonitoredTrialRequestsResource::makeScheduleAction(),
            Action::make('cancel')
                ->label('إلغاء الطلب')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => TrialRequestStatus::CANCELLED]))
                ->visible(fn () => in_array($this->record->status, [TrialRequestStatus::PENDING, TrialRequestStatus::SCHEDULED])),
        ];
    }
}
