<?php

namespace App\Filament\Teacher\Widgets;

use App\Filament\Shared\Widgets\BaseEarningsOverviewWidget;
use App\Models\QuranTeacherProfile;
use Illuminate\Support\Facades\Auth;

class EarningsOverviewWidget extends BaseEarningsOverviewWidget
{
    /**
     * Get the Quran teacher profile for the current user.
     */
    protected function getTeacherProfileData(): ?array
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        if (! $teacherProfile) {
            return null;
        }

        return [
            'profile' => $teacherProfile,
            'type_class' => QuranTeacherProfile::class,
        ];
    }
}
