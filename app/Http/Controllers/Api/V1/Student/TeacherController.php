<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicIndividualLesson;
use App\Models\AcademicPackage;
use App\Models\AcademicTeacherProfile;
use App\Models\QuranIndividualCircle;
use App\Models\QuranPackage;
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

        // Check which teachers the student is subscribed to
        $studentId = $request->user()?->id;
        $subscribedTeacherUserIds = [];
        $subscriptionMap = collect();
        if ($studentId) {
            $circles = QuranIndividualCircle::where('student_id', $studentId)
                ->where('is_active', true)
                ->get(['id', 'quran_teacher_id']);
            $subscribedTeacherUserIds = $circles->pluck('quran_teacher_id')->toArray();
            $subscriptionMap = $circles->keyBy('quran_teacher_id');
        }

        return $this->success([
            'teachers' => collect($teachers->items())->map(fn ($teacher) => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'bio' => $teacher->bio_arabic,
                'educational_qualification' => $teacher->educational_qualification ? (
                    is_string($teacher->educational_qualification)
                        ? $teacher->educational_qualification
                        : $teacher->educational_qualification->value
                ) : null,
                'certifications' => $teacher->certifications ?? [],
                'teaching_experience_years' => $teacher->teaching_experience_years ?? 0,
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'languages' => $teacher->languages ?? [],
                'is_subscribed' => in_array($teacher->user_id, $subscribedTeacherUserIds),
                'subscription_id' => $subscriptionMap->get($teacher->user_id)?->id,
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
            ->with(['user'])
            ->first();

        if (! $teacher) {
            return $this->notFound(__('Teacher not found.'));
        }

        // Check subscription status
        $studentId = $request->user()?->id;
        $circle = $studentId ? QuranIndividualCircle::where('student_id', $studentId)
            ->where('quran_teacher_id', $teacher->user_id)
            ->where('is_active', true)
            ->first(['id']) : null;

        // Load packages for the academy
        $packages = QuranPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return $this->success([
            'teacher' => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'email' => $teacher->user?->email,
                'bio' => $teacher->bio_arabic,
                'bio_en' => $teacher->bio_english,
                'educational_qualification' => $teacher->educational_qualification ? (
                    is_string($teacher->educational_qualification)
                        ? $teacher->educational_qualification
                        : $teacher->educational_qualification->value
                ) : null,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'certifications' => $teacher->certifications ?? [],
                'languages' => $teacher->languages ?? [],
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'total_sessions' => $teacher->total_sessions ?? 0,
                'session_price_individual' => $teacher->session_price_individual,
                'session_price_group' => $teacher->session_price_group,
                'available_days' => $teacher->available_days ?? [],
                'available_time_start' => $teacher->available_time_start?->format('H:i'),
                'available_time_end' => $teacher->available_time_end?->format('H:i'),
                'is_subscribed' => $circle !== null,
                'subscription_id' => $circle?->id,
                'packages' => $packages->map(fn ($pkg) => [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'description' => $pkg->description,
                    'sessions_per_month' => $pkg->sessions_per_month,
                    'session_duration_minutes' => $pkg->session_duration_minutes,
                    'monthly_price' => $pkg->monthly_price,
                    'quarterly_price' => $pkg->quarterly_price,
                    'yearly_price' => $pkg->yearly_price,
                    'currency' => $pkg->currency ?? 'SAR',
                    'features' => $pkg->features ?? [],
                    'is_popular' => $pkg->sort_order === 0,
                ])->toArray(),
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

        // Check which teachers the student is subscribed to
        $studentId = $request->user()?->id;
        $subscribedTeacherIds = [];
        $lessonMap = collect();
        if ($studentId) {
            $lessons = AcademicIndividualLesson::where('student_id', $studentId)
                ->where('status', 'active')
                ->get(['id', 'academic_teacher_id']);
            $subscribedTeacherIds = $lessons->pluck('academic_teacher_id')->toArray();
            $lessonMap = $lessons->keyBy('academic_teacher_id');
        }

        return $this->success([
            'teachers' => collect($teachers->items())->map(fn ($teacher) => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'bio' => $teacher->bio_arabic,
                'education_level' => $teacher->education_level?->value,
                'university' => $teacher->university,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'certifications' => $teacher->certifications ?? [],
                'subject_ids' => $teacher->subject_ids ?? [],
                'grade_level_ids' => $teacher->grade_level_ids ?? [],
                'rating' => round($teacher->rating ?? 0, 1),
                'total_reviews' => $teacher->total_reviews ?? 0,
                'total_students' => $teacher->total_students ?? 0,
                'is_subscribed' => in_array($teacher->id, $subscribedTeacherIds),
                'subscription_id' => $lessonMap->get($teacher->id)?->id,
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
            ->with(['user', 'subjects', 'gradeLevels'])
            ->first();

        if (! $teacher) {
            return $this->notFound(__('Teacher not found.'));
        }

        // Check subscription status
        $studentId = $request->user()?->id;
        $lesson = $studentId ? AcademicIndividualLesson::where('student_id', $studentId)
            ->where('academic_teacher_id', $teacher->id)
            ->where('status', 'active')
            ->first(['id']) : null;

        // Load packages for the academy
        $packages = AcademicPackage::where('academy_id', $academy->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('monthly_price')
            ->get();

        return $this->success([
            'teacher' => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'name' => $teacher->user?->name ?? $teacher->full_name,
                'avatar' => $teacher->user?->avatar ? asset('storage/'.$teacher->user->avatar) : null,
                'email' => $teacher->user?->email,
                'bio' => $teacher->bio_arabic,
                'bio_en' => $teacher->bio_english,
                'education_level' => $teacher->education_level?->value,
                'university' => $teacher->university,
                'teaching_experience_years' => $teacher->teaching_experience_years,
                'certifications' => $teacher->certifications ?? [],
                'languages' => $teacher->languages ?? [],
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
                'session_price_individual' => $teacher->session_price_individual,
                'available_days' => $teacher->available_days ?? [],
                'available_time_start' => $teacher->available_time_start?->format('H:i'),
                'available_time_end' => $teacher->available_time_end?->format('H:i'),
                'is_subscribed' => $lesson !== null,
                'subscription_id' => $lesson?->id,
                'packages' => $packages->map(fn ($pkg) => [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'description' => $pkg->description,
                    'sessions_per_month' => $pkg->sessions_per_month,
                    'session_duration_minutes' => $pkg->session_duration_minutes,
                    'monthly_price' => $pkg->monthly_price,
                    'quarterly_price' => $pkg->quarterly_price,
                    'yearly_price' => $pkg->yearly_price,
                    'currency' => $pkg->currency ?? 'SAR',
                    'features' => $pkg->features ?? [],
                    'is_popular' => $pkg->sort_order === 0,
                ])->toArray(),
            ],
        ], __('Teacher retrieved successfully'));
    }
}
