<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\ParentChildVerificationService;
use App\Services\ParentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Parent Certificate Controller
 *
 * Handles viewing and downloading child certificates.
 * Supports filtering by child using query parameters.
 */
class ParentCertificateController extends Controller
{
    public function __construct(
        protected ParentDataService $dataService,
        protected ParentChildVerificationService $verificationService
    ) {
        // Enforce read-only access
        $this->middleware('parent.readonly');
    }

    /**
     * List certificates - supports filtering by child via session-based selection
     *
     * Uses the student view with parent layout for consistent design.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certificate::class);

        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get child USER IDs from middleware (session-based selection)
        // Note: Certificate.student_id references User.id, not StudentProfile.id
        $childUserIds = \App\Http\Middleware\ChildSelectionMiddleware::getChildUserIds();

        // Build certificates query
        $query = Certificate::whereIn('student_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->with(['student']);

        // Apply type filter
        if ($request->has('type') && $request->type !== 'all') {
            $typeMap = [
                'recorded_course' => 'App\\Models\\RecordedCourse',
                'interactive_course' => 'App\\Models\\InteractiveCourse',
                'quran_subscription' => 'App\\Models\\QuranSubscription',
                'academic_subscription' => 'App\\Models\\AcademicSubscription',
            ];

            if (isset($typeMap[$request->type])) {
                $query->where('certificateable_type', $typeMap[$request->type]);
            }
        }

        // Paginate results
        $certificates = $query->orderBy('issued_at', 'desc')->paginate(12);

        // Return student view with parent layout
        return view('student.certificates', [
            'certificates' => $certificates,
            'layout' => 'parent',
        ]);
    }

    /**
     * View certificate
     */
    public function show(Request $request, int $certificateId): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $this->verificationService->getChildrenWithUsers($parent);

        $certificate = Certificate::with('student')->findOrFail($certificateId);

        $this->authorize('view', $certificate);

        // Verify certificate belongs to one of parent's children
        $this->verificationService->verifyCertificateBelongsToParent($parent, $certificate);

        return view('parent.certificates.show', [
            'parent' => $parent,
            'children' => $children,
            'certificate' => $certificate,
        ]);
    }

    /**
     * Download certificate PDF
     */
    public function download(Request $request, int $certificateId): StreamedResponse
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        $certificate = Certificate::findOrFail($certificateId);

        $this->authorize('download', $certificate);

        // Verify certificate belongs to one of parent's children
        $this->verificationService->verifyCertificateBelongsToParent($parent, $certificate);

        // Check if file exists
        if (! $certificate->file_path || ! Storage::exists($certificate->file_path)) {
            abort(404, 'ملف الشهادة غير متوفر');
        }

        // Download certificate
        return Storage::download(
            $certificate->file_path,
            'certificate-'.$certificate->certificate_number.'.pdf'
        );
    }

    /**
     * Helper: Get student IDs for children based on filter
     */
    protected function getChildIds($children, $selectedChildId): array
    {
        if ($selectedChildId === 'all') {
            return $children->pluck('id')->toArray();
        }

        // Find the specific child
        $child = $children->firstWhere('id', $selectedChildId);
        if ($child) {
            return [$child->id];
        }

        // Fallback to all children if invalid selection
        return $children->pluck('id')->toArray();
    }
}
