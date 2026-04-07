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

        $query = Payment::with(['user', 'payable']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('payment_gateway')) {
            $query->where('payment_gateway', $request->payment_gateway);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        $query = match ($sort) {
            'amount_desc' => $query->orderByDesc('amount'),
            'amount_asc' => $query->orderBy('amount'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $payments = $query->paginate(15)->withQueryString();

        // Stats
        $revenueThisMonth = Payment::where('status', PaymentStatus::COMPLETED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $pendingCount = Payment::where('status', PaymentStatus::PENDING)->count();

        $completedToday = Payment::where('status', PaymentStatus::COMPLETED)
            ->whereDate('paid_at', today())
            ->count();

        $totalRevenue = Payment::where('status', PaymentStatus::COMPLETED)->sum('amount');

        // Per-gateway revenue stats
        $paymobRevenue = Payment::where('status', PaymentStatus::COMPLETED)
            ->where('payment_gateway', 'paymob')->sum('amount');
        $easykashRevenue = Payment::where('status', PaymentStatus::COMPLETED)
            ->where('payment_gateway', 'easykash')->sum('amount');
        $tapRevenue = Payment::where('status', PaymentStatus::COMPLETED)
            ->where('payment_gateway', 'tap')->sum('amount');
        $manualRevenue = Payment::where('status', PaymentStatus::COMPLETED)
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
