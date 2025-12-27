<?php

namespace App\Http\Controllers;

use App\Http\Middleware\ChildSelectionMiddleware;
use App\Models\Payment;
use App\Services\ParentDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Enums\SessionStatus;

/**
 * Parent Payment Controller
 *
 * Handles viewing of child payment history and receipts.
 * Uses session-based child selection via middleware.
 * Returns student view with parent layout for consistent design.
 */
class ParentPaymentController extends Controller
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
     * Payment history - supports filtering by child via session-based selection
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
        if ($request->has('date_from') && !empty($request->date_from)) {
            $paymentsQuery->whereDate('payment_date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && !empty($request->date_to)) {
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
                ->where('status', SessionStatus::COMPLETED->value)
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
     *
     * @param Request $request
     * @param int $paymentId
     * @return \Illuminate\View\View
     */
    public function show(Request $request, int $paymentId)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $parent->students()->with('user')->get();
        $childUserIds = $children->pluck('user_id')->toArray();

        $payment = Payment::with('user')->findOrFail($paymentId);

        // Verify payment belongs to one of parent's children
        if (!in_array($payment->user_id, $childUserIds)) {
            abort(403, 'لا يمكنك الوصول إلى هذا الدفع');
        }

        return view('parent.payments.show', [
            'parent' => $parent,
            'children' => $children,
            'payment' => $payment,
        ]);
    }

    /**
     * Download receipt PDF
     *
     * @param Request $request
     * @param int $paymentId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadReceipt(Request $request, int $paymentId)
    {
        $user = Auth::user();
        $parent = $user->parentProfile;
        $children = $parent->students()->with('user')->get();
        $childUserIds = $children->pluck('user_id')->toArray();

        $payment = Payment::findOrFail($paymentId);

        // Verify payment belongs to one of parent's children
        if (!in_array($payment->user_id, $childUserIds)) {
            abort(403, 'لا يمكنك الوصول إلى هذا الدفع');
        }

        // Check if receipt exists
        if (!$payment->receipt_url) {
            abort(404, 'إيصال الدفع غير متوفر');
        }

        // Download receipt
        return response()->download(
            storage_path('app/' . $payment->receipt_url),
            'receipt-' . $payment->payment_code . '.pdf'
        );
    }

    /**
     * Helper: Get user IDs for children based on filter
     */
    protected function getChildUserIds($children, $selectedChildId): array
    {
        if ($selectedChildId === 'all') {
            return $children->pluck('user_id')->toArray();
        }

        // Find the specific child
        $child = $children->firstWhere('id', $selectedChildId);
        if ($child) {
            return [$child->user_id];
        }

        // Fallback to all children if invalid selection
        return $children->pluck('user_id')->toArray();
    }
}
