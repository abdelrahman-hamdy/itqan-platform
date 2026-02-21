<?php

namespace App\Filament\Academy\Resources\QuranPackageResource\Pages;

use App\Filament\Academy\Resources\QuranPackageResource;
use App\Filament\Pages\BaseViewRecord as ViewRecord;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

/** @property \App\Models\QuranPackage $record */
class ViewQuranPackage extends ViewRecord
{
    protected static string $resource = QuranPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('activate')
                ->label('تفعيل الباقة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_active' => true]))
                ->visible(fn () => ! $this->record->is_active),
            Action::make('deactivate')
                ->label('إلغاء تفعيل الباقة')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_active' => false]))
                ->visible(fn () => $this->record->is_active),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
