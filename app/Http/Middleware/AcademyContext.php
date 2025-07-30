<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Academy;
use App\Services\AcademyContextService;

class AcademyContext
{
    public function handle(Request $request, Closure $next)
    {
        // Only apply to super admin panel
        if (!str_contains($request->path(), 'admin')) {
            return $next($request);
        }

        // Check if user is super admin
        if (!auth()->check() || !AcademyContextService::isSuperAdmin()) {
            return $next($request);
        }

        // Handle academy parameter from URL (for academy switching)
        $academyId = $request->query('academy');
        
        if ($academyId) {
            // Set academy context using the service
            AcademyContextService::setAcademyContext($academyId);
            
            // Redirect to clean URL without academy parameter
            return redirect($request->url());
        }

        // Ensure current academy context is set in app container for this request
        $currentAcademy = AcademyContextService::getCurrentAcademy();
        if ($currentAcademy) {
            app()->instance('current_academy', $currentAcademy);
        }

        return $next($request);
    }
} 