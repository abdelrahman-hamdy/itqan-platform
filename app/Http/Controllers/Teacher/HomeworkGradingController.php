<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AcademicHomework;
use App\Models\AcademicHomeworkSubmission;
use App\Services\HomeworkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Enums\SessionStatus;

class HomeworkGradingController extends Controller
{
    protected HomeworkService $homeworkService;

    public function __construct(HomeworkService $homeworkService)
    {
        $this->middleware('auth');
        $this->middleware('role:academic_teacher');
        $this->homeworkService = $homeworkService;
    }

    /**
     * Display a listing of homework for grading
     */
    public function index(Request $request): View
    {
        $teacher = Auth::user();
        $academyId = $teacher->academy_id;

        // Get filter parameters
        $needsGrading = $request->get('needs_grading', false);

        // Get homework for teacher
        $homework = $this->homeworkService->getTeacherHomework($teacher->id, $academyId, $needsGrading);

        // Get submissions needing grading
        $pendingSubmissions = $this->homeworkService->getSubmissionsNeedingGrading($teacher->id, $academyId);

        // Get statistics
        $statistics = $this->homeworkService->getTeacherHomeworkStatistics($teacher->id, $academyId);

        return view('teacher.homework.index', compact('homework', 'pendingSubmissions', 'statistics'));
    }

    /**
     * Show the grading form for a specific submission
     */
    public function grade($submissionId): View|RedirectResponse
    {
        $teacher = Auth::user();
        $academyId = $teacher->academy_id;

        // Get submission with relationships
        $submission = AcademicHomeworkSubmission::with(['homework', 'student', 'session'])
            ->where('id', $submissionId)
            ->where('academy_id', $academyId)
            ->first();

        if (!$submission) {
            return redirect()->route('teacher.homework.index')
                ->with('error', 'لم يتم العثور على الواجب المطلوب');
        }

        // Verify teacher owns this homework
        $homework = $submission->homework;
        if ($homework->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.homework.index')
                ->with('error', 'ليس لديك صلاحية لتصحيح هذا الواجب');
        }

        return view('teacher.homework.grade', compact('submission', 'homework'));
    }

    /**
     * Process grading of a submission
     */
    public function gradeProcess(Request $request, $submissionId): RedirectResponse
    {
        $teacher = Auth::user();
        $academyId = $teacher->academy_id;

        // Get submission
        $submission = AcademicHomeworkSubmission::with('homework')
            ->where('id', $submissionId)
            ->where('academy_id', $academyId)
            ->first();

        if (!$submission) {
            return redirect()->route('teacher.homework.index')
                ->with('error', 'لم يتم العثور على الواجب المطلوب');
        }

        // Verify teacher owns this homework
        if ($submission->homework->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.homework.index')
                ->with('error', 'ليس لديك صلاحية لتصحيح هذا الواجب');
        }

        // Validate grading data
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:' . $submission->max_score,
            'teacher_feedback' => 'required|string|min:10',
            'content_quality_score' => 'nullable|numeric|min:0|max:100',
            'presentation_score' => 'nullable|numeric|min:0|max:100',
            'effort_score' => 'nullable|numeric|min:0|max:100',
            'creativity_score' => 'nullable|numeric|min:0|max:100',
        ], [
            'score.required' => 'يجب إدخال الدرجة',
            'score.max' => 'الدرجة يجب أن لا تتجاوز ' . $submission->max_score,
            'teacher_feedback.required' => 'يجب كتابة ملاحظات وتعليقات',
            'teacher_feedback.min' => 'يجب أن تكون الملاحظات 10 أحرف على الأقل',
        ]);

        try {
            // Prepare quality scores array
            $qualityScores = null;
            if ($request->has('content_quality_score') || $request->has('presentation_score') ||
                $request->has('effort_score') || $request->has('creativity_score')) {
                $qualityScores = [
                    'content' => $request->input('content_quality_score'),
                    'presentation' => $request->input('presentation_score'),
                    'effort' => $request->input('effort_score'),
                    'creativity' => $request->input('creativity_score'),
                ];
            }

            // Grade the submission
            $this->homeworkService->gradeAcademicHomework(
                $submissionId,
                $validated['score'],
                $validated['teacher_feedback'],
                $qualityScores,
                $teacher->id
            );

            // Check which action was requested
            $action = $request->input('action');

            if ($action === 'grade_and_return') {
                // Grade and return to student
                $this->homeworkService->returnHomeworkToStudent($submissionId);
                return redirect()->route('teacher.homework.index')
                    ->with('success', 'تم تصحيح الواجب وإرجاعه للطالب بنجاح');
            } elseif ($action === 'update_grade') {
                // Update existing grade
                return redirect()->route('teacher.homework.grade', $submissionId)
                    ->with('success', 'تم تحديث التقييم بنجاح');
            } elseif ($action === 'return_to_student') {
                // Return already graded homework to student
                $this->homeworkService->returnHomeworkToStudent($submissionId);
                return redirect()->route('teacher.homework.index')
                    ->with('success', 'تم إرجاع الواجب للطالب بنجاح');
            } else {
                // Default: just save the grade
                return redirect()->route('teacher.homework.grade', $submissionId)
                    ->with('success', 'تم حفظ التقييم بنجاح');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تصحيح الواجب: ' . $e->getMessage());
        }
    }

    /**
     * Request revision for a submission
     */
    public function requestRevision(Request $request, $submissionId): RedirectResponse
    {
        $teacher = Auth::user();
        $academyId = $teacher->academy_id;

        // Get submission
        $submission = AcademicHomeworkSubmission::with('homework')
            ->where('id', $submissionId)
            ->where('academy_id', $academyId)
            ->first();

        if (!$submission) {
            return redirect()->route('teacher.homework.index')
                ->with('error', 'لم يتم العثور على الواجب المطلوب');
        }

        // Verify teacher owns this homework
        if ($submission->homework->teacher_id !== $teacher->id) {
            return redirect()->route('teacher.homework.index')
                ->with('error', 'ليس لديك صلاحية لطلب تعديل على هذا الواجب');
        }

        // Validate revision request
        $validated = $request->validate([
            'revision_reason' => 'required|string|min:10',
        ], [
            'revision_reason.required' => 'يجب كتابة سبب طلب التعديل',
            'revision_reason.min' => 'يجب أن يكون السبب 10 أحرف على الأقل',
        ]);

        try {
            $this->homeworkService->requestRevision($submissionId, $validated['revision_reason']);

            return redirect()->route('teacher.homework.index')
                ->with('success', 'تم طلب التعديل من الطالب بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'حدث خطأ أثناء طلب التعديل: ' . $e->getMessage());
        }
    }

    /**
     * Show homework statistics for teacher
     */
    public function statistics(): View
    {
        $teacher = Auth::user();
        $academyId = $teacher->academy_id;

        $statistics = $this->homeworkService->getTeacherHomeworkStatistics($teacher->id, $academyId);
        $homework = $this->homeworkService->getTeacherHomework($teacher->id, $academyId);

        return view('teacher.homework.statistics', compact('statistics', 'homework'));
    }
}
