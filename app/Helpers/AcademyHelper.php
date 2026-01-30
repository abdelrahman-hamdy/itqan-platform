<?php

namespace App\Helpers;

use App\Models\Academy;
use Illuminate\Support\Facades\Session;

class AcademyHelper
{
    public static function getCurrentAcademy(): ?Academy
    {
        // Always fetch fresh from database to get latest settings (like currency)
        $academyId = Session::get('selected_academy_id');
        if ($academyId) {
            return Academy::find($academyId);
        }
        return null;
    }

    public static function getCurrentAcademyId(): ?int
    {
        return Session::get('selected_academy_id');
    }

    public static function setCurrentAcademy(Academy $academy): void
    {
        // Only store the ID, not the object (to avoid stale data when settings change)
        Session::put('selected_academy_id', $academy->id);
        Session::forget('selected_academy'); // Clear any cached object
        app()->forgetInstance('current_academy'); // Don't use app container cache either
    }

    public static function clearCurrentAcademy(): void
    {
        Session::forget('selected_academy_id');
        Session::forget('selected_academy');
        app()->forgetInstance('current_academy');
    }

    public static function hasAcademySelected(): bool
    {
        return Session::has('selected_academy_id');
    }

    public static function getAcademyQuery(): array
    {
        $academyId = self::getCurrentAcademyId();

        return $academyId ? ['academy_id' => $academyId] : [];
    }
}
