<?php

namespace App\Http\Controllers\Api\V1\ParentApi;

use App\Http\Controllers\Controller;
use App\Http\Traits\Api\ApiResponses;
use App\Models\Certificate;
use App\Models\ParentStudentRelationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    use ApiResponses;

    /**
     * Get all certificates for linked children.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children
        $children = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->with('student.user')
            ->get();

        $certificates = [];

        foreach ($children as $relationship) {
            $student = $relationship->student;
            $studentUserId = $student->user?->id ?? $student->id;

            // Filter by specific child if requested
            if ($request->filled('child_id') && $student->id != $request->child_id) {
                continue;
            }

            // Get certificates - use student_id (StudentProfile.id), not user_id
            $childCertificates = Certificate::where('student_id', $student->id)
                ->with(['certificatable'])
                ->orderBy('issued_at', 'desc')
                ->get();

            foreach ($childCertificates as $cert) {
                $certificates[] = [
                    'id' => $cert->id,
                    'child_id' => $student->id,
                    'child_name' => $student->full_name,
                    'title' => $cert->title,
                    'type' => $cert->type,
                    'description' => $cert->description,
                    'certificate_number' => $cert->certificate_number,
                    'issued_at' => $cert->issued_at?->toDateString(),
                    'expires_at' => $cert->expires_at?->toDateString(),
                    'is_expired' => $cert->expires_at ? $cert->expires_at->isPast() : false,
                    'thumbnail_url' => $cert->thumbnail_url ? asset('storage/' . $cert->thumbnail_url) : null,
                    'created_at' => $cert->created_at->toISOString(),
                ];
            }
        }

        // Sort by issued date
        usort($certificates, fn($a, $b) =>
            strtotime($b['issued_at'] ?? $b['created_at']) <=> strtotime($a['issued_at'] ?? $a['created_at'])
        );

        // Pagination
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);
        $total = count($certificates);
        $certificates = array_slice($certificates, ($page - 1) * $perPage, $perPage);

        return $this->success([
            'certificates' => array_values($certificates),
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
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
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Get all linked children's student profile IDs
        $childStudentIds = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->pluck('student_id')
            ->toArray();

        $certificate = Certificate::where('id', $id)
            ->whereIn('student_id', $childStudentIds)
            ->with(['certificatable'])
            ->first();

        if (!$certificate) {
            return $this->notFound(__('Certificate not found.'));
        }

        // Get child info
        $childRelation = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $certificate->student_id)
            ->with('student')
            ->first();

        return $this->success([
            'certificate' => [
                'id' => $certificate->id,
                'child' => $childRelation?->student ? [
                    'id' => $childRelation->student->id,
                    'name' => $childRelation->student->full_name,
                ] : null,
                'title' => $certificate->title,
                'type' => $certificate->type,
                'description' => $certificate->description,
                'certificate_number' => $certificate->certificate_number,
                'issued_at' => $certificate->issued_at?->toDateString(),
                'expires_at' => $certificate->expires_at?->toDateString(),
                'is_expired' => $certificate->expires_at ? $certificate->expires_at->isPast() : false,
                'issuer' => $certificate->issuer_name ?? $certificate->academy?->name,
                'thumbnail_url' => $certificate->thumbnail_url ? asset('storage/' . $certificate->thumbnail_url) : null,
                'download_url' => $certificate->file_path ? route('certificates.download', $certificate->id) : null,
                'verification_url' => $certificate->certificate_number
                    ? route('certificates.verify', $certificate->certificate_number)
                    : null,
                'metadata' => $certificate->metadata ?? [],
                'certificatable' => $certificate->certificatable ? [
                    'type' => class_basename($certificate->certificatable_type),
                    'id' => $certificate->certificatable_id,
                    'name' => $certificate->certificatable->title ?? $certificate->certificatable->name ?? null,
                ] : null,
                'created_at' => $certificate->created_at->toISOString(),
            ],
        ], __('Certificate retrieved successfully'));
    }

    /**
     * Get certificates for a specific child.
     *
     * @param Request $request
     * @param int $childId
     * @return JsonResponse
     */
    public function childCertificates(Request $request, int $childId): JsonResponse
    {
        $user = $request->user();
        $parentProfile = $user->parentProfile()->first();

        if (!$parentProfile) {
            return $this->error(__('Parent profile not found.'), 404, 'PARENT_PROFILE_NOT_FOUND');
        }

        // Verify child is linked
        $relationship = ParentStudentRelationship::where('parent_id', $parentProfile->id)
            ->where('student_id', $childId)
            ->with('student.user')
            ->first();

        if (!$relationship) {
            return $this->notFound(__('Child not found.'));
        }

        $student = $relationship->student;

        $certificates = Certificate::where('student_id', $student->id)
            ->with(['certificatable'])
            ->orderBy('issued_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success([
            'child' => [
                'id' => $student->id,
                'name' => $student->full_name,
            ],
            'certificates' => collect($certificates->items())->map(fn($cert) => [
                'id' => $cert->id,
                'title' => $cert->title,
                'type' => $cert->type,
                'description' => $cert->description,
                'certificate_number' => $cert->certificate_number,
                'issued_at' => $cert->issued_at?->toDateString(),
                'expires_at' => $cert->expires_at?->toDateString(),
                'is_expired' => $cert->expires_at ? $cert->expires_at->isPast() : false,
                'thumbnail_url' => $cert->thumbnail_url ? asset('storage/' . $cert->thumbnail_url) : null,
                'download_url' => $cert->file_path ? route('certificates.download', $cert->id) : null,
            ])->toArray(),
            'pagination' => [
                'current_page' => $certificates->currentPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
                'total_pages' => $certificates->lastPage(),
                'has_more' => $certificates->hasMorePages(),
            ],
        ], __('Child certificates retrieved successfully'));
    }
}
