<?php

namespace App\Filament\Teacher\Resources\QuranTrialRequestResource\Pages;

use App\Enums\TrialRequestStatus;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Teacher\Resources\QuranTrialRequestResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;

class ViewQuranTrialRequest extends ViewRecord
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('complete')
                ->label('تحديد كمكتمل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => TrialRequestStatus::COMPLETED]))
                ->visible(fn () => $this->record->status === TrialRequestStatus::SCHEDULED),
            Action::make('cancel')
                ->label('إلغاء الطلب')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => TrialRequestStatus::CANCELLED]))
                ->visible(fn () => $this->record->status?->isActive() ?? false),
        ];
    }
}
