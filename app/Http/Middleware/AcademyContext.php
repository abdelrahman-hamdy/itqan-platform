<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use App\Services\AcademyContextService;
use Closure;
use Illuminate\Http\Request;

class AcademyContext
{
    public function handle(Request $request, Closure $next)
    {
        // Only apply to super admin panel
        if (! str_contains($request->path(), 'admin')) {
            return $next($request);
        }

        // Check if user is super admin
        if (! auth()->check() || ! AcademyContextService::isSuperAdmin()) {
            return $next($request);
        }

        // Handle academy parameter from URL (for academy switching)
        $academyId = $request->query('academy');

        if ($academyId) {
            // Validate that the academy exists before setting context
            // This prevents an attacker from setting arbitrary academy IDs in session
            $academy = Academy::find($academyId);

            if ($academy) {
                // Set academy context using the service
                AcademyContextService::setAcademyContext($academyId);
            }

            // Redirect to clean URL without academy parameter (regardless of validity)
            return redirect($request->url());
        }

        // Auto-initialize academy context for Super Admin if not set and not in global view
        if (! AcademyContextService::hasAcademySelected() && ! AcademyContextService::isGlobalViewMode()) {
            AcademyContextService::initializeSuperAdminContext();
        }

        // Ensure current academy context is set in app container for this request
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        if ($currentAcademy) {
            app()->instance('current_academy', $currentAcademy);
        }

        return $next($request);
    }
}
