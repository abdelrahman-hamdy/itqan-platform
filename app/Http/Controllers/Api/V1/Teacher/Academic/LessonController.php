<?php

namespace App\Http\Controllers\Api\V1\Teacher\Academic;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicIndividualLesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    use ApiResponses;

    /**
     * Get individual lessons.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $query = AcademicIndividualLesson::where('academic_teacher_id', $academicTeacherId)
            ->with(['student.user', 'subscription', 'subject']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by subject
        if ($request->filled('subject_id')) {
            $query->whereHas('subscription', function ($q) use ($request) {
                $q->where('subject_id', $request->subject_id);
            });
        }

        $lessons = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'lessons' => collect($lessons->items())->map(fn ($lesson) => [
                'id' => $lesson->id,
                'name' => $lesson->name,
                'student' => $lesson->student?->user ? [
                    'id' => $lesson->student->user->id,
                    'name' => $lesson->student->user->name,
                    'avatar' => $lesson->student->user->avatar
                        ? asset('storage/'.$lesson->student->user->avatar)
                        : null,
                ] : null,
                'subject' => $lesson->subject?->name ?? $lesson->subscription?->subject?->name ?? $lesson->subscription?->subject_name,
                'status' => $lesson->status,
                'sessions_count' => $lesson->subscription?->sessions_count ?? 0,
                'completed_sessions' => $lesson->subscription?->completed_sessions_count ?? 0,
                'remaining_sessions' => $lesson->subscription?->remaining_sessions ?? 0,
                'schedule' => $lesson->schedule ?? [],
                'created_at' => $lesson->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($lessons),
        ], __('Lessons retrieved successfully'));
    }

    /**
     * Get lesson detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            return $this->error(__('Academic teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $lesson = AcademicIndividualLesson::where('id', $id)
            ->where('academic_teacher_id', $academicTeacherId)
            ->with(['student.user', 'subscription.subject', 'subscription.certificate', 'sessions' => function ($q) {
                $q->orderBy('scheduled_at', 'desc')->limit(10);
            }])
            ->first();

        if (! $lesson) {
            return $this->notFound(__('Lesson not found.'));
        }

        // Calculate session statistics
        $allSessions = $lesson->sessions()->get();
        $sessionsStats = [
            'total' => $allSessions->count(),
            'completed' => $allSessions->where('status', SessionStatus::COMPLETED)->count(),
            'scheduled' => $allSessions->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])->count(),
            'cancelled' => $allSessions->where('status', SessionStatus::CANCELLED)->count(),
            'absent' => $allSessions->where('status', SessionStatus::ABSENT)->count(),
        ];

        // Get certificate data
        $certificate = $lesson->subscription?->certificate;
        $certificateData = $certificate ? [
            'issued' => true,
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'issued_at' => $certificate->issued_at?->toISOString(),
            'view_url' => $certificate->view_url,
            'download_url' => $certificate->download_url,
        ] : ['issued' => false];

        // Determine if certificate can be issued (has subscription and not already issued)
        $canIssueCertificate = $lesson->subscription && ! $certificate;

        // Check if teacher can chat (has supervisor)
        $canChat = $user->hasSupervisor();

        return $this->success([
            'lesson' => [
                'id' => $lesson->id,
                'name' => $lesson->name,
                'description' => $lesson->description,
                'student' => $lesson->student?->user ? [
                    'id' => $lesson->student->user->id,
                    'name' => $lesson->student->user->name,
                    'email' => $lesson->student->user->email,
                    'avatar' => $lesson->student->user->avatar
                        ? asset('storage/'.$lesson->student->user->avatar)
                        : null,
                    'phone' => $lesson->student?->phone ?? $lesson->student->user->phone,
                ] : null,
                'subject' => $lesson->subscription?->subject ? [
                    'id' => $lesson->subscription->subject->id,
                    'name' => $lesson->subscription->subject->name,
                ] : [
                    'name' => $lesson->subscription?->subject_name ?? 'غير محدد',
                ],
                'status' => $lesson->status,
                'subscription' => $lesson->subscription ? [
                    'id' => $lesson->subscription->id,
                    'status' => $lesson->subscription->status,
                    'sessions_count' => $lesson->subscription->sessions_count,
                    'completed_sessions' => $lesson->subscription->completed_sessions_count ?? 0,
                    'remaining_sessions' => $lesson->subscription->remaining_sessions ?? 0,
                    'start_date' => $lesson->subscription->start_date?->toDateString(),
                    'end_date' => $lesson->subscription->end_date?->toDateString(),
                ] : null,
                'certificate' => $certificateData,
                'can_issue_certificate' => $canIssueCertificate,
                'sessions_stats' => $sessionsStats,
                'quick_actions' => [
                    'can_chat' => $canChat,
                    'can_issue_certificate' => $canIssueCertificate,
                ],
                'schedule' => $lesson->schedule ?? [],
                'recent_sessions' => $lesson->sessions->map(fn ($s) => [
                    'id' => $s->id,
                    'scheduled_at' => $s->scheduled_at?->toISOString(),
                    'status' => $s->status->value ?? $s->status,
                    'homework' => $s->homework,
                ])->toArray(),
                'created_at' => $lesson->created_at->toISOString(),
            ],
        ], __('Lesson retrieved successfully'));
    }
}
