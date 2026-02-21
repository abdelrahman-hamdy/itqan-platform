<?php

namespace App\Filament\Academy\Resources\QuranTeacherProfileResource\Pages;

use App\Filament\Academy\Resources\QuranTeacherProfileResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

/** @property \App\Models\QuranTeacherProfile $record */
class ViewQuranTeacherProfile extends ViewRecord
{
    protected static string $resource = QuranTeacherProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('toggle_active')
                ->label(fn () => $this->record->user?->active_status ? 'تعطيل الحساب' : 'تفعيل الحساب')
                ->icon(fn () => $this->record->user?->active_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->user?->active_status ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->user?->update(['active_status' => ! $this->record->user->active_status]))
                ->visible(fn () => $this->record->user !== null),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
