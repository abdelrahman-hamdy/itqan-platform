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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if ($this->record->user) {
            $data['first_name'] = $this->record->user->first_name;
            $data['last_name'] = $this->record->user->last_name;
            $data['email'] = $this->record->user->email;
            $data['phone'] = $this->record->user->phone;
        }

        return $data;
    }

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
                ->action(function () {
                    if ($user = $this->record->user) {
                        $user->active_status = ! $user->active_status;
                        $user->save();
                    }
                })
                ->visible(fn () => $this->record->user !== null),
            Action::make('toggle_fully_booked')
                ->label(fn () => $this->record->is_fully_booked ? __('teacher.mark_available') : __('teacher.mark_fully_booked'))
                ->icon(fn () => $this->record->is_fully_booked ? 'heroicon-o-check-circle' : 'heroicon-o-no-symbol')
                ->color(fn () => $this->record->is_fully_booked ? 'success' : 'warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['is_fully_booked' => ! $this->record->is_fully_booked]);
                }),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
