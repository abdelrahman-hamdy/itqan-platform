<?php

namespace App\Http\Controllers\Supervisor;

use App\Services\Subscription\LegacyCountingAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupervisorLegacyCountingAuditController extends BaseSupervisorWebController
{
    public function index(Request $request, string $subdomain, LegacyCountingAuditService $service): View
    {
        if (! $this->canManageSubscriptions()) {
            abort(403);
        }

        $report = $service->buildReport();

        return view('supervisor.legacy-counting-audit.index', [
            'subdomain' => $subdomain,
            'summary' => $report['summary'],
            'totals' => $report['totals'],
            'driftLegacyNotConsumption' => $report['drift_legacy_not_consumption'],
            'driftConsumptionNotLegacy' => $report['drift_consumption_not_legacy'],
            'attendanceDrift' => $report['attendance_drift'],
            'cyclesPendingV2Migration' => $report['cycles_pending_v2_migration'],
            'stuckWithEarnings' => $report['stuck_with_earnings'],
        ]);
    }
}
