<?php

namespace App\Http\Controllers\Supervisor;

use App\Services\Subscription\PresetSessionsReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorPresetSessionsReviewController extends BaseSupervisorWebController
{
    public function index(Request $request, string $subdomain, PresetSessionsReviewService $service): View
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        return view('supervisor.preset-sessions-review.index', [
            'subdomain' => $subdomain,
            'subs' => $service->atRiskSubs(),
        ]);
    }

    public function record(
        Request $request,
        string $subdomain,
        int $sub,
        PresetSessionsReviewService $service,
    ): RedirectResponse {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $validated = $request->validate([
            'preserved_value' => 'required|integer|min:0|max:9999',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $result = $service->recordDecision(
                subId: $sub,
                preservedValue: (int) $validated['preserved_value'],
                supervisorUserId: (int) $request->user()->id,
                note: $validated['note'] ?? null,
            );

            return redirect()
                ->route('manage.preset-sessions-review.index', ['subdomain' => $subdomain])
                ->with('success', sprintf(
                    'Sub #%d updated: used=%d remaining=%d',
                    $result['sub_id'],
                    $result['sessions_used'],
                    $result['sessions_remaining'],
                ));
        } catch (\Throwable $e) {
            return redirect()
                ->route('manage.preset-sessions-review.index', ['subdomain' => $subdomain])
                ->with('error', sprintf('Sub #%d failed: %s', $sub, $e->getMessage()));
        }
    }
}
