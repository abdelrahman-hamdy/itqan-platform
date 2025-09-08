<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateInteractiveCourse extends CreateRecord
{
    protected static string $resource = InteractiveCourseResource::class;

    public function getTitle(): string
    {
        return 'إنشاء دورة تفاعلية جديدة';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        $data['academy_id'] = $teacherProfile->academy_id;
        $data['assigned_teacher_id'] = $teacherProfile->id;
        $data['created_by'] = $user->id;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
