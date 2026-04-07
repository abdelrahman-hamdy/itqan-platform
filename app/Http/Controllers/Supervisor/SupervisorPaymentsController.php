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

        $applyFilters = function ($q) use ($request) {
            if ($request->filled('status')) {
                $q->where('status', $request->status);
            }
            if ($request->filled('payment_method')) {
                $q->forMethod($request->payment_method);
            }
            if ($request->filled('date_from')) {
                $q->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $q->whereDate('created_at', '<=', $request->date_to);
            }
            if ($request->filled('payment_gateway')) {
                $q->forGateway($request->payment_gateway);
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

        $query = $applyFilters(Payment::with(['user', 'payable']));

        $sort = $request->get('sort', 'newest');
        $query = match ($sort) {
            'amount_desc' => $query->orderByDesc('amount'),
            'amount_asc' => $query->orderBy('amount'),
            'oldest' => $query->orderBy('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $payments = $query->paginate(15)->withQueryString();

        $pendingCount = $applyFilters(Payment::query())->pending()->count();

        $completedToday = $applyFilters(Payment::query())
            ->where('status', PaymentStatus::COMPLETED)
            ->whereDate('paid_at', today())
            ->count();

        $isAdmin = $this->isAdminUser();

        $revenueThisMonth = 0;
        $totalRevenue = 0;
        $gatewayRevenues = collect();

        if ($isAdmin) {
            $revenueThisMonth = $applyFilters(Payment::query())
                ->where('status', PaymentStatus::COMPLETED)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');

            // Single GROUP BY query for total + per-gateway revenue
            $gatewayRevenues = $applyFilters(Payment::query())
                ->where('status', PaymentStatus::COMPLETED)
                ->groupBy('payment_gateway')
                ->selectRaw('payment_gateway, SUM(amount) as total')
                ->pluck('total', 'payment_gateway');

            $totalRevenue = $gatewayRevenues->sum();
        }

        $paymentMethods = Payment::whereNotNull('payment_method')
            ->distinct()
            ->pluck('payment_method')
            ->toArray();

        return view('supervisor.payments.index', compact(
            'payments',
            'revenueThisMonth',
            'pendingCount',
            'completedToday',
            'totalRevenue',
            'gatewayRevenues',
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
