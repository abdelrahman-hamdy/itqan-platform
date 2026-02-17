<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect('/admin/login');
        }

        $user = auth()->user();

        if (! $user->isSuperAdmin()) {
            abort(403, 'Access denied. SuperAdmin privileges required.');
        }

        return $next($request);
    }
}
