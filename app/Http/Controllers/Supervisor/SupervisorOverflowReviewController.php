<?php

namespace App\Http\Controllers\Supervisor;

use App\Services\Subscription\OverflowCyclesReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorOverflowReviewController extends BaseSupervisorWebController
{
    public function index(Request $request, string $subdomain, OverflowCyclesReviewService $service): View
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        return view('supervisor.overflow-cycles-review.index', [
            'subdomain' => $subdomain,
            'cycles' => $service->overflowCycles(),
        ]);
    }

    public function record(
        Request $request,
        string $subdomain,
        int $cycle,
        OverflowCyclesReviewService $service,
    ): RedirectResponse {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:bump_total,forgive_n,defer',
            'forgive_count' => 'nullable|integer|min:1|max:999',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $result = $service->recordDecision(
                cycleId: $cycle,
                action: $validated['action'],
                forgiveCount: $validated['forgive_count'] ?? null,
                supervisorUserId: (int) $request->user()->id,
                note: $validated['note'] ?? null,
            );

            return redirect()
                ->route('manage.overflow-cycles-review.index', ['subdomain' => $subdomain])
                ->with('success', sprintf(
                    'Cycle #%d: action=%s, drift_after=%d, total_after=%d',
                    $result['cycle_id'],
                    $result['action'],
                    $result['drift_after'],
                    $result['total_after'],
                ));
        } catch (\Throwable $e) {
            return redirect()
                ->route('manage.overflow-cycles-review.index', ['subdomain' => $subdomain])
                ->with('error', sprintf('Cycle #%d failed: %s', $cycle, $e->getMessage()));
        }
    }
}
