<?php

namespace App\Http\Controllers\Api\V1\Teacher\Quran;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\QuranSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\SessionStatus;

class CircleController extends Controller
{
    use ApiResponses;

    /**
     * Get individual circles.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function individualIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $query = QuranIndividualCircle::where('quran_teacher_id', $quranTeacherId)
            ->with(['student.user', 'subscription']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $circles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'circles' => collect($circles->items())->map(fn($circle) => [
                'id' => $circle->id,
                'name' => $circle->name,
                'student' => $circle->student?->user ? [
                    'id' => $circle->student->user->id,
                    'name' => $circle->student->user->name,
                    'avatar' => $circle->student->user->avatar
                        ? asset('storage/' . $circle->student->user->avatar)
                        : null,
                ] : null,
                'status' => $circle->status,
                'sessions_count' => $circle->subscription?->sessions_count ?? 0,
                'completed_sessions' => $circle->subscription?->completed_sessions_count ?? 0,
                'remaining_sessions' => $circle->subscription?->remaining_sessions ?? 0,
                'schedule' => $circle->schedule ?? [],
                'created_at' => $circle->created_at->toISOString(),
            ])->toArray(),
            'pagination' => [
                'current_page' => $circles->currentPage(),
                'per_page' => $circles->perPage(),
                'total' => $circles->total(),
                'total_pages' => $circles->lastPage(),
                'has_more' => $circles->hasMorePages(),
            ],
        ], __('Individual circles retrieved successfully'));
    }

    /**
     * Get individual circle detail.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function individualShow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranIndividualCircle::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['student.user', 'subscription', 'sessions' => function ($q) {
                $q->with('reports')->orderBy('scheduled_at', 'desc')->limit(10);
            }])
            ->first();

        if (!$circle) {
            return $this->notFound(__('Circle not found.'));
        }

        return $this->success([
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'student' => $circle->student?->user ? [
                    'id' => $circle->student->user->id,
                    'name' => $circle->student->user->name,
                    'email' => $circle->student->user->email,
                    'avatar' => $circle->student->user->avatar
                        ? asset('storage/' . $circle->student->user->avatar)
                        : null,
                    'phone' => $circle->student?->phone ?? $circle->student->user->phone,
                ] : null,
                'status' => $circle->status,
                'subscription' => $circle->subscription ? [
                    'id' => $circle->subscription->id,
                    'status' => $circle->subscription->status,
                    'sessions_count' => $circle->subscription->sessions_count,
                    'completed_sessions' => $circle->subscription->completed_sessions_count ?? 0,
                    'remaining_sessions' => $circle->subscription->remaining_sessions ?? 0,
                    'start_date' => $circle->subscription->start_date?->toDateString(),
                    'end_date' => $circle->subscription->end_date?->toDateString(),
                ] : null,
                'schedule' => $circle->schedule ?? [],
                'recent_sessions' => $circle->sessions->map(function ($s) {
                    $report = $s->reports?->first();
                    return [
                        'id' => $s->id,
                        'scheduled_at' => $s->scheduled_at?->toISOString(),
                        'status' => $s->status->value ?? $s->status,
                        'memorization_degree' => $report?->new_memorization_degree,
                        'revision_degree' => $report?->reservation_degree,
                        'overall_performance' => $report?->overall_performance,
                    ];
                })->toArray(),
                'progress' => [
                    'current_surah' => $circle->current_surah,
                    'current_page' => $circle->current_page,
                    'total_memorized_pages' => $circle->total_memorized_pages,
                ],
                'created_at' => $circle->created_at->toISOString(),
            ],
        ], __('Circle retrieved successfully'));
    }

    /**
     * Get group circles.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function groupIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $query = QuranCircle::where('quran_teacher_id', $quranTeacherId)
            ->withCount('students');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $circles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'circles' => collect($circles->items())->map(fn($circle) => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'status' => $circle->status,
                'students_count' => $circle->students_count,
                'max_students' => $circle->max_students,
                'level' => $circle->level,
                'schedule' => $circle->schedule ?? [],
                'created_at' => $circle->created_at->toISOString(),
            ])->toArray(),
            'pagination' => [
                'current_page' => $circles->currentPage(),
                'per_page' => $circles->perPage(),
                'total' => $circles->total(),
                'total_pages' => $circles->lastPage(),
                'has_more' => $circles->hasMorePages(),
            ],
        ], __('Group circles retrieved successfully'));
    }

    /**
     * Get group circle detail.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function groupShow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['students.user', 'sessions' => function ($q) {
                $q->orderBy('scheduled_at', 'desc')->limit(10);
            }])
            ->first();

        if (!$circle) {
            return $this->notFound(__('Circle not found.'));
        }

        return $this->success([
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'status' => $circle->status,
                'level' => $circle->level,
                'students_count' => $circle->students->count(),
                'max_students' => $circle->max_students,
                'schedule' => $circle->schedule ?? [],
                'recent_sessions' => $circle->sessions->map(fn($s) => [
                    'id' => $s->id,
                    'scheduled_at' => $s->scheduled_at?->toISOString(),
                    'status' => $s->status->value ?? $s->status,
                ])->toArray(),
                'created_at' => $circle->created_at->toISOString(),
            ],
        ], __('Circle retrieved successfully'));
    }

    /**
     * Get students in a group circle.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function groupStudents(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quranTeacherId = $user->quranTeacherProfile?->id;

        if (!$quranTeacherId) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['students.user', 'students.subscriptions' => function ($q) use ($id) {
                $q->where('quran_circle_id', $id);
            }])
            ->first();

        if (!$circle) {
            return $this->notFound(__('Circle not found.'));
        }

        $students = $circle->students->map(function ($student) {
            $subscription = $student->subscriptions->first();

            return [
                'id' => $student->id,
                'user_id' => $student->user?->id,
                'name' => $student->user?->name ?? $student->full_name,
                'avatar' => $student->user?->avatar
                    ? asset('storage/' . $student->user->avatar)
                    : null,
                'phone' => $student->phone ?? $student->user?->phone,
                'subscription_status' => $subscription?->status ?? 'unknown',
                'current_surah' => $student->current_surah,
                'current_page' => $student->current_page,
                'joined_at' => $subscription?->created_at?->toISOString(),
            ];
        });

        return $this->success([
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
            ],
            'students' => $students->toArray(),
            'total' => $students->count(),
        ], __('Circle students retrieved successfully'));
    }
}
