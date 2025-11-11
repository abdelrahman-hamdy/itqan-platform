<?php

namespace App\Helpers;

use App\Models\Academy;
use Illuminate\Support\Facades\Session;

class AcademyHelper
{
    public static function getCurrentAcademy(): ?Academy
    {
        return Session::get('selected_academy');
    }

    public static function getCurrentAcademyId(): ?int
    {
        return Session::get('selected_academy_id');
    }

    public static function setCurrentAcademy(Academy $academy): void
    {
        Session::put('selected_academy_id', $academy->id);
        Session::put('selected_academy', $academy);
        app()->instance('current_academy', $academy);
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