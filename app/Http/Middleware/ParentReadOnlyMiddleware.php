<?php

namespace App\Http\Middleware;

use App\Constants\ErrorMessages;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to restrict parent users to read-only operations.
 *
 * Parents should only be able to view data, not modify it.
 * This middleware allows GET, HEAD, and POST (for forms that don't modify data).
 *
 * Usage in controllers:
 * $this->middleware(ParentReadOnlyMiddleware::class);
 *
 * Or in routes:
 * Route::middleware('parent.readonly')->group(function () { ... });
 */
class ParentReadOnlyMiddleware
{
    /**
     * HTTP methods allowed for parent users.
     * GET, HEAD: Read operations
     * POST: Allowed for search forms, filters, etc. (non-destructive)
     */
    private const ALLOWED_METHODS = ['GET', 'HEAD', 'POST'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only restrict if user is a parent
        $user = auth()->user();

        if ($user && $user->parentProfile) {
            if (! in_array($request->method(), self::ALLOWED_METHODS)) {
                abort(403, ErrorMessages::PARENT_VIEW_ONLY);
            }
        }

        return $next($request);
    }
}
