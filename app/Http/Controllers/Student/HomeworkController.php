<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\HomeworkService;
use App\Services\UnifiedHomeworkService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;
use App\Http\Requests\SubmitStudentHomeworkRequest;

class HomeworkController extends Controller
{
    protected HomeworkService $homeworkService;
    protected UnifiedHomeworkService $unifiedHomeworkService;

    public function __construct(
        HomeworkService $homeworkService,
        UnifiedHomeworkService $unifiedHomeworkService
    ) {
        $this->middleware('auth');
        $this->middleware('role:student');
        $this->homeworkService = $homeworkService;
        $this->unifiedHomeworkService = $unifiedHomeworkService;
    }

    /**
     * Display a listing of homework for the student
     *
     * Uses UnifiedHomeworkService to aggregate ALL homework types:
     * - Academic homework
     * - Interactive course homework
     * - Quran homework (view-only)
     */
    public function index(Request $request): View
    {
        $student = Auth::user();
        $academyId = $student->academy_id;

        // Get filter parameters
        $status = $request->get('status'); // pending, submitted, graded, overdue, late
        $type = $request->get('type'); // academic, interactive, quran

        // Get all homework for student using unified service
        $homework = $this->unifiedHomeworkService->getStudentHomework(
            $student->id,
            $academyId,
            $status,
            $type
        );

        // Get statistics
        $statistics = $this->unifiedHomeworkService->getStudentHomeworkStatistics(
            $student->id,
            $academyId
        );

        return view('student.homework.index', compact('homework', 'statistics'));
    }

    /**
     * Show the form for submitting homework
     */
    public function submit(Request $request, $id, $type = 'academic'): View|RedirectResponse
    {
        $student = Auth::user();
        $academyId = $student->academy_id;

        // Get homework based on type
        $homework = $this->getHomeworkByType($id, $type, $academyId);

        if (!$homework) {
            return redirect()->route('student.homework.index')
                ->with('error', 'لم يتم العثور على الواجب المطلوب');
        }

        // Get existing submission if any
        $submission = $this->getSubmissionForStudent($homework, $student->id, $type);

        // Check if can submit
        if ($submission && !$submission->can_submit) {
            return redirect()->route('student.homework.view', ['id' => $id, 'type' => $type])
                ->with('error', 'لا يمكن تقديم الواجب في حالته الحالية');
        }

        return view('student.homework.submit', compact('homework', 'submission', 'type'));
    }

