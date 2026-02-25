<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Models\QuranCircleEnrollment;
use App\Enums\CircleEnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranCircle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CircleController extends Controller
{
    use ApiResponses;

    /**
     * Get list of available Quran circles for browsing.
     */
    public function index(Request $request): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $query = QuranCircle::where('academy_id', $academy->id)
            ->where('status', true)
            ->where('enrollment_status', CircleEnrollmentStatus::OPEN)
            ->with(['quranTeacherProfile.user']);

        // Filter by teacher
        if ($request->filled('teacher_id')) {
            $query->where('quran_teacher_id', $request->teacher_id);
        }

        // Filter by gender
        if ($request->filled('gender')) {
            $query->where('target_gender', $request->gender);
        }

        // Filter by level
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Filter by day
        if ($request->filled('day')) {
            $query->whereJsonContains('schedule_days', $request->day);
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('quranTeacher', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
            });
        }

        $circles = $query->orderBy('created_at', 'desc')
            ->paginate(min((int) $request->get('per_page', 15), 100));

        // Check which circles the student is enrolled in
        $studentId = $request->user()?->id;
        $enrolledCircleIds = [];
        if ($studentId) {
            $enrolledCircleIds = QuranCircleEnrollment::where('student_id', $studentId)
                ->where('status', 'enrolled')
                ->pluck('circle_id')
                ->toArray();
        }

        return $this->success([
            'circles' => collect($circles->items())->map(fn ($circle) => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'teacher_id' => $circle->quranTeacherProfile?->id,
                'teacher_name' => $circle->quranTeacherProfile?->user?->name ?? $circle->quranTeacherProfile?->full_name,
                'teacher_avatar' => $circle->quranTeacherProfile?->user?->avatar
                    ? asset('storage/'.$circle->quranTeacherProfile->user->avatar)
                    : null,
                'target_gender' => $circle->target_gender,
                'level' => $circle->level,
                'current_students' => $circle->current_students_count ?? 0,
                'max_students' => $circle->max_students,
                'schedule_days' => $circle->schedule_days ?? [],
                'start_time' => $circle->start_time,
                'end_time' => $circle->end_time,
                'monthly_price' => $circle->monthly_price,
                'is_full' => ($circle->current_students_count ?? 0) >= $circle->max_students,
                'is_enrolled' => in_array($circle->id, $enrolledCircleIds),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($circles),
        ], __('Circles retrieved successfully'));
    }

    /**
     * Get details of a specific Quran circle.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $academy = $request->attributes->get('academy') ?? current_academy();

        $circle = QuranCircle::where('id', $id)
            ->where('academy_id', $academy->id)
            ->with(['quranTeacherProfile.user'])
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        return $this->success([
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'teacher_id' => $circle->quranTeacherProfile?->id,
                'teacher_name' => $circle->quranTeacherProfile?->user?->name ?? $circle->quranTeacherProfile?->full_name,
                'teacher_avatar' => $circle->quranTeacherProfile?->user?->avatar
                    ? asset('storage/'.$circle->quranTeacherProfile->user->avatar)
                    : null,
                'teacher_bio' => $circle->quranTeacherProfile?->bio_arabic,
                'target_gender' => $circle->target_gender,
                'level' => $circle->level,
                'current_students' => $circle->current_students_count ?? 0,
                'max_students' => $circle->max_students,
                'schedule_days' => $circle->schedule_days ?? [],
                'start_time' => $circle->start_time,
                'end_time' => $circle->end_time,
                'session_duration_minutes' => $circle->session_duration_minutes,
                'monthly_price' => $circle->monthly_price,
                'is_active' => (bool) $circle->status,
                'accepts_new_students' => $circle->enrollment_status === CircleEnrollmentStatus::OPEN,
                'is_full' => ($circle->current_students_count ?? 0) >= $circle->max_students,
                'requirements' => $circle->requirements,
                'features' => $circle->features ?? [],
            ],
        ], __('Circle retrieved successfully'));
    }
}
