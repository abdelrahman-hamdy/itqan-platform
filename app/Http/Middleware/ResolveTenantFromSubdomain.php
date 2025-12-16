<?php

namespace App\Http\Middleware;

use App\Models\Academy;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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
        // COMPLETELY skip tenant resolution for any Livewire-related request
        $path = $request->path();
        $uri = $request->getRequestUri();
        $method = $request->method();

        // AGGRESSIVE DEBUG: Log academic subscription requests
        if (str_contains($path, 'academic-packages') && str_contains($path, 'subscribe')) {
            \Log::error('ACADEMIC SUBSCRIPTION REQUEST DETECTED IN MIDDLEWARE', [
                'path' => $path,
                'uri' => $uri,
                'method' => $method,
                'all_data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);
        }

        // Log for debugging
        if (str_contains($path, 'livewire') || str_contains($uri, 'livewire')) {
            \Log::info('Livewire request detected', [
                'path' => $path,
                'uri' => $uri,
                'method' => $method,
                'headers' => $request->headers->all(),
            ]);
        }

        // Even for Livewire requests, we need to set URL defaults for route generation
        // Extract subdomain from host and set URL defaults BEFORE any early returns
        $host = $request->getHost();
        $baseDomain = config('app.domain', 'itqan-platform.test');
        if (str_contains($host, $baseDomain)) {
            $earlySubdomain = str_replace('.'.$baseDomain, '', $host);
            if ($earlySubdomain !== $baseDomain && ! empty($earlySubdomain)) {
                URL::defaults(['subdomain' => $earlySubdomain]);
            }
        }

        // Comprehensive exclusions        // Early return for ANY Livewire operation
        if (str_contains($path, 'livewire') ||
            str_contains($uri, 'livewire') ||
            $request->hasHeader('X-Livewire') ||
            $request->routeIs('livewire.*') ||
            $request->is('livewire/*') ||
            $request->is('storage/*') ||
            $request->is('livewire-uploads/*') ||
            str_contains($path, 'temp') ||
            str_contains($path, 'upload') ||
            str_contains($path, 'tmp') ||
            // Additional safety checks
            $method === 'POST' && str_contains($request->getContent(), 'livewire') ||
            $request->ajax() && str_contains($uri, 'livewire')) {

            \Log::info('Skipping tenant resolution for Livewire request', [
                'path' => $path,
                'uri' => $uri,
                'method' => $method,
                'route' => $request->route() ? $request->route()->getName() : 'no-route',
            ]);

            return $next($request);
        }

        // Extract subdomain if it exists (reuse $host and $baseDomain from above)
        try {
            if (str_contains($host, $baseDomain)) {
                $subdomain = str_replace('.'.$baseDomain, '', $host);

                // If we have a subdomain and it's not the base domain
                if ($subdomain !== $baseDomain && ! empty($subdomain)) {
                    // Find the academy
                    $academy = Academy::where('subdomain', $subdomain)->first();

                    if ($academy) {
                        // Set the current tenant in the request
                        $request->merge(['academy' => $academy]);

                        // You can also set it globally if needed
                        app()->instance('current_academy', $academy);
                    }
                }
            }
        } catch (\Exception $e) {
            // Log the error but don't break the request
            \Log::warning('Tenant resolution failed: '.$e->getMessage());
        }

        return $next($request);
    }
}
