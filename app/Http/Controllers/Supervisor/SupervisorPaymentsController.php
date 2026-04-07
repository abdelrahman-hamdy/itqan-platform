<?php

namespace App\Http\Controllers\Supervisor;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorPaymentsController extends BaseSupervisorWebController
{
    public function index(Request $request, $subdomain = null): View
    {
        if (! $this->canManagePayments()) {
            abort(403);
        }

        // Reusable filter closure applied to all queries (list + stats)
        $applyFilters = function ($q) use ($request) {
            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }
            if ($request->filled('payment_method')) {
                $q->where('payment_method', $request->payment_method);
            }
            if ($request->filled('date_from')) {
                $q->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $q->whereDate('created_at', '<=', $request->date_to);
            }
            if ($request->filled('payment_gateway')) {
                $q->where('payment_gateway', $request->payment_gateway);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            return $q;
        };

        // Paginated list
        $query = $applyFilters(Payment::with(['user', 'payable']));

        $sort = $request->get('sort', 'newest');
        $query = match ($sort) {
            'amount_desc' => $query->orderByDesc('amount'),
            'amount_asc' => $query->orderBy('amount'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $payments = $query->paginate(15)->withQueryString();

        // Stats (filtered)
        $revenueThisMonth = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $pendingCount = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::PENDING)->count();

        $completedToday = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)
            ->whereDate('paid_at', today())
            ->count();

        $totalRevenue = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)->sum('amount');

        // Per-gateway revenue stats (filtered)
        $paymobRevenue = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)
            ->where('payment_gateway', 'paymob')->sum('amount');
        $easykashRevenue = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)
            ->where('payment_gateway', 'easykash')->sum('amount');
        $tapRevenue = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)
            ->where('payment_gateway', 'tap')->sum('amount');
        $manualRevenue = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)
            ->where('payment_gateway', 'manual')->sum('amount');

        // Distinct payment methods for filter dropdown
        $paymentMethods = Payment::whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->toArray();

        $isAdmin = $this->isAdminUser();

        return view('supervisor.payments.index', compact(
            'payments',
            'revenueThisMonth',
            'pendingCount',
            'completedToday',
            'totalRevenue',
            'paymobRevenue',
            'easykashRevenue',
            'tapRevenue',
            'manualRevenue',
            'paymentMethods',
            'isAdmin',
        ));
    }

    public function show(Request $request, $subdomain = null, $payment = null): View
    {
        if (! $this->canManagePayments()) {
            abort(403);
        }

        $payment = Payment::with(['user', 'payable'])->findOrFail($payment);

        return view('supervisor.payments.show', compact('payment'));
    }

    public function markCompleted(Request $request, $subdomain = null, $payment = null)
    {
        if (! $this->canManagePayments()) {
            abort(403);
        }

        $payment = Payment::findOrFail($payment);
        $payment->markAsCompleted();

        return redirect()->back()->with('success', __('supervisor.payments.marked_completed'));
    }
}
