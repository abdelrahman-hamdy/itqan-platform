<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranTeacherProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use ApiResponses;

    /**
     * Get list of Quran teachers.
     */
    public function quranTeachers(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = QuranTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user']);

        // Filter by gender if provided
        if ($request->filled('gender')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('gender', $request->gender);
            });
        }

        // Search by name (through User relationship - personal info is on User model)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('rating', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'teachers' => collect($teachers->items())->map(fn ($teacher) => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'bio' => $teacher->bio_arabic,
                'educational_qualification' => $teacher->educational_qualification,
                'university' => $teacher->university,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'specializations' => $teacher->specializations ?? [],
                'hourly_rate' => $teacher->hourly_rate,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($teachers),
        ], __('Teachers retrieved successfully'));
    }

    /**
     * Get a specific Quran teacher.
     */
    public function showQuranTeacher(Request $request, int $id): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $teacher = QuranTeacherProfile::where('id', $id)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user'])
            ->first();

        if (! $teacher) {
            return $this->notFound(__('Teacher not found.'));
        }

        return $this->success([
            'teacher' => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'email' => $teacher->user?->email,
                'bio' => $teacher->bio_arabic,
                'bio_en' => $teacher->bio_english,
                'educational_qualification' => $teacher->educational_qualification,
                'university' => $teacher->university,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'certifications' => $teacher->certifications ?? [],
                'specializations' => $teacher->specializations ?? [],
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'total_sessions' => $teacher->total_sessions ?? 0,
                'hourly_rate' => $teacher->hourly_rate,
                'available_packages' => $teacher->packages?->where('is_active', true)->map(fn ($pkg) => [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'description' => $pkg->description,
                    'sessions_per_month' => $pkg->sessions_per_month,
                    'session_duration_minutes' => $pkg->session_duration_minutes,
                    'monthly_price' => $pkg->monthly_price,
                    'features' => $pkg->features ?? [],
                ])->toArray() ?? [],
            ],
        ], __('Teacher retrieved successfully'));
    }

    /**
     * Get list of Academic teachers.
     */
    public function academicTeachers(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = AcademicTeacherProfile::where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user']);

        // Filter by subject
        if ($request->filled('subject_id')) {
            $subjectId = $request->subject_id;
            $query->where(function ($q) use ($subjectId) {
                $q->whereJsonContains('subject_ids', (int) $subjectId)
                    ->orWhereJsonContains('subject_ids', (string) $subjectId);
            });
        }

        // Filter by grade level
        if ($request->filled('grade_level_id')) {
            $gradeLevelId = $request->grade_level_id;
            $query->where(function ($q) use ($gradeLevelId) {
                $q->whereJsonContains('grade_level_ids', (int) $gradeLevelId)
                    ->orWhereJsonContains('grade_level_ids', (string) $gradeLevelId);
            });
        }

        // Search by name (through User relationship - personal info is on User model)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('rating', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'teachers' => collect($teachers->items())->map(fn ($teacher) => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'bio' => $teacher->bio_arabic,
                'education_level' => $teacher->education_level,
                'university' => $teacher->university,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'subject_ids' => $teacher->subject_ids ?? [],
                'grade_level_ids' => $teacher->grade_level_ids ?? [],
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'hourly_rate' => $teacher->hourly_rate,
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($teachers),
        ], __('Teachers retrieved successfully'));
    }

    /**
     * Get a specific Academic teacher.
     */
    public function showAcademicTeacher(Request $request, int $id): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $teacher = AcademicTeacherProfile::where('id', $id)
            ->where('academy_id', $academy->id)
            ->whereHas('user', fn ($q) => $q->where('active_status', true))
            ->with(['user', 'subjects', 'gradeLevels', 'packages'])
            ->first();

        if (! $teacher) {
            return $this->notFound(__('Teacher not found.'));
        }

        return $this->success([
            'teacher' => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'email' => $teacher->user?->email,
                'bio' => $teacher->bio_arabic,
                'bio_en' => $teacher->bio_english,
                'education_level' => $teacher->education_level,
                'university' => $teacher->university,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'certifications' => $teacher->certifications ?? [],
                'subjects' => $teacher->subjects?->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                ])->toArray() ?? [],
                'grade_levels' => $teacher->gradeLevels?->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->name,
                ])->toArray() ?? [],
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'total_sessions' => $teacher->total_sessions ?? 0,
                'hourly_rate' => $teacher->hourly_rate,
                'available_packages' => $teacher->packages?->where('is_active', true)->map(fn ($pkg) => [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'description' => $pkg->description,
                    'sessions_per_week' => $pkg->sessions_per_week,
                    'session_duration_minutes' => $pkg->session_duration_minutes,
                    'monthly_price' => $pkg->monthly_price,
                    'features' => $pkg->features ?? [],
                ])->toArray() ?? [],
            ],
        ], __('Teacher retrieved successfully'));
    }
}