    /**
     * Process homework submission
     */
    public function submitProcess(SubmitStudentHomeworkRequest $request, $id, $type = 'academic'): RedirectResponse
    {
        $student = Auth::user();
        $academyId = $student->academy_id;

        // Get homework based on type
        $homework = $this->getHomeworkByType($id, $type, $academyId);

        if (!$homework) {
            return redirect()->route('student.homework.index')
                ->with('error', 'لم يتم العثور على الواجب المطلوب');
        }

        try {
            // Check if it's a draft save or actual submission
            $isDraft = $request->input('action') === 'save_draft';

            if ($isDraft) {
                // Save as draft
                $submission = $this->homeworkService->saveDraft($homework->id, $student->id, [
                    'text' => $request->input('text'),
                    'files' => $request->file('files'),
                    'notes' => $request->input('notes'),
                ]);

                return redirect()->route('student.homework.submit', ['id' => $id, 'type' => $type])
                    ->with('success', 'تم حفظ المسودة بنجاح');
            } else {
                // Submit homework
                $submission = $this->homeworkService->submitAcademicHomework($homework->id, $student->id, [
                    'text' => $request->input('text'),
                    'files' => $request->file('files'),
                    'notes' => $request->input('notes'),
                    'time_spent' => $request->input('time_spent'),
                    'difficulty_rating' => $request->input('difficulty_rating'),
                ]);

                return redirect()->route('student.homework.view', ['id' => $id, 'type' => $type])
                    ->with('success', 'تم تسليم الواجب بنجاح');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تسليم الواجب: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific homework with submission details
     */
    public function view(Request $request, $id, $type = 'academic'): View
    {
        $student = Auth::user();
        $academyId = $student->academy_id;

        // Get homework based on type
        $homeworkObj = $this->getHomeworkByType($id, $type, $academyId);

        if (!$homeworkObj) {
            return view('student.homework.view', [
                'homework' => null,
                'submission' => null,
            ]);
        }

        // Get submission
        $submission = $this->getSubmissionForStudent($homeworkObj, $student->id, $type);

        // Format homework data for view
        $homework = $this->formatHomeworkForView($homeworkObj, $submission, $type);

        return view('student.homework.view', compact('homework', 'submission'));
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
            // Note: 'quran' type removed - Quran homework is now tracked through QuranSession model
            // and graded through student session reports. See migration: 2025_11_17_190605_drop_quran_homework_tables.php
            'interactive' => \App\Models\InteractiveCourseHomework::where('id', $id)
                ->whereHas('session.interactiveCourse', function($q) use ($academyId) {
                    $q->where('academy_id', $academyId);
                })
                ->first(),
            default => null,
        };
    }

    /**
     * Get submission for student
     */
    private function getSubmissionForStudent($homework, $studentId, $type)
    {
        return match($type) {
            'academic' => $homework->getSubmissionForStudent($studentId),
            'quran' => $homework->student_id == $studentId ? $homework : null,
            'interactive' => $homework->getSubmissionForStudent($studentId),
            default => null,
        };
    }

    /**
     * Format homework data for view
     */
    private function formatHomeworkForView($homework, $submission, $type)
    {
        if ($type === 'academic') {
            return [
                'type' => 'academic',
                'id' => $homework->id,
                'title' => $homework->title,
                'description' => $homework->description,
                'due_date' => $homework->due_date,
                'status' => $submission?->submission_status ?? 'not_submitted',
                'status_text' => $submission?->submission_status_text ?? 'لم يتم التسليم',
                'is_late' => $submission?->is_late ?? false,
                'score' => $submission?->score,
                'max_score' => $homework->max_score,
                'score_percentage' => $submission?->score_percentage,
                'grade_performance' => $submission?->grade_performance,
                'teacher_feedback' => $submission?->teacher_feedback,
                'submitted_at' => $submission?->submitted_at,
                'graded_at' => $submission?->graded_at,
            ];
        }

        if ($type === 'interactive') {
            return [
                'type' => 'interactive',
                'id' => $homework->id,
                'title' => $homework->title ?? 'واجب دورة تفاعلية',
                'description' => $homework->description ?? '',
                'due_date' => $homework->due_date,
                'status' => $submission?->submission_status ?? 'not_submitted',
                'status_text' => $submission?->submission_status_text ?? 'لم يتم التسليم',
                'is_late' => $submission?->is_late ?? false,
                'score' => $submission?->score,
                'max_score' => $homework->max_score ?? 10,
                'score_percentage' => $submission?->score_percentage,
                'grade_performance' => $submission?->grade_performance,
                'teacher_feedback' => $submission?->teacher_feedback,
                'submitted_at' => $submission?->submitted_at,
                'graded_at' => $submission?->graded_at,
            ];
        }

        // For other types, return basic structure
        return [
            'type' => $type,
            'id' => $homework->id,
            'title' => $homework->title ?? 'واجب',
            'description' => $homework->description ?? '',
            'due_date' => $homework->due_date ?? null,
            'status' => $submission?->status ?? 'not_submitted',
            'status_text' => $submission?->status_text ?? 'لم يتم التسليم',
            'is_late' => false,
            'score' => null,
            'max_score' => 10,
            'score_percentage' => null,
            'grade_performance' => null,
            'teacher_feedback' => null,
            'submitted_at' => null,
            'graded_at' => null,
        ];
    }
}
