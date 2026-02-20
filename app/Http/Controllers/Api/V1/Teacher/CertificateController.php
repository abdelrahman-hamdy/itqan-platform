<?php

namespace App\Http\Controllers\Api\V1\Teacher;

use Exception;
use App\Enums\CertificateTemplateStyle;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\AcademicIndividualLesson;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourse;
use App\Models\QuranCircle;
use App\Models\QuranIndividualCircle;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    use ApiResponses;

    public function __construct(
        protected CertificateService $certificateService
    ) {}

    /**
     * Issue a certificate for a student.
     */
    public function issue(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:quran_individual,quran_group,academic,interactive',
            'entity_id' => 'required|integer',
            'student_id' => 'required|integer',
            'achievement_text' => 'required|string|max:1000',
            'template_style' => 'nullable|string|in:'.implode(',', CertificateTemplateStyle::values()),
        ]);

        if ($validator->fails()) {
            return $this->error(__('Validation failed.'), 422, 'VALIDATION_ERROR', $validator->errors()->toArray());
        }

        $user = $request->user();
        $type = $request->type;
        $entityId = $request->entity_id;
        $studentId = $request->student_id;
        $achievementText = $request->achievement_text;
        $templateStyle = $request->template_style ?? CertificateTemplateStyle::TEMPLATE_1->value;

        try {
            DB::beginTransaction();

            $certificate = match ($type) {
                'quran_individual' => $this->issueQuranIndividualCertificate(
                    $user,
                    $entityId,
                    $achievementText,
                    $templateStyle
                ),
                'quran_group' => $this->issueQuranGroupCertificate(
                    $user,
                    $entityId,
                    $studentId,
                    $achievementText,
                    $templateStyle
                ),
                'academic' => $this->issueAcademicCertificate(
                    $user,
                    $entityId,
                    $achievementText,
                    $templateStyle
                ),
                'interactive' => $this->issueInteractiveCertificate(
                    $user,
                    $entityId,
                    $studentId,
                    $achievementText,
                    $templateStyle
                ),
            };

            DB::commit();

            return $this->success([
                'certificate' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'issued_at' => $certificate->issued_at?->toISOString(),
                    'view_url' => $certificate->view_url,
                    'download_url' => $certificate->download_url,
                ],
            ], __('Certificate issued successfully'));

        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage(), 400, 'CERTIFICATE_ISSUE_FAILED');
        }
    }

    /**
     * Issue certificate for Quran individual circle.
     */
    protected function issueQuranIndividualCertificate(
        User $user,
        int $circleId,
        string $achievementText,
        string $templateStyle
    ) {
        if (! $user->quranTeacherProfile) {
            throw new Exception(__('Quran teacher profile not found.'));
        }

        $quranTeacherId = $user->id;

        $circle = QuranIndividualCircle::where('id', $circleId)
            ->where('quran_teacher_id', $quranTeacherId)
            ->with('subscription')
            ->first();

        if (! $circle) {
            throw new Exception(__('Circle not found.'));
        }

        if (! $circle->subscription) {
            throw new Exception(__('No subscription found for this circle.'));
        }

        if ($circle->subscription->certificate_issued) {
            throw new Exception(__('Certificate already issued for this subscription.'));
        }

        return $this->certificateService->issueManualCertificate(
            $circle->subscription,
            $achievementText,
            CertificateTemplateStyle::from($templateStyle),
            $user->id,
            $quranTeacherId
        );
    }

    /**
     * Issue certificate for Quran group circle student.
     */
    protected function issueQuranGroupCertificate(
        User $user,
        int $circleId,
        int $studentId,
        string $achievementText,
        string $templateStyle
    ) {
        if (! $user->quranTeacherProfile) {
            throw new Exception(__('Quran teacher profile not found.'));
        }

        $quranTeacherId = $user->id;

        $circle = QuranCircle::where('id', $circleId)
            ->where('quran_teacher_id', $quranTeacherId)
            ->first();

        if (! $circle) {
            throw new Exception(__('Circle not found.'));
        }

        // SECURITY: Verify enrollment FIRST, then get student from enrolled list
        // This prevents user enumeration attacks via timing/error differences
        $student = $circle->students()->where('users.id', $studentId)->first();
        if (! $student) {
            // Generic message prevents enumeration - attacker can't tell if user exists but isn't enrolled
            throw new Exception(__('Student is not enrolled in this circle.'));
        }

        return $this->certificateService->issueGroupCircleCertificate(
            $circle,
            $student,
            $achievementText,
            CertificateTemplateStyle::from($templateStyle),
            $user->id
        );
    }

    /**
     * Issue certificate for Academic individual lesson.
     */
    protected function issueAcademicCertificate(
        User $user,
        int $lessonId,
        string $achievementText,
        string $templateStyle
    ) {
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            throw new Exception(__('Academic teacher profile not found.'));
        }

        $lesson = AcademicIndividualLesson::where('id', $lessonId)
            ->where('academic_teacher_id', $academicTeacherId)
            ->with('subscription')
            ->first();

        if (! $lesson) {
            throw new Exception(__('Lesson not found.'));
        }

        if (! $lesson->subscription) {
            throw new Exception(__('No subscription found for this lesson.'));
        }

        if ($lesson->subscription->certificate_issued) {
            throw new Exception(__('Certificate already issued for this subscription.'));
        }

        return $this->certificateService->issueManualCertificate(
            $lesson->subscription,
            $achievementText,
            CertificateTemplateStyle::from($templateStyle),
            $user->id,
            $user->id
        );
    }

    /**
     * Issue certificate for Interactive course student.
     */
    protected function issueInteractiveCertificate(
        User $user,
        int $courseId,
        int $studentId,
        string $achievementText,
        string $templateStyle
    ) {
        $academicTeacherId = $user->academicTeacherProfile?->id;

        if (! $academicTeacherId) {
            throw new Exception(__('Academic teacher profile not found.'));
        }

        $course = InteractiveCourse::where('id', $courseId)
            ->where('assigned_teacher_id', $academicTeacherId)
            ->first();

        if (! $course) {
            throw new Exception(__('Course not found.'));
        }

        // SECURITY: Verify enrollment FIRST, then get student from enrolled list
        // This prevents user enumeration attacks via timing/error differences
        $student = User::whereHas('courseSubscriptions', function ($query) use ($courseId) {
            $query->where('course_id', $courseId);
        })->where('id', $studentId)->first();

        if (! $student) {
            // Generic message prevents enumeration - attacker can't tell if user exists but isn't enrolled
            throw new Exception(__('Student is not enrolled in this course.'));
        }

        return $this->certificateService->issueInteractiveCourseCertificate(
            $course,
            $student,
            $achievementText,
            CertificateTemplateStyle::from($templateStyle),
            $user->id
        );
    }
}
