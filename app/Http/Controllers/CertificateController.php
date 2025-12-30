<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Http\Requests\PreviewCertificateRequest;
use App\Http\Requests\RequestInteractiveCourseCertificateRequest;
use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CertificateController extends Controller
{
    use ApiResponses;

    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Display a listing of student's certificates
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certificate::class);

        $user = Auth::user();
        $academy = $request->get('academy') ?? session('current_academy');

        $query = Certificate::query()
            ->where('student_id', $user->id)
            ->with(['academy', 'teacher', 'certificateable'])
            ->orderBy('issued_at', 'desc');

        // Filter by academy if provided
        if ($academy) {
            $query->where('academy_id', $academy->id ?? $academy);
        }

        // Filter by type if provided
        if ($request->has('type') && $request->type) {
            $query->where('certificate_type', $request->type);
        }

        $certificates = $query->paginate(12)->withQueryString();

        return view('student.certificates', [
            'certificates' => $certificates,
            'academy' => $academy,
        ]);
    }

    /**
     * Download certificate PDF
     */
    public function download(string $subdomain, string $certificate): Response|RedirectResponse
    {
        $certificate = Certificate::findOrFail($certificate);

        // Authorization
        $this->authorize('download', $certificate);

        try {
            return $this->certificateService->downloadCertificate($certificate);
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء تحميل الشهادة: '.$e->getMessage());
        }
    }

    /**
     * View certificate in browser
     */
    public function view(string $subdomain, string $certificate): Response|RedirectResponse
    {
        $certificate = Certificate::findOrFail($certificate);

        // Authorization
        $this->authorize('view', $certificate);

        try {
            return $this->certificateService->streamCertificate($certificate);
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء عرض الشهادة: '.$e->getMessage());
        }
    }

    /**
     * Preview certificate (for teachers/admins)
     */
    public function preview(PreviewCertificateRequest $request): Response|JsonResponse
    {
        $this->authorize('create', Certificate::class);

        $data = $request->validated();

        // Add default values
        $data['certificate_number'] = 'PREVIEW-'.now()->format('YmdHis');
        $data['issued_date'] = now()->format('Y-m-d');
        $data['issued_date_formatted'] = now()->locale('ar')->translatedFormat('d F Y');
        $data['metadata'] = [];

        try {
            $pdf = $this->certificateService->previewCertificate($data, $data['template_style']);

            // TCPDF Output: 'I' = inline (browser display)
            return response($pdf->Output('', 'S'), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="certificate-preview.pdf"');
        } catch (\Exception $e) {
            \Log::error('Certificate preview error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return $this->serverError('حدث خطأ أثناء معاينة الشهادة: '.$e->getMessage());
        }
    }

    /**
     * Request certificate for interactive course (student action)
     */
    public function requestForInteractiveCourse(RequestInteractiveCourseCertificateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $enrollment = \App\Models\InteractiveCourseEnrollment::findOrFail($validated['enrollment_id']);

        // Authorization: Ensure the current user owns this enrollment
        if (Auth::id() !== $enrollment->student_id) {
            $this->authorize('view', $enrollment); // Will fail with 403 if not authorized
        }

        // Check if certificate already issued
        if ($enrollment->certificate_issued) {
            return back()->with('info', 'تم إصدار الشهادة مسبقاً.');
        }

        try {
            $certificate = $this->certificateService->issueCertificateForInteractiveCourse($enrollment);

            return back()->with('success', 'تم إصدار شهادتك بنجاح! يمكنك تحميلها الآن.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
