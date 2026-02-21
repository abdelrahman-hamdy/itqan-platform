<?php

namespace App\Filament\Resources\QuranTrialRequestResource\Pages;

use App\Enums\TrialRequestStatus;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\QuranTrialRequestResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

/** @property \App\Models\QuranTrialRequest $record */
class ViewQuranTrialRequest extends ViewRecord
{
    protected static string $resource = QuranTrialRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            QuranTrialRequestResource::makeScheduleAction(),
            Action::make('cancel')
                ->label('إلغاء الطلب')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['status' => TrialRequestStatus::CANCELLED]))
                ->visible(fn () => $this->record->status?->isActive() ?? false),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
