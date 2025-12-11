<?php

namespace App\Http\Controllers\Api\V1\Student;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Certificate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    use ApiResponses;

    /**
     * Get all certificates for the student.
     *
     * @param Request $request
     * @return JsonResponse
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
            'certificates' => $certificates->map(fn($cert) => [
                'id' => $cert->id,
                'type' => $cert->type,
                'title' => $cert->title,
                'description' => $cert->description,
                'certificate_number' => $cert->certificate_number,
                'issued_at' => $cert->issued_at?->toISOString(),
                'expires_at' => $cert->expires_at?->toISOString(),
                'is_expired' => $cert->expires_at && $cert->expires_at->isPast(),
                'preview_url' => $cert->preview_url ? asset('storage/' . $cert->preview_url) : null,
                'download_url' => route('api.v1.student.certificates.download', ['id' => $cert->id]),
                'share_url' => $cert->share_url,
                'issuer' => $cert->issuer_name,
            ])->toArray(),
            'total' => $certificates->count(),
        ], __('Certificates retrieved successfully'));
    }

    /**
     * Get a specific certificate.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $certificate = Certificate::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$certificate) {
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
                'preview_url' => $certificate->preview_url ? asset('storage/' . $certificate->preview_url) : null,
                'download_url' => route('api.v1.student.certificates.download', ['id' => $certificate->id]),
                'share_url' => $certificate->share_url,
                'verification_url' => $certificate->verification_url,
                'issuer' => [
                    'name' => $certificate->issuer_name,
                    'logo' => $certificate->issuer_logo ? asset('storage/' . $certificate->issuer_logo) : null,
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
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse|\Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request, int $id)
    {
        $user = $request->user();

        $certificate = Certificate::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$certificate) {
            return $this->notFound(__('Certificate not found.'));
        }

        if (!$certificate->file_path || !Storage::disk('public')->exists($certificate->file_path)) {
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
            'filename' => $certificate->certificate_number . '.pdf',
            'expires_at' => now()->addMinutes(30)->toISOString(),
        ], __('Download URL generated'));
    }
}
