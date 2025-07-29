<?php

namespace App\Filament\Resources\AcademicTeacherResource\Pages;

use App\Filament\Resources\AcademicTeacherResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAcademicTeacher extends ViewRecord
{
    protected static string $resource = AcademicTeacherResource::class;

    public function getTitle(): string
    {
        $teacherName = $this->record->user->name ?? 'معلم أكاديمي';
        return "المعلم الأكاديمي: {$teacherName}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('تعديل'),
        ];
    }
} 