<?php

use App\Enums\UserType;
use Illuminate\Support\Facades\Auth;

if (! function_exists('getAuthenticatedStudentGender')) {
    /**
     * Get the gender of the currently authenticated student.
     * Returns null if the user is not authenticated or is not a student.
     */
    function getAuthenticatedStudentGender(): ?string
    {
        $user = Auth::user();

        if (! $user || $user->user_type !== UserType::STUDENT->value) {
            return null;
        }

        return $user->studentProfile?->gender ?? $user->gender;
    }
}
