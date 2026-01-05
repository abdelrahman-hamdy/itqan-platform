<?php

namespace App\Filament\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\Resources\AcademicIndividualLessonResource;
use App\Filament\Resources\AcademicSessionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicIndividualLesson extends ViewRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    public function getTitle(): string
    {
        return 'الدرس الفردي: ' . $this->record->lesson_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
            Actions\Action::make('view_sessions')
                ->label('عرض الجلسات')
                ->icon('heroicon-o-calendar-days')
                ->url(fn (): string => AcademicSessionResource::getUrl('index', [
                    'tableFilters[academic_individual_lesson_id][value]' => $this->record->id,
                ])),
            Actions\DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
