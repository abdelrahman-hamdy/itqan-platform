<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class CustomAuthenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Try to get subdomain from route or host
        $subdomain = $request->route('subdomain');
        if (!$subdomain) {
            $host = $request->getHost();
            $baseDomain = config('app.domain', 'itqan-platform.test');
            if (str_contains($host, $baseDomain)) {
                $sub = str_replace('.' . $baseDomain, '', $host);
                if ($sub && $sub !== $baseDomain) {
                    $subdomain = $sub;
                }
            }
        }
        
        // Default to itqan-academy if no subdomain found
        $subdomain = $subdomain ?: 'itqan-academy';

        return route('login', ['subdomain' => $subdomain]);
    }
}