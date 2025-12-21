<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * URIs that should be accessible even during maintenance mode
     */
    protected $except = [
        'admin/*',
        'filament/*',
        'livewire/*',
        'login',
        'logout',
        'api/webhooks/*',
        'webhooks/*',
        'storage/*',
        'css/*',
        'js/*',
        'images/*',
        'favicon.ico',
        'robots.txt',
        'build/*',
        '@vite/*',
        'maintenance',
        'supervisor-panel/*',
        'supervisor-panel',
        'teacher-panel/*',
        'teacher-panel',
        'academic-teacher-panel/*',
        'academic-teacher-panel',
        'panel/*',
        'panel',
    ];

    /**
     * User types that can bypass maintenance mode
     */
    protected $bypassUserTypes = [
        'super_admin',
        'admin',
        'supervisor',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the current academy from request or app container
        $academy = $request->get('academy');

        // If not in request, try to get from app container
        if (!$academy && app()->has('current_academy')) {
            $academy = app('current_academy');
        }

        // If no academy is found (main domain), proceed normally
        if (!$academy) {
            return $next($request);
        }

        // Check if academy is in maintenance mode
        if ($academy->maintenance_mode) {
            // Check if current user can bypass maintenance mode
            if ($this->canBypassMaintenance($request)) {
                return $next($request);
            }

            // Check if current path is excluded from maintenance mode
            if ($this->inExceptArray($request)) {
                return $next($request);
            }

            // Check if request is for maintenance page itself to prevent redirect loop
            if ($request->is('maintenance') || $request->routeIs('maintenance')) {
                return $next($request);
            }

            // If AJAX request, return JSON response
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'message' => __('messages.maintenance_mode'),
                    'status' => 'maintenance'
                ], 503);
            }

            // Redirect to maintenance page
            return response()->view('errors.maintenance', [
                'academy' => $academy,
                'message' => $academy->academic_settings['maintenance_message'] ?? null
            ], 503);
        }

        return $next($request);
    }

    /**
     * Determine if the user can bypass maintenance mode
     */
    protected function canBypassMaintenance(Request $request): bool
    {
        $user = $request->user();

        if (!$user) {
            return false;
        }

        // Check if user is super admin or has bypass user type
        if (in_array($user->user_type, $this->bypassUserTypes)) {
            return true;
        }

        // Check if user is the academy admin
        $academy = $request->get('academy');

        // If not in request, try to get from app container
        if (!$academy && app()->has('current_academy')) {
            $academy = app('current_academy');
        }

        if ($academy && $academy->admin_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine if the request has a URI that should be accessible in maintenance mode.
     */
    protected function inExceptArray(Request $request): bool
    {
        foreach ($this->except as $except) {
            if ($except === '/') {
                $except = '';
            }

            // Handle wildcard exceptions
            if (str_contains($except, '*')) {
                // Escape special regex chars except *, then replace * with .*
                $pattern = preg_quote($except, '/');
                $pattern = str_replace('\*', '.*', $pattern);
                if (preg_match('/^' . $pattern . '$/u', $request->path())) {
                    return true;
                }
            } elseif ($request->is($except)) {
                return true;
            }
        }

        return false;
    }
}