<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ChildSelectionMiddleware;
use App\Services\UnifiedHomeworkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Parent Homework Controller
 *
 * Handles viewing children's homework assignments.
 * Supports filtering by child using session-based selection.
 * Returns student view with parent layout for consistent design.
 */
class ParentHomeworkController extends Controller
{
    protected UnifiedHomeworkService $homeworkService;

    public function __construct(UnifiedHomeworkService $homeworkService)
    {
        $this->homeworkService = $homeworkService;

        // Enforce read-only access
        $this->middleware(function ($request, $next) {
            if (!in_array($request->method(), ['GET', 'HEAD'])) {
                abort(403, 'أولياء الأمور لديهم صلاحيات مشاهدة فقط');
            }
            return $next($request);
        });
    }

    /**
     * Display homework list for children
     *
     * Uses the student homework view with parent layout.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $academyId = $parent->academy_id;

        // Get child user IDs from middleware (session-based selection)
        $childUserIds = ChildSelectionMiddleware::getChildIds();

        // Get filter parameters
        $status = $request->get('status');
        $type = $request->get('type');

        // Aggregate homework for all selected children
        $allHomework = collect();
        $totalStatistics = [
            'total' => 0,
            'pending' => 0,
            'submitted' => 0,
            'graded' => 0,
            'overdue' => 0,
            'average_score' => 0,
            'type_breakdown' => [
                'academic' => 0,
                'quran' => 0,
                'interactive' => 0,
            ],
            'completion_rate' => 0,
        ];

        $scoresCount = 0;
        $scoresSum = 0;

        foreach ($childUserIds as $childUserId) {
            // Get homework for this child
            $homework = $this->homeworkService->getStudentHomework(
                $childUserId,
                $academyId,
                $status,
                $type
            );

            // Add child info to each homework item
            $childUser = \App\Models\User::find($childUserId);
            $homework = collect($homework)->map(function ($hw) use ($childUser) {
                $hw['child_name'] = $childUser->name ?? 'غير معروف';
                $hw['child_id'] = $childUser->id;
                return $hw;
            });

            $allHomework = $allHomework->merge($homework);

            // Get statistics for this child
            $stats = $this->homeworkService->getStudentHomeworkStatistics($childUserId, $academyId);
            $totalStatistics['total'] += $stats['total'] ?? 0;
            $totalStatistics['pending'] += $stats['pending'] ?? 0;
            $totalStatistics['submitted'] += $stats['submitted'] ?? 0;
            $totalStatistics['graded'] += $stats['graded'] ?? 0;
            $totalStatistics['overdue'] += $stats['overdue'] ?? 0;
            $totalStatistics['type_breakdown']['academic'] += $stats['type_breakdown']['academic'] ?? 0;
            $totalStatistics['type_breakdown']['quran'] += $stats['type_breakdown']['quran'] ?? 0;
            $totalStatistics['type_breakdown']['interactive'] += $stats['type_breakdown']['interactive'] ?? 0;

            // Track for average score
            if (isset($stats['average_score']) && $stats['graded'] > 0) {
                $scoresSum += ($stats['average_score'] * $stats['graded']);
                $scoresCount += $stats['graded'];
            }
        }

        // Calculate aggregated average score
        if ($scoresCount > 0) {
            $totalStatistics['average_score'] = $scoresSum / $scoresCount;
        }

        // Calculate completion rate
        if ($totalStatistics['total'] > 0) {
            $totalStatistics['completion_rate'] = round(
                (($totalStatistics['submitted'] + $totalStatistics['graded']) / $totalStatistics['total']) * 100
            );
        }

        // Sort by due date (most recent first)
        $allHomework = $allHomework->sortByDesc('due_date')->values()->all();

        // Return student view with parent layout
        return view('student.homework.index', [
            'homework' => $allHomework,
            'statistics' => $totalStatistics,
            'layout' => 'parent',
        ]);
    }

    /**
     * View homework details for a specific child
     *
     * @param Request $request
     * @param int $id
     * @param string $type
     * @return \Illuminate\View\View
     */
    public function view(Request $request, $id, $type = 'academic')
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get all children user IDs
        $children = $parent->students()->with('user')->get();
        $childUserIds = $children->pluck('user_id')->toArray();

        // Get homework based on type
        $homework = $this->getHomeworkByType($id, $type, $parent->academy_id);

        if (!$homework) {
            return view('student.homework.view', [
                'homework' => null,
                'submission' => null,
                'layout' => 'parent',
            ]);
        }

        // Verify homework belongs to one of parent's children
        $studentId = $this->getStudentIdFromHomework($homework, $type);
        if (!in_array($studentId, $childUserIds)) {
            abort(403, 'لا يمكنك الوصول إلى هذا الواجب');
        }

        // Get submission
        $submission = $this->getSubmission($homework, $type);

        // Format homework data
        $homeworkData = $this->formatHomeworkForView($homework, $submission, $type);

        return view('student.homework.view', [
            'homework' => $homeworkData,
            'submission' => $submission,
            'layout' => 'parent',
        ]);
    }

    /**
     * Get homework by type and ID
     */
    private function getHomeworkByType($id, $type, $academyId)
    {
        return match($type) {
            'academic' => \App\Models\AcademicHomework::where('id', $id)
                ->where('academy_id', $academyId)
                ->first(),
            'interactive' => \App\Models\InteractiveCourseHomework::where('id', $id)
                ->whereHas('session.interactiveCourse', function($q) use ($academyId) {
                    $q->where('academy_id', $academyId);
                })
                ->first(),
            default => null,
        };
    }

    /**
     * Get student ID from homework based on type
     */
    private function getStudentIdFromHomework($homework, $type)
    {
        return match($type) {
            'academic' => $homework->session?->student_id ?? null,
            'interactive' => $homework->session?->course?->enrollments()
                ->where('status', 'active')
                ->pluck('student_id')
                ->toArray() ?? [],
            default => null,
        };
    }

    /**
     * Get submission for homework
     */
    private function getSubmission($homework, $type)
    {
        return match($type) {
            'academic' => $homework->submissions()->first(),
            'interactive' => $homework->submissions()->first(),
            default => null,
        };
    }

    /**
     * Format homework data for view
     */
    private function formatHomeworkForView($homework, $submission, $type)
    {
        return [
            'type' => $type,
            'id' => $homework->id,
            'title' => $homework->title ?? 'واجب',
            'description' => $homework->description ?? '',
            'due_date' => $homework->due_date ?? null,
            'status' => $submission?->submission_status ?? $submission?->status ?? 'not_submitted',
            'status_text' => $submission?->submission_status_text ?? $submission?->status_text ?? 'لم يتم التسليم',
            'is_late' => $submission?->is_late ?? false,
            'score' => $submission?->score ?? null,
            'max_score' => $homework->max_score ?? 100,
            'score_percentage' => $submission?->score_percentage ?? null,
            'grade_performance' => $submission?->grade_performance ?? null,
            'teacher_feedback' => $submission?->teacher_feedback ?? null,
            'submitted_at' => $submission?->submitted_at ?? null,
            'graded_at' => $submission?->graded_at ?? null,
        ];
    }
}
