<?php

namespace App\Filament\AcademicTeacher\Widgets;

use App\Filament\Shared\Widgets\BaseEarningsOverviewWidget;
use App\Models\AcademicTeacherProfile;
use Illuminate\Support\Facades\Auth;

class EarningsOverviewWidget extends BaseEarningsOverviewWidget
{
    /**
     * Get the Academic teacher profile for the current user.
     */
    protected function getTeacherProfileData(): ?array
    {
        $user = Auth::user();
        $teacherProfile = $user->academicTeacherProfile;

        if (! $teacherProfile) {
            return null;
        }

        return [
            'profile' => $teacherProfile,
            'type_class' => AcademicTeacherProfile::class,
        ];
    }
}
