<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController extends Controller
{
    protected CertificateService $certificateService;

    public function __construct(CertificateService $certificateService)
    {
        $this->certificateService = $certificateService;
    }

    /**
     * Display a listing of student's certificates
     */
    public function index(Request $request)
    {
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
    public function download(string $subdomain, string $certificate)
    {
        $certificate = Certificate::findOrFail($certificate);

        // Authorization
        $this->authorize('view', $certificate);

        try {
            return $this->certificateService->downloadCertificate($certificate);
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء تحميل الشهادة: ' . $e->getMessage());
        }
    }

    /**
     * View certificate in browser
     */
    public function view(string $subdomain, string $certificate)
    {
        $certificate = Certificate::findOrFail($certificate);

        // Authorization
        $this->authorize('view', $certificate);

        try {
            return $this->certificateService->streamCertificate($certificate);
        } catch (\Exception $e) {
            return back()->with('error', 'حدث خطأ أثناء عرض الشهادة: ' . $e->getMessage());
        }
    }

    /**
     * Preview certificate (for teachers/admins)
     */
    public function preview(Request $request)
    {
        // Authorization - only teachers and admins
        if (!Auth::user()->hasAnyRole(['teacher', 'quran_teacher', 'academic_teacher', 'admin', 'super_admin'])) {
            abort(403);
        }

        $data = $request->validate([
            'student_name' => 'required|string',
            'certificate_text' => 'required|string',
            'teacher_name' => 'nullable|string',
            'academy_name' => 'required|string',
            'template_style' => 'required|string',
        ]);

        // Add default values
        $data['certificate_number'] = 'PREVIEW-' . now()->format('YmdHis');
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
            return response()->json(['error' => 'حدث خطأ أثناء معاينة الشهادة: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Request certificate for interactive course (student action)
     */
    public function requestForInteractiveCourse(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'enrollment_id' => 'required|exists:interactive_course_enrollments,id',
        ]);

        $enrollment = \App\Models\InteractiveCourseEnrollment::findOrFail($validated['enrollment_id']);

        // Check if student owns this enrollment
        if ($enrollment->student_id !== $user->id) {
            return back()->with('error', 'غير مصرح لك بطلب هذه الشهادة.');
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
