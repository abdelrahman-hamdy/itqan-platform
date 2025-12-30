<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales
     */
    protected array $supportedLocales = ['ar', 'en'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority order:
        // 1. Query parameter (for switching)
        // 2. Session storage
        // 3. User preference (if authenticated)
        // 4. Browser preference
        // 5. Default locale

        $locale = $this->determineLocale($request);

        App::setLocale($locale);
        Session::put('locale', $locale);

        return $next($request);
    }

    /**
     * Determine the locale to use
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check query parameter (allows explicit switching)
        if ($request->has('lang') && $this->isSupported($request->get('lang'))) {
            return $request->get('lang');
        }

        // 2. Check session
        if (Session::has('locale') && $this->isSupported(Session::get('locale'))) {
            return Session::get('locale');
        }

        // 3. Check authenticated user preference
        if ($request->user() && $request->user()->preferred_locale && $this->isSupported($request->user()->preferred_locale)) {
            return $request->user()->preferred_locale;
        }

        // 4. Check browser preference
        $browserLocale = $request->getPreferredLanguage($this->supportedLocales);
        if ($browserLocale && $this->isSupported($browserLocale)) {
            return $browserLocale;
        }

        // 5. Fall back to default
        return config('app.locale', 'ar');
    }

    /**
     * Check if locale is supported
     */
    protected function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales);
    }
}
