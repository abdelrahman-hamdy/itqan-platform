<?php

namespace App\Filament\AcademicTeacher\Resources\InteractiveCourseResource\Pages;

use App\Filament\AcademicTeacher\Resources\InteractiveCourseResource;
use App\Filament\Pages\BaseCreateRecord as CreateRecord;
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

        // Calculate total sessions
        if (isset($data['duration_weeks']) && isset($data['sessions_per_week'])) {
            $data['total_sessions'] = $data['duration_weeks'] * $data['sessions_per_week'];
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
