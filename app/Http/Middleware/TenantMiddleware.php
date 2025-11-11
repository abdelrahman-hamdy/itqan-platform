<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Facades\Filament;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip tenant resolution for Super-Admin routes
        if ($request->is('admin') || $request->is('admin/*')) {
            return $next($request);
        }
        
        $host = $request->getHost();
        $baseDomain = config('app.domain', 'itqan-platform.test');
        
        // Extract subdomain if it exists
        if (str_contains($host, $baseDomain)) {
            $subdomain = str_replace('.' . $baseDomain, '', $host);
            
            // If we have a subdomain and it's not the base domain
            if ($subdomain && $subdomain !== $baseDomain) {
                // Find academy by subdomain
                $academy = Academy::where('subdomain', $subdomain)->first();
                
                if (!$academy) {
                    abort(404, 'Academy not found');
                }
                
                if (!$academy->is_active) {
                    abort(503, 'Academy is currently unavailable');
                }
                
                if ($academy->maintenance_mode) {
                    abort(503, 'Academy is currently under maintenance');
                }
                
                // Set the tenant in Filament context only for tenant-aware panels
                if ($request->is('panel') || $request->is('panel/*') || 
                    $request->is('teacher-panel') || $request->is('teacher-panel/*') ||
                    $request->is('supervisor-panel') || $request->is('supervisor-panel/*')) {
                    Filament::setTenant($academy);
                }
                
                // Store in app container for easy access
                app()->instance('current_academy', $academy);
            } else {
                // This is the main domain - check if there's a default academy
                $defaultAcademy = Academy::where('subdomain', 'itqan-academy')->first();
                
                if ($defaultAcademy) {
                    // Only set tenant for tenant-aware panels, not for Super-Admin
                    if ($request->is('panel') || $request->is('panel/*') || 
                        $request->is('teacher-panel') || $request->is('teacher-panel/*') ||
                        $request->is('supervisor-panel') || $request->is('supervisor-panel/*')) {
                        Filament::setTenant($defaultAcademy);
                    }
                    app()->instance('current_academy', $defaultAcademy);
                }
            }
        }
        
        return $next($request);
    }
}
