<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\ParentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Enums\SessionStatus;

/**
 * Parent Certificate Controller
 *
 * Handles viewing and downloading child certificates.
 * Supports filtering by child using query parameters.
 */
class ParentCertificateController extends Controller
{
    protected ParentDataService $dataService;

    public function __construct(ParentDataService $dataService)
    {
        $this->dataService = $dataService;

        // Enforce read-only access
        $this->middleware(function ($request, $next) {
            if (!in_array($request->method(), ['GET', 'HEAD'])) {
                abort(403, 'أولياء الأمور لديهم صلاحيات مشاهدة فقط');
            }
            return $next($request);
        });
    }

    /**
     * List certificates - supports filtering by child via session-based selection
     *
     * Uses the student view with parent layout for consistent design.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
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
     *
     * @param Request $request
     * @param int $certificateId
     * @return \Illuminate\View\View
     */
    public function show(Request $request, int $certificateId)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $parent->students()->with('user')->get();
        // Certificate.student_id references User.id, not StudentProfile.id
        $childUserIds = $children->pluck('user_id')->toArray();

        $certificate = Certificate::with('student')->findOrFail($certificateId);

        // Verify certificate belongs to one of parent's children
        if (!in_array($certificate->student_id, $childUserIds)) {
            abort(403, 'لا يمكنك الوصول إلى هذه الشهادة');
        }

        return view('parent.certificates.show', [
            'parent' => $parent,
            'children' => $children,
            'certificate' => $certificate,
        ]);
    }

    /**
     * Download certificate PDF
     *
     * @param Request $request
     * @param int $certificateId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request, int $certificateId)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $parent->students()->with('user')->get();
        // Certificate.student_id references User.id, not StudentProfile.id
        $childUserIds = $children->pluck('user_id')->toArray();

        $certificate = Certificate::findOrFail($certificateId);

        // Verify certificate belongs to one of parent's children
        if (!in_array($certificate->student_id, $childUserIds)) {
            abort(403, 'لا يمكنك الوصول إلى هذه الشهادة');
        }

        // Check if file exists
        if (!$certificate->file_path || !Storage::exists($certificate->file_path)) {
            abort(404, 'ملف الشهادة غير متوفر');
        }

        // Download certificate
        return Storage::download(
            $certificate->file_path,
            'certificate-' . $certificate->certificate_number . '.pdf'
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
