<?php

namespace App\Http\Middleware;

use App\Constants\DefaultAcademy;
use Filament\Http\Middleware\Authenticate as FilamentBaseAuthenticate;

class FilamentAuthenticate extends FilamentBaseAuthenticate
{
    /**
     * Redirect unauthenticated users to the frontend login page
     * instead of Filament's built-in login page.
     */
    protected function redirectTo($request): ?string
    {
        // Extract subdomain from request host
        $subdomain = null;
        $host = $request->getHost();
        $baseDomain = config('app.domain', 'itqan-platform.test');

        if (str_contains($host, $baseDomain)) {
            $sub = str_replace('.' . $baseDomain, '', $host);
            if ($sub && $sub !== $baseDomain) {
                $subdomain = $sub;
            }
        }

        $subdomain = $subdomain ?: DefaultAcademy::subdomain();

        $intendedUrl = $request->url();

        return route('login', ['subdomain' => $subdomain, 'redirect' => $intendedUrl]);
    }
}
