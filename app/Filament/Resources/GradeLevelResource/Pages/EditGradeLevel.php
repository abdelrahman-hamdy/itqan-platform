<?php

namespace App\Filament\Resources\GradeLevelResource\Pages;

use App\Filament\Resources\GradeLevelResource; 
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGradeLevel extends EditRecord
{
    protected static string $resource = GradeLevelResource::class;

    public function getTitle(): string
    {
        return 'تعديل المرحلة الدراسية: ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->label('عرض')
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->label('حذف')
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث المرحلة الدراسية بنجاح';
    }
}
