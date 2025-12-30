<?php

namespace App\Http\Controllers;

use App\Http\Traits\Api\ApiResponses;
use App\Models\CourseSubscription;
use App\Models\Payment;
use App\Services\Student\StudentPaymentQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class StudentPaymentController extends Controller
{
    use ApiResponses;
    public function __construct(
        protected StudentPaymentQueryService $paymentQueryService
    ) {}

    public function payments(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $user = Auth::user();

        // Get payment history using service
        $payments = $this->paymentQueryService->getPaymentHistory($user, $request, 15);

        // Get payment statistics using service
        $stats = $this->paymentQueryService->getPaymentStatistics($user);

        return view('student.payments', compact('payments', 'stats'));
    }

    public function certificates(): View
    {
        $user = Auth::user();
        $academy = $user->academy;

        // This would fetch actual certificates from your system
        $certificates = collect([
            [
                'id' => 1,
                'title' => 'شهادة إتمام دورة القرآن الكريم',
                'course' => 'دائرة الحفظ المتقدم',
                'teacher' => 'الأستاذ أحمد محمد',
                'date' => now()->subDays(30),
                'status' => 'issued',
            ],
            [
                'id' => 2,
                'title' => 'شهادة إتمام كورس الرياضيات',
                'course' => 'الرياضيات للصف الثالث',
                'teacher' => 'الأستاذة ليلى محمد',
                'date' => now()->subDays(15),
                'status' => 'issued',
            ],
        ]);

        return view('student.certificates', compact('certificates'));
    }

    /**
     * Download certificate for completed course
     */
    public function downloadCertificate(Request $request, $enrollmentId): JsonResponse
    {
        $user = Auth::user();

        // Find the enrollment
        $enrollment = CourseSubscription::where('id', $enrollmentId)
            ->where('student_id', $user->id)
            ->where('status', 'completed')
            ->with(['course', 'student'])
            ->first();

        if (! $enrollment) {
            abort(404, 'Certificate not found or course not completed');
        }

        // Generate certificate (placeholder for now)
        // In a real implementation, you would generate a PDF certificate
        return $this->success(
            ['enrollment' => $enrollment->id],
            'Certificate download functionality will be implemented soon'
        );
    }
}
