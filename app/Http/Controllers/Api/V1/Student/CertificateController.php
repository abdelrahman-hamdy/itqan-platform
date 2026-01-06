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
        $type = $request->get('type'); // quran, academic, course

        $query = Certificate::where('user_id', $user->id)
            ->where('status', 'issued');

        if ($type) {
            $query->where('type', $type);
        }

        $certificates = $query->orderBy('issued_at', 'desc')->get();

        return $this->success([
            'certificates' => $certificates->map(fn ($cert) => [
                'id' => $cert->id,
                'type' => $cert->type,
                'title' => $cert->title,
                'description' => $cert->description,
                'certificate_number' => $cert->certificate_number,
                'issued_at' => $cert->issued_at?->toISOString(),
                'expires_at' => $cert->expires_at?->toISOString(),
                'is_expired' => $cert->expires_at && $cert->expires_at->isPast(),
                'preview_url' => $cert->preview_url ? asset('storage/'.$cert->preview_url) : null,
                'download_url' => route('api.v1.student.certificates.download', ['id' => $cert->id]),
                'share_url' => $cert->share_url,
                'issuer' => $cert->issuer_name,
            ])->toArray(),
            'total' => $certificates->count(),
        ], __('Certificates retrieved successfully'));
    }

    /**
     * Get a specific certificate.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $certificate = Certificate::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (! $certificate) {
            return $this->notFound(__('Certificate not found.'));
        }

        return $this->success([
            'certificate' => [
                'id' => $certificate->id,
                'type' => $certificate->type,
                'title' => $certificate->title,
                'description' => $certificate->description,
                'certificate_number' => $certificate->certificate_number,
                'issued_at' => $certificate->issued_at?->toISOString(),
                'expires_at' => $certificate->expires_at?->toISOString(),
                'is_expired' => $certificate->expires_at && $certificate->expires_at->isPast(),
                'status' => $certificate->status,
                'preview_url' => $certificate->preview_url ? asset('storage/'.$certificate->preview_url) : null,
                'download_url' => route('api.v1.student.certificates.download', ['id' => $certificate->id]),
                'share_url' => $certificate->share_url,
                'verification_url' => $certificate->verification_url,
                'issuer' => [
                    'name' => $certificate->issuer_name,
                    'logo' => $certificate->issuer_logo ? asset('storage/'.$certificate->issuer_logo) : null,
                ],
                'recipient' => [
                    'name' => $certificate->recipient_name,
                ],
                'metadata' => $certificate->metadata ?? [],
            ],
        ], __('Certificate retrieved successfully'));
    }

    /**
     * Download a certificate.
     */
    public function download(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $certificate = Certificate::where('id', $id)
            ->where('user_id', $user->id)
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

        // For API, return download URL instead of file stream
        $downloadUrl = Storage::disk('public')->temporaryUrl(
            $certificate->file_path,
            now()->addMinutes(30)
        );

        return $this->success([
            'download_url' => $downloadUrl,
            'filename' => $certificate->certificate_number.'.pdf',
            'expires_at' => now()->addMinutes(30)->toISOString(),
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
            'issued_at' => $certificate->issued_at?->toISOString(),
            'download_url' => route('api.v1.student.certificates.download', ['id' => $certificate->id]),
            'view_url' => $certificate->view_url,
        ];
    }
}
