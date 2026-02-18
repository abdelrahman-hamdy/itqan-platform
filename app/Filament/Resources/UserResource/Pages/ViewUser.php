<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\UserResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('toggle_active')
                ->label(fn () => $this->record->active_status ? 'تعطيل الحساب' : 'تفعيل الحساب')
                ->icon(fn () => $this->record->active_status ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                ->color(fn () => $this->record->active_status ? 'warning' : 'success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['active_status' => ! $this->record->active_status])),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
