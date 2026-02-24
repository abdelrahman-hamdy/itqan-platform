<?php

namespace App\Http\Controllers\Api\V1\Teacher\Quran;

use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Helpers\PaginationHelper;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Certificate;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CircleController extends Controller
{
    use ApiResponses;

    /**
     * Get individual circles.
     */
    public function individualIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $query = QuranIndividualCircle::where('quran_teacher_id', $quranTeacherId)
            ->with(['student', 'subscription']);

        // Filter by status (is_active replaces the old status column)
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif (in_array($request->status, ['suspended', 'cancelled', 'completed'])) {
                $query->where('is_active', false);
            }
        }

        $circles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'circles' => collect($circles->items())->map(fn ($circle) => [
                'id' => $circle->id,
                'name' => $circle->name,
                'student' => $circle->student ? [
                    'id' => $circle->student->id,
                    'name' => $circle->student->name,
                    'avatar' => $circle->student->avatar
                        ? asset('storage/'.$circle->student->avatar)
                        : null,
                ] : null,
                'status' => $circle->is_active ? 'active' : 'suspended',
                'sessions_count' => $circle->subscription?->sessions_count ?? 0,
                'completed_sessions' => $circle->subscription?->completed_sessions_count ?? 0,
                'remaining_sessions' => $circle->subscription?->remaining_sessions ?? 0,
                'schedule' => $circle->schedule ?? [],
                'created_at' => $circle->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($circles),
        ], __('Individual circles retrieved successfully'));
    }

    /**
     * Get individual circle detail.
     */
    public function individualShow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $circle = QuranIndividualCircle::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['student', 'subscription.certificate', 'sessions' => function ($q) {
                $q->with('reports')->orderBy('scheduled_at', 'desc')->limit(10);
            }])
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        // Calculate session statistics
        $allSessions = $circle->sessions()->get();
        $sessionsStats = [
            'total' => $allSessions->count(),
            'completed' => $allSessions->where('status', SessionStatus::COMPLETED)->count(),
            'scheduled' => $allSessions->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])->count(),
            'cancelled' => $allSessions->where('status', SessionStatus::CANCELLED)->count(),
            'absent' => $allSessions->where('status', SessionStatus::ABSENT)->count(),
        ];

        // Get certificate data
        $certificate = $circle->subscription?->certificate;
        $certificateData = $certificate ? [
            'issued' => true,
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'issued_at' => $certificate->issued_at?->toISOString(),
            'view_url' => $certificate->view_url,
            'download_url' => $certificate->download_url,
        ] : ['issued' => false];

        // Determine if certificate can be issued (has subscription and not already issued)
        $canIssueCertificate = $circle->subscription && ! $certificate;

        // Check if teacher can chat (has supervisor)
        $canChat = $user->hasSupervisor();

        return $this->success([
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'student' => $circle->student ? [
                    'id' => $circle->student->id,
                    'name' => $circle->student->name,
                    'email' => $circle->student->email,
                    'avatar' => $circle->student->avatar
                        ? asset('storage/'.$circle->student->avatar)
                        : null,
                    'phone' => $circle->student->phone,
                ] : null,
                'status' => $circle->is_active ? 'active' : 'suspended',
                'subscription' => $circle->subscription ? [
                    'id' => $circle->subscription->id,
                    'status' => $circle->subscription->status,
                    'sessions_count' => $circle->subscription->sessions_count,
                    'completed_sessions' => $circle->subscription->completed_sessions_count ?? 0,
                    'remaining_sessions' => $circle->subscription->remaining_sessions ?? 0,
                    'start_date' => $circle->subscription->start_date?->toDateString(),
                    'end_date' => $circle->subscription->end_date?->toDateString(),
                ] : null,
                'certificate' => $certificateData,
                'can_issue_certificate' => $canIssueCertificate,
                'sessions_stats' => $sessionsStats,
                'quick_actions' => [
                    'can_chat' => $canChat,
                    'can_issue_certificate' => $canIssueCertificate,
                ],
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
                    'total_memorized_pages' => $circle->total_memorized_pages,
                ],
                'created_at' => $circle->created_at->toISOString(),
            ],
        ], __('Circle retrieved successfully'));
    }

    /**
     * Get group circles.
     */
    public function groupIndex(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $query = QuranCircle::where('quran_teacher_id', $quranTeacherId)
            ->withCount('students');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $circles = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'circles' => collect($circles->items())->map(fn ($circle) => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'status' => $circle->status ? 'active' : 'suspended',
                'students_count' => $circle->students_count,
                'max_students' => $circle->max_students,
                'level' => $circle->level,
                'schedule' => $circle->schedule ?? [],
                'created_at' => $circle->created_at->toISOString(),
            ])->toArray(),
            'pagination' => PaginationHelper::fromPaginator($circles),
        ], __('Group circles retrieved successfully'));
    }

    /**
     * Get group circle detail.
     */
    public function groupShow(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['students.user', 'sessions' => function ($q) {
                $q->orderBy('scheduled_at', 'desc')->limit(10);
            }])
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        // Calculate session statistics
        $allSessions = $circle->sessions()->get();
        $sessionsStats = [
            'total' => $allSessions->count(),
            'completed' => $allSessions->where('status', SessionStatus::COMPLETED)->count(),
            'scheduled' => $allSessions->whereIn('status', [SessionStatus::SCHEDULED, SessionStatus::READY])->count(),
            'cancelled' => $allSessions->where('status', SessionStatus::CANCELLED)->count(),
        ];

        // Count certificates issued for students in this circle
        $studentIds = $circle->students->pluck('user_id')->filter()->toArray();
        $certificatesIssued = Certificate::where('certificateable_type', QuranCircle::class)
            ->where('certificateable_id', $circle->id)
            ->whereIn('student_id', $studentIds)
            ->count();

        $certificatesStats = [
            'total_students' => $circle->students->count(),
            'certificates_issued' => $certificatesIssued,
        ];

        return $this->success([
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
                'description' => $circle->description,
                'status' => $circle->status ? 'active' : 'suspended',
                'level' => $circle->level,
                'students_count' => $circle->students->count(),
                'max_students' => $circle->max_students,
                'sessions_stats' => $sessionsStats,
                'certificates_stats' => $certificatesStats,
                'quick_actions' => [
                    'can_issue_certificate' => $circle->students->count() > $certificatesIssued,
                ],
                'schedule' => $circle->schedule ?? [],
                'recent_sessions' => $circle->sessions->map(fn ($s) => [
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
     */
    public function groupStudents(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with(['students.user', 'students.subscriptions' => function ($q) use ($id) {
                $q->where('quran_circle_id', $id);
            }])
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        // Check if teacher can chat (has supervisor)
        $canChat = $user->hasSupervisor();

        // Get all certificates for this circle
        $studentIds = $circle->students->pluck('user_id')->filter()->toArray();
        $certificates = Certificate::where('certificateable_type', QuranCircle::class)
            ->where('certificateable_id', $circle->id)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $students = $circle->students->map(function ($student) use ($certificates, $canChat) {
            $subscription = $student->subscriptions->first();
            $certificate = $certificates->get($student->user?->id);

            $certificateData = $certificate ? [
                'issued' => true,
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'issued_at' => $certificate->issued_at?->toISOString(),
                'view_url' => $certificate->view_url,
                'download_url' => $certificate->download_url,
            ] : ['issued' => false];

            return [
                'id' => $student->id,
                'user_id' => $student->user?->id,
                'name' => $student->user?->name ?? $student->full_name,
                'email' => $student->user?->email,
                'avatar' => $student->user?->avatar
                    ? asset('storage/'.$student->user->avatar)
                    : null,
                'phone' => $student->phone ?? $student->user?->phone,
                'subscription_status' => $subscription?->status ?? 'unknown',
                'joined_at' => $subscription?->created_at?->toISOString(),
                'certificate' => $certificateData,
                'can_chat' => $canChat,
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

    /**
     * Get certificates for a group circle.
     */
    public function groupCertificates(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (! $user->quranTeacherProfile) {
            return $this->error(__('Quran teacher profile not found.'), 404, 'PROFILE_NOT_FOUND');
        }

        $quranTeacherId = $user->id;

        $circle = QuranCircle::where('id', $id)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (! $circle) {
            return $this->notFound(__('Circle not found.'));
        }

        // Get all certificates for this circle
        $studentIds = $circle->students->pluck('user_id')->filter()->toArray();
        $certificates = Certificate::where('certificateable_type', QuranCircle::class)
            ->where('certificateable_id', $circle->id)
            ->whereIn('student_id', $studentIds)
            ->with('student')
            ->orderBy('issued_at', 'desc')
            ->get();

        return $this->success([
            'circle' => [
                'id' => $circle->id,
                'name' => $circle->name,
            ],
            'certificates' => $certificates->map(fn ($cert) => [
                'id' => $cert->id,
                'certificate_number' => $cert->certificate_number,
                'student' => [
                    'id' => $cert->student?->id,
                    'name' => $cert->student?->name,
                    'avatar' => $cert->student?->avatar
                        ? asset('storage/'.$cert->student->avatar)
                        : null,
                ],
                'issued_at' => $cert->issued_at?->toISOString(),
                'view_url' => $cert->view_url,
                'download_url' => $cert->download_url,
            ])->toArray(),
            'total' => $certificates->count(),
        ], __('Circle certificates retrieved successfully'));
    }
}
