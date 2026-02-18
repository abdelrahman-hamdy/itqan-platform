<?php

namespace App\Filament\Resources\RecordedCourseResource\Pages;

use App\Filament\Pages\BaseViewRecord as ViewRecord;
use App\Filament\Resources\RecordedCourseResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

class ViewRecordedCourse extends ViewRecord
{
    protected static string $resource = RecordedCourseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('publish')
                ->label('نشر الدورة')
                ->icon('heroicon-o-globe-alt')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_published' => true]))
                ->visible(fn () => ! $this->record->is_published),
            Action::make('unpublish')
                ->label('إلغاء النشر')
                ->icon('heroicon-o-eye-slash')
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['is_published' => false]))
                ->visible(fn () => $this->record->is_published),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
