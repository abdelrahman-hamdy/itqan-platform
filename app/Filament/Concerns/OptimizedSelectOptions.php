<?php

namespace App\Filament\Concerns;

use App\Models\AcademicGradeLevel;
use App\Models\AcademicPackage;
use App\Models\AcademicSubject;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranCircle;
use App\Models\QuranPackage;
use App\Models\QuranTeacherProfile;
use App\Models\User;
use App\Services\AcademyContextService;
use Closure;

/**
 * Trait OptimizedSelectOptions
 *
 * Provides optimized, academy-filtered select options for Filament resources.
 * Uses searchable AJAX loading instead of mass-loading all options.
 *
 * Benefits:
 * - Prevents N+1 queries and memory issues
 * - Enforces tenant isolation (academy filtering)
 * - Consistent pattern across all resources
 *
 * Usage in Filament resources:
 *
 * use App\Filament\Concerns\OptimizedSelectOptions;
 *
 * class MyResource extends Resource
 * {
 *     use OptimizedSelectOptions;
 *
 *     public static function form(Form $form): Form
 *     {
 *         return $form->schema([
 *             Forms\Components\Select::make('student_id')
 *                 ->label('الطالب')
 *                 ->searchable()
 *                 ->preload(false)
 *                 ->getSearchResultsUsing(static::getStudentSearchResults())
 *                 ->getOptionLabelUsing(static::getStudentOptionLabel())
 *                 ->required(),
 *         ]);
 *     }
 * }
 */
trait OptimizedSelectOptions
{
    /**
     * Default limit for search results.
     */
    protected static int $searchResultLimit = 50;

    /**
     * Get academy-filtered student search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getStudentSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return User::query()
                ->where('user_type', 'student')
                ->with('studentProfile')
                ->when($academyId, function ($query) use ($academyId) {
                    $query->whereHas('studentProfile.gradeLevel', function ($q) use ($academyId) {
                        $q->where('academy_id', $academyId);
                    });
                })
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('studentProfile', function ($sq) use ($search) {
                            $sq->where('student_code', 'like', "%{$search}%");
                        });
                })
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($user) {
                    $label = $user->studentProfile?->display_name ?? $user->name;
                    if ($user->studentProfile?->student_code) {
                        $label .= " ({$user->studentProfile->student_code})";
                    }

                    return [$user->id => $label];
                })
                ->toArray();
        };
    }

    /**
     * Get student option label closure for displaying selected values.
     */
    protected static function getStudentOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $user = User::with('studentProfile')->find($value);
            if (! $user) {
                return 'طالب #'.$value;
            }

            $label = $user->studentProfile?->display_name ?? $user->name;
            if ($user->studentProfile?->student_code) {
                $label .= " ({$user->studentProfile->student_code})";
            }

