<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromSubdomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $baseDomain = config('app.domain', 'itqan-platform.test');
        
        // Extract subdomain if it exists
        if (str_contains($host, $baseDomain)) {
            $subdomain = str_replace('.' . $baseDomain, '', $host);
            
            // If we have a subdomain and it's not the base domain
            if ($subdomain !== $baseDomain && !empty($subdomain)) {
                // Find the academy
                $academy = Academy::where('subdomain', $subdomain)->first();
                
                if ($academy) {
                    // Set the current tenant in the request
                    $request->merge(['current_academy' => $academy]);
                    
                    // You can also set it globally if needed
                    app()->instance('current_academy', $academy);
                }
            }
        }
        
        return $next($request);
    }
}
