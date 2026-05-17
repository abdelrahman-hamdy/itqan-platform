<?php

namespace App\Http\Controllers\Supervisor;

use App\Services\Subscription\ResidueReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Supervisor surface for the residue (NEEDS_REVIEW + BACKUP_SHOWS_DIFFERENT)
 * verdicts emitted by `subscriptions:classify-residue-drift` (A.2 of the
 * 2026-05-17 final cleanup plan).
 *
 * Index: read-only listing pulled from the classifier CSV via
 *        {@see ResidueReviewService::entries()}.
 *
 * Record: apply the supervisor's per-session decision
 *        (force_count / force_uncount / defer) via
 *        {@see ResidueReviewService::recordDecision()}.
 */
class SupervisorResidueReviewController extends BaseSupervisorWebController
{
    public function index(Request $request, string $subdomain, ResidueReviewService $service): View
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        return view('supervisor.residue-review.index', [
            'subdomain' => $subdomain,
            'entries' => $service->entries(),
        ]);
    }

    public function record(
        Request $request,
        string $subdomain,
        int $session,
        ResidueReviewService $service,
    ): RedirectResponse {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $validated = $request->validate([
            'action' => 'required|in:force_count,force_uncount,defer',
            'note' => 'nullable|string|max:500',
        ]);

        try {
            $result = $service->recordDecision(
                sessionId: $session,
                action: $validated['action'],
                supervisorUserId: (int) $request->user()->id,
                note: $validated['note'] ?? null,
            );

            return redirect()
                ->route('manage.residue-review.index', ['subdomain' => $subdomain])
                ->with('success', sprintf(
                    'Session #%d: action=%s sub=%s cycle=%s',
                    $result['session_id'],
                    $result['action'],
                    $result['sub_id'] ?? '—',
                    $result['cycle_id'] ?? '—',
                ));
        } catch (\Throwable $e) {
            return redirect()
                ->route('manage.residue-review.index', ['subdomain' => $subdomain])
                ->with('error', sprintf('Session #%d failed: %s', $session, $e->getMessage()));
        }
    }
}
