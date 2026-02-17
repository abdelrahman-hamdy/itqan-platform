<?php

namespace App\Filament\Resources\AcademicIndividualLessonResource\Pages;

use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Models\AcademicIndividualLesson;
use App\Filament\Resources\AcademicIndividualLessonResource;
use App\Filament\Resources\AcademicSessionResource;
use Filament\Actions;
use App\Filament\Pages\BaseViewRecord as ViewRecord;

/**
 * @property AcademicIndividualLesson $record
 */
class ViewAcademicIndividualLesson extends ViewRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    public function getTitle(): string
    {
        return 'الدرس الفردي: '.$this->record->lesson_code;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل'),
            Action::make('view_sessions')
                ->label('عرض الجلسات')
                ->icon('heroicon-o-calendar-days')
                ->url(fn (): string => AcademicSessionResource::getUrl('index', [
                    'tableFilters[academic_individual_lesson_id][value]' => $this->record->id,
                ])),
            DeleteAction::make()
                ->label('حذف'),
        ];
    }
}