            return $label;
        };
    }

    /**
     * Get academy-filtered Quran teacher search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getQuranTeacherSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return QuranTeacherProfile::query()
                ->with('user')
                ->whereHas('user', fn ($q) => $q->where('active_status', true))
                ->when($academyId, function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })
                ->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($profile) {
                    return [$profile->id => $profile->user?->name ?? 'معلم #'.$profile->id];
                })
                ->toArray();
        };
    }

    /**
     * Get Quran teacher option label closure.
     */
    protected static function getQuranTeacherOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $profile = QuranTeacherProfile::with('user')->find($value);

            return $profile?->user?->name ?? 'معلم #'.$value;
        };
    }

    /**
     * Get academy-filtered Academic teacher search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getAcademicTeacherSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return AcademicTeacherProfile::query()
                ->with('user')
                ->whereHas('user', fn ($q) => $q->where('active_status', true))
                ->when($academyId, function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })
                ->whereHas('user', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($profile) {
                    return [$profile->id => $profile->user?->name ?? 'معلم #'.$profile->id];
                })
                ->toArray();
        };
    }

    /**
     * Get Academic teacher option label closure.
     */
    protected static function getAcademicTeacherOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $profile = AcademicTeacherProfile::with('user')->find($value);

            return $profile?->user?->name ?? 'معلم #'.$value;
        };
    }

    /**
     * Get academy-filtered Academic package search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getAcademicPackageSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return AcademicPackage::query()
                ->where('is_active', true)
                ->when($academyId, function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($package) {
                    $label = $package->name;
                    if ($package->sessions_per_month) {
                        $label .= " ({$package->sessions_per_month} جلسة/شهر)";
                    }

                    return [$package->id => $label];
                })
                ->toArray();
        };
    }

    /**
     * Get Academic package option label closure.
     */
    protected static function getAcademicPackageOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $package = AcademicPackage::find($value);
            if (! $package) {
                return 'باقة #'.$value;
            }

            $label = $package->name;
            if ($package->sessions_per_month) {
                $label .= " ({$package->sessions_per_month} جلسة/شهر)";
            }

            return $label;
        };
    }

    /**
     * Get academy-filtered Quran package search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getQuranPackageSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return QuranPackage::query()
                ->where('is_active', true)
                ->when($academyId, function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($package) {
                    $label = $package->name;
                    if ($package->sessions_per_month) {
                        $label .= " ({$package->sessions_per_month} جلسة/شهر)";
                    }

                    return [$package->id => $label];
                })
                ->toArray();
        };
    }

    /**
     * Get Quran package option label closure.
     */
    protected static function getQuranPackageOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $package = QuranPackage::find($value);
            if (! $package) {
                return 'باقة #'.$value;
            }

            $label = $package->name;
            if ($package->sessions_per_month) {
                $label .= " ({$package->sessions_per_month} جلسة/شهر)";
            }

            return $label;
        };
    }

    /**
     * Get academy-filtered Quran circle search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getQuranCircleSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return QuranCircle::query()
                ->with('teacher.user')
                ->where('is_active', true)
                ->when($academyId, function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('circle_code', 'like', "%{$search}%")
                        ->orWhereHas('teacher.user', function ($tq) use ($search) {
                            $tq->where('name', 'like', "%{$search}%");
                        });
                })
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($circle) {
                    $label = $circle->name;
                    if ($circle->teacher?->user?->name) {
                        $label .= " - المعلم: {$circle->teacher->user->name}";
                    }

                    return [$circle->id => $label];
                })
                ->toArray();
        };
    }

    /**
     * Get Quran circle option label closure.
     */
    protected static function getQuranCircleOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $circle = QuranCircle::with('teacher.user')->find($value);
            if (! $circle) {
                return 'حلقة #'.$value;
            }

            $label = $circle->name;
            if ($circle->teacher?->user?->name) {
                $label .= " - المعلم: {$circle->teacher->user->name}";
            }

            return $label;
        };
    }

    /**
     * Get academy-filtered grade level search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getGradeLevelSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return AcademicGradeLevel::query()
                ->when($academyId, function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })
                ->where('name', 'like', "%{$search}%")
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($level) {
                    return [$level->id => $level->name];
                })
                ->toArray();
        };
    }

    /**
     * Get grade level option label closure.
     */
    protected static function getGradeLevelOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $level = AcademicGradeLevel::find($value);

            return $level?->name ?? 'مستوى #'.$value;
        };
    }

    /**
     * Get academy-filtered academic subject search results closure.
     *
     * @param  int|null  $customLimit  Override the default limit
     * @param  bool  $requireAcademy  Whether to require academy context
     */
    protected static function getAcademicSubjectSearchResults(?int $customLimit = null, bool $requireAcademy = false): Closure
    {
        $limit = $customLimit ?? static::$searchResultLimit;

        return function (string $search) use ($limit, $requireAcademy): array {
            $academyId = AcademyContextService::getCurrentAcademyId();

            if ($requireAcademy && ! $academyId) {
                return [];
            }

            return AcademicSubject::query()
                ->where('is_active', true)
                ->when($academyId, function ($query) use ($academyId) {
                    $query->where('academy_id', $academyId);
                })
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')
                ->limit($limit)
                ->get()
                ->mapWithKeys(function ($subject) {
                    return [$subject->id => $subject->name];
                })
                ->toArray();
        };
    }

    /**
     * Get academic subject option label closure.
     */
    protected static function getAcademicSubjectOptionLabel(): Closure
    {
        return function ($value): ?string {
            if (! $value) {
                return null;
            }

            $subject = AcademicSubject::find($value);

            return $subject?->name ?? 'مادة #'.$value;
        };
    }

    /**
     * Create a fully configured searchable student select field.
     *
     * @param  string  $name  Field name
     * @param  string  $label  Field label
     * @param  bool  $required  Whether the field is required
     */
    protected static function makeStudentSelect(string $name = 'student_id', string $label = 'الطالب', bool $required = true): \Filament\Forms\Components\Select
    {
        return \Filament\Forms\Components\Select::make($name)
            ->label($label)
            ->searchable()
            ->preload(false)
            ->getSearchResultsUsing(static::getStudentSearchResults())
            ->getOptionLabelUsing(static::getStudentOptionLabel())
            ->required($required);
    }

    /**
     * Create a fully configured searchable Quran teacher select field.
     *
     * @param  string  $name  Field name
     * @param  string  $label  Field label
     * @param  bool  $required  Whether the field is required
     */
    protected static function makeQuranTeacherSelect(string $name = 'quran_teacher_profile_id', string $label = 'المعلم', bool $required = true): \Filament\Forms\Components\Select
    {
        return \Filament\Forms\Components\Select::make($name)
            ->label($label)
            ->searchable()
            ->preload(false)
            ->getSearchResultsUsing(static::getQuranTeacherSearchResults())
            ->getOptionLabelUsing(static::getQuranTeacherOptionLabel())
            ->required($required);
    }

    /**
     * Create a fully configured searchable Academic teacher select field.
     *
     * @param  string  $name  Field name
     * @param  string  $label  Field label
     * @param  bool  $required  Whether the field is required
     */
    protected static function makeAcademicTeacherSelect(string $name = 'academic_teacher_profile_id', string $label = 'المعلم', bool $required = true): \Filament\Forms\Components\Select
    {
        return \Filament\Forms\Components\Select::make($name)
            ->label($label)
            ->searchable()
            ->preload(false)
            ->getSearchResultsUsing(static::getAcademicTeacherSearchResults())
            ->getOptionLabelUsing(static::getAcademicTeacherOptionLabel())
            ->required($required);
    }

    /**
     * Create a fully configured searchable Quran circle select field.
     *
     * @param  string  $name  Field name
     * @param  string  $label  Field label
     * @param  bool  $required  Whether the field is required
     */
    protected static function makeQuranCircleSelect(string $name = 'quran_circle_id', string $label = 'الحلقة', bool $required = true): \Filament\Forms\Components\Select
    {
        return \Filament\Forms\Components\Select::make($name)
            ->label($label)
            ->searchable()
            ->preload(false)
            ->getSearchResultsUsing(static::getQuranCircleSearchResults())
            ->getOptionLabelUsing(static::getQuranCircleOptionLabel())
            ->required($required);
    }
}
