<?php

namespace App\Http\Controllers\Supervisor;

use App\Models\SubscriptionAdminAuditDecision;
use App\Services\Subscription\AdminAuditCaseService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Temporary subscription admin audit page — collects human decisions on
 * cases that can't be auto-fixed (INV-D2 ambiguous, free-not-override,
 * orphan packages, corrupted pause states). Live case list rebuilt on each
 * load; decisions persisted to subscription_admin_audit_decisions.
 *
 * Permission gate: admin/super_admin OR supervisor with
 * `can_manage_subscriptions = true`. Mirrors the existing
 * SupervisorSubscriptionsController gate.
 */
class SupervisorAdminAuditController extends BaseSupervisorWebController
{
    public function index(Request $request, AdminAuditCaseService $service, $subdomain = null): View
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $cases = $service->buildAllCases();

        $totals = collect($cases)->map(fn ($bucket) => count($bucket))->all();
        $decided = collect($cases)
            ->map(fn ($bucket) => collect($bucket)->filter(fn ($c) => $c['decision'] !== null)->count())
            ->all();

        return view('supervisor.admin-audit.index', [
            'cases' => $cases,
            'totals' => $totals,
            'decided' => $decided,
        ]);
    }

    public function decide(Request $request, $subdomain = null): RedirectResponse
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $data = $request->validate([
            'case_key' => 'required|string|max:191',
            'case_type' => 'required|string|max:64',
            'subject_type' => 'nullable|string|max:64',
            'subject_id' => 'nullable|integer',
            'selected_option' => 'nullable|string|max:128',
            'free_text' => 'nullable|string|max:5000',
        ]);

        SubscriptionAdminAuditDecision::updateOrCreate(
            ['case_key' => $data['case_key']],
            [
                'case_type' => $data['case_type'],
                'subject_type' => $data['subject_type'] ?? null,
                'subject_id' => $data['subject_id'] ?? null,
                'selected_option' => $data['selected_option'] ?? null,
                'free_text' => $data['free_text'] ?? null,
                'decided_by_user_id' => $request->user()?->id,
                'decided_at' => now(),
            ],
        );

        return redirect()
            ->route('manage.admin-audit.index', ['subdomain' => $subdomain])
            ->with('success', 'تم حفظ القرار')
            ->withFragment($data['case_key']);
    }
}
