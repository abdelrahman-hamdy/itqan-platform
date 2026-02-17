<?php

namespace App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource\Pages;

use App\Filament\AcademicTeacher\Resources\AcademicIndividualLessonResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAcademicIndividualLesson extends CreateRecord
{
    protected static string $resource = AcademicIndividualLessonResource::class;

    public function getTitle(): string
    {
        return 'إنشاء درس فردي جديد';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        $data['academy_id'] = $teacherProfile->academy_id;
        $data['academic_teacher_id'] = $teacherProfile->id;
        $data['created_by'] = $user->id;
        $data['sessions_remaining'] = $data['total_sessions'] ?? 0;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
