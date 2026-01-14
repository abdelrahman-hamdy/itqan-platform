<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Enums\CertificateType;
use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Certificate;
use App\Models\CourseSubscription;
use App\Models\InteractiveCourseEnrollment;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    use ApiResponses;

    /**
     * Get all certificates for the student.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $type = $request->get('type'); // quran, academic, interactive_course, recorded_course

        $query = Certificate::where('student_id', $user->id)
            ->with(['student', 'teacher', 'academy', 'certificateable']);

        if ($type) {
            $query->where('certificate_type', $type);
        }

        $certificates = $query->orderBy('issued_at', 'desc')->get();

        return $this->success([
            'certificates' => $certificates->map(fn ($cert) => [
                'id' => $cert->id,
                'type' => $cert->certificate_type instanceof \App\Enums\CertificateType
                    ? $cert->certificate_type->value
                    : $cert->certificate_type,
                'title' => $this->getCertificateTitle($cert),
                'description' => $cert->certificate_text,
                'certificate_number' => $cert->certificate_number,
                'issued_at' => $cert->issued_at?->toISOString(),
                'download_url' => route('api.v1.student.certificates.download', ['id' => $cert->id]),
                'view_url' => $cert->view_url,
                'issuer' => $cert->academy?->name ?? __('Itqan Academy'),
                'teacher_name' => $cert->teacher?->full_name,
            ])->toArray(),
            'total' => $certificates->count(),
        ], __('Certificates retrieved successfully'));
    }

    /**
     * Get a specific certificate.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $certificate = Certificate::where('id', $id)
            ->where('student_id', $user->id)
            ->with(['student', 'teacher', 'academy', 'certificateable'])
            ->first();

        if (! $certificate) {
            return $this->notFound(__('Certificate not found.'));
        }

        return $this->success([
            'certificate' => [
                'id' => $certificate->id,
                'type' => $certificate->certificate_type instanceof \App\Enums\CertificateType
                    ? $certificate->certificate_type->value
                    : $certificate->certificate_type,
                'title' => $this->getCertificateTitle($certificate),
                'description' => $certificate->certificate_text,
                'certificate_number' => $certificate->certificate_number,
                'issued_at' => $certificate->issued_at?->toISOString(),
                'template_style' => $certificate->template_style instanceof \App\Enums\CertificateTemplateStyle
                    ? $certificate->template_style->value
                    : $certificate->template_style,
                'download_url' => route('api.v1.student.certificates.download', ['id' => $certificate->id]),
                'view_url' => $certificate->view_url,
                'issuer' => [
                    'name' => $certificate->academy?->name ?? __('Itqan Academy'),
                    'logo' => $certificate->academy?->logo ? asset('storage/'.$certificate->academy->logo) : null,
                ],
                'recipient' => [
                    'name' => $certificate->student?->full_name ?? $user->full_name,
                ],
                'teacher' => $certificate->teacher ? [
                    'name' => $certificate->teacher->full_name,
                ] : null,
                'metadata' => $certificate->metadata ?? [],
            ],
        ], __('Certificate retrieved successfully'));
    }

    /**
     * Download a certificate.
     */
    public function download(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $certificate = Certificate::where('id', $id)
            ->where('student_id', $user->id)
            ->first();

        if (! $certificate) {
            return $this->notFound(__('Certificate not found.'));
        }

        if (! $certificate->file_path || ! Storage::disk('public')->exists($certificate->file_path)) {
            return $this->error(
                __('Certificate file not available.'),
                404,
                'FILE_NOT_FOUND'
            );
        }

        // For API, return download URL using public storage URL
        $downloadUrl = asset('storage/'.$certificate->file_path);

        return $this->success([
            'download_url' => $downloadUrl,
            'filename' => ($certificate->certificate_number ?? 'certificate').'.pdf',
        ], __('Download URL generated'));
    }

    /**
     * Request a certificate for a completed course/program.
     */
    public function request(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => ['required', 'string', 'in:recorded_course,interactive_course'],
            'subscription_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $user = $request->user();
        $type = $request->input('type');
        $subscriptionId = $request->input('subscription_id');

        try {
            $certificateService = app(CertificateService::class);

            if ($type === 'recorded_course') {
                return $this->requestRecordedCourseCertificate(
                    $certificateService,
                    $user,
                    $subscriptionId
                );
            }

            if ($type === 'interactive_course') {
                return $this->requestInteractiveCourseCertificate(
                    $certificateService,
                    $user,
                    $subscriptionId
                );
            }

            return $this->error(
                __('Invalid certificate type.'),
                400,
                'INVALID_TYPE'
            );
        } catch (\Exception $e) {
            Log::error('Certificate request failed', [
                'user_id' => $user->id,
                'type' => $type,
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return $this->error(
                $e->getMessage(),
                400,
                'CERTIFICATE_ERROR'
            );
        }
    }

    /**
     * Request certificate for a recorded course.
     */
    protected function requestRecordedCourseCertificate(
        CertificateService $certificateService,
        $user,
        string $subscriptionId
    ): JsonResponse {
        $subscription = CourseSubscription::where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->with(['recordedCourse', 'certificate'])
            ->first();

        if (! $subscription) {
            return $this->notFound(__('Course subscription not found.'));
        }

        // Check if certificate already issued
        if ($subscription->certificate_issued && $subscription->certificate) {
            return $this->success([
                'certificate' => $this->formatCertificateResponse($subscription->certificate),
                'already_issued' => true,
            ], __('Certificate already issued'));
        }

        // Check completion status
        if ($subscription->progress_percentage < 100) {
            return $this->error(
                __('You must complete 100% of the course to receive a certificate. Current progress: :progress%', [
                    'progress' => $subscription->progress_percentage,
                ]),
                400,
                'NOT_COMPLETED'
            );
        }

        // Issue certificate
        $certificate = $certificateService->issueCertificateForRecordedCourse($subscription);

        return $this->created([
            'certificate' => $this->formatCertificateResponse($certificate),
        ], __('Certificate issued successfully'));
    }

    /**
     * Request certificate for an interactive course.
     */
    protected function requestInteractiveCourseCertificate(
        CertificateService $certificateService,
        $user,
        string $subscriptionId
    ): JsonResponse {
        $enrollment = InteractiveCourseEnrollment::where('id', $subscriptionId)
            ->where('student_id', $user->id)
            ->with(['course', 'certificate'])
            ->first();

        if (! $enrollment) {
            return $this->notFound(__('Course enrollment not found.'));
        }

        // Check if certificate already issued
        if ($enrollment->certificate_issued && $enrollment->certificate) {
            return $this->success([
                'certificate' => $this->formatCertificateResponse($enrollment->certificate),
                'already_issued' => true,
            ], __('Certificate already issued'));
        }

        // Check completion status
        $completedStatus = EnrollmentStatus::COMPLETED->value ?? 'completed';
        if ($enrollment->enrollment_status !== $completedStatus &&
            $enrollment->enrollment_status !== EnrollmentStatus::COMPLETED) {
            return $this->error(
                __('You must complete the course to receive a certificate. Current status: :status', [
                    'status' => $enrollment->enrollment_status,
                ]),
                400,
                'NOT_COMPLETED'
            );
        }

        // Issue certificate
        $certificate = $certificateService->issueCertificateForInteractiveCourse($enrollment);

        return $this->created([
            'certificate' => $this->formatCertificateResponse($certificate),
        ], __('Certificate issued successfully'));
    }

    /**
     * Format certificate response.
     */
    protected function formatCertificateResponse(Certificate $certificate): array
    {
        return [
            'id' => $certificate->id,
            'certificate_number' => $certificate->certificate_number,
            'type' => $certificate->certificate_type instanceof CertificateType
                ? $certificate->certificate_type->value
                : $certificate->certificate_type,
            'title' => $this->getCertificateTitle($certificate),
            'issued_at' => $certificate->issued_at?->toISOString(),
            'download_url' => route('api.v1.student.certificates.download', ['id' => $certificate->id]),
            'view_url' => $certificate->view_url,
        ];
    }

    /**
     * Get the title for a certificate based on its type and related model.
     */
    protected function getCertificateTitle(Certificate $certificate): string
    {
        $certificateable = $certificate->certificateable;

        if ($certificateable) {
            if ($certificateable instanceof \App\Models\QuranCircle) {
                return __('شهادة إتمام حلقة :name', ['name' => $certificateable->name]);
            }
            if ($certificateable instanceof \App\Models\InteractiveCourse) {
                return __('شهادة إتمام دورة :title', ['title' => $certificateable->title]);
            }
            if ($certificateable instanceof \App\Models\RecordedCourse) {
                return __('شهادة إتمام دورة :title', ['title' => $certificateable->title]);
            }
        }

        // Fallback based on certificate type
        $type = $certificate->certificate_type instanceof CertificateType
            ? $certificate->certificate_type
            : CertificateType::tryFrom($certificate->certificate_type);

        return match ($type) {
            CertificateType::QURAN => __('شهادة قرآن'),
            CertificateType::ACADEMIC => __('شهادة أكاديمية'),
            CertificateType::INTERACTIVE_COURSE => __('شهادة دورة تفاعلية'),
            CertificateType::RECORDED_COURSE => __('شهادة دورة مسجلة'),
            default => __('شهادة'),
        };
    }
}
