<?php

namespace App\Filament\Resources\StudentProfileResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\StudentProfileResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

/** @property \App\Models\StudentProfile $record */
class ViewStudentProfile extends ViewRecord
{
    protected static string $resource = StudentProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('activate')
                ->label('تفعيل')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->user?->update(['active_status' => true]))
                ->visible(fn () => $this->record->user && ! $this->record->user->active_status),
            Action::make('deactivate')
                ->label('إيقاف')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn () => $this->record->user?->update(['active_status' => false]))
                ->visible(fn () => $this->record->user && $this->record->user->active_status),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
