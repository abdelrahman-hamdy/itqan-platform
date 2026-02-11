<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Traits\HasParentChildren;
use App\Http\Middleware\ChildSelectionMiddleware;
use App\Models\Payment;
use App\Services\ParentChildVerificationService;
use App\Services\ParentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Parent Payment Controller
 *
 * Handles viewing of child payment history and receipts.
 * Uses session-based child selection via middleware.
 * Returns student view with parent layout for consistent design.
 */
class ParentPaymentController extends Controller
{
    use HasParentChildren;

    public function __construct(
        protected ParentDataService $dataService,
        protected ParentChildVerificationService $verificationService
    ) {
        // Enforce read-only access
        $this->middleware('parent.readonly');
    }

    /**
     * Payment history - supports filtering by child via session-based selection
     *
     * Uses the student view with parent layout for consistent design.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payment::class);

        $user = Auth::user();
        $parent = $user->parentProfile;

        // Get child IDs from middleware (session-based selection)
        $childUserIds = ChildSelectionMiddleware::getChildIds();

        // Build payments query with filters
        $paymentsQuery = Payment::whereIn('user_id', $childUserIds)
            ->where('academy_id', $parent->academy_id)
            ->with(['subscription', 'user'])
            ->orderBy('payment_date', 'desc');

        // Apply status filter
        if ($request->has('status') && $request->status !== 'all') {
            $paymentsQuery->where('status', $request->status);
        }

        // Apply date filters
        if ($request->has('date_from') && ! empty($request->date_from)) {
            $paymentsQuery->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && ! empty($request->date_to)) {
            $paymentsQuery->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $paymentsQuery->paginate(15)->appends($request->query());

        // Calculate statistics (matching student controller format)
        $stats = [
            'total_payments' => Payment::whereIn('user_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->count(),
            'successful_payments' => Payment::whereIn('user_id', $childUserIds)
                ->where('academy_id', $parent->academy_id)
                ->where('status', PaymentStatus::COMPLETED->value)
                ->count(),
        ];

        // Return student view with parent layout
        return view('student.payments', [
            'payments' => $payments,
            'stats' => $stats,
            'layout' => 'parent',
        ]);
    }

    /**
     * Payment details
     */
    public function show(Request $request, int $paymentId): View
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $this->verificationService->getChildrenWithUsers($parent);

        $payment = Payment::with('user')->findOrFail($paymentId);

        $this->authorize('view', $payment);

        // Verify payment belongs to one of parent's children
        $this->verificationService->verifyPaymentBelongsToParent($parent, $payment);

        return view('parent.payments.show', [
            'parent' => $parent,
            'children' => $children,
            'payment' => $payment,
        ]);
    }

    /**
     * Download receipt PDF
     */
    public function downloadReceipt(Request $request, int $paymentId): StreamedResponse
    {
        $user = Auth::user();
        $parent = $user->parentProfile;

        $payment = Payment::findOrFail($paymentId);

        $this->authorize('downloadReceipt', $payment);

        // Verify payment belongs to one of parent's children
        $this->verificationService->verifyPaymentBelongsToParent($parent, $payment);

        // Check if receipt exists
        if (! $payment->receipt_url) {
            abort(404, 'إيصال الدفع غير متوفر');
        }

        // Download receipt
        return response()->download(
            storage_path('app/'.$payment->receipt_url),
            'receipt-'.$payment->payment_code.'.pdf'
        );
    }
}
