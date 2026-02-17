<?php

namespace App\Http\Middleware\Api;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set the application locale for API requests based on request headers.
 *
 * Priority order:
 * 1. X-Locale header (explicit mobile app setting)
 * 2. Accept-Language header
 * 3. Authenticated user's preferred_locale (fallback)
 * 4. Default to Arabic ('ar')
 */
class SetApiLocale
{
    /**
     * Supported locales for the API.
     */
    protected array $supportedLocales = ['ar', 'en'];

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request):Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        App::setLocale($locale);

        // Add locale to response headers for client debugging
        $response = $next($request);

        if ($response instanceof Response) {
            $response->headers->set('Content-Language', $locale);
        }

        return $response;
    }

    /**
     * Determine the locale to use for this request.
     */
    protected function determineLocale(Request $request): string
    {
        // 1. X-Locale header (explicit mobile app setting - highest priority)
        $xLocale = $request->header('X-Locale');
        if ($xLocale && $this->isSupported($xLocale)) {
            return strtolower($xLocale);
        }

        // 2. Accept-Language header
        $acceptLang = $request->getPreferredLanguage($this->supportedLocales);
        if ($acceptLang && $this->isSupported($acceptLang)) {
            return strtolower($acceptLang);
        }

        // 3. Authenticated user's preferred_locale (fallback only)
        $user = $request->user();
        if ($user && $user->preferred_locale && $this->isSupported($user->preferred_locale)) {
            return strtolower($user->preferred_locale);
        }

        // 4. Default to Arabic
        return 'ar';
    }

    /**
     * Check if the given locale is supported.
     */
    protected function isSupported(string $locale): bool
    {
        return in_array(strtolower($locale), $this->supportedLocales);
    }
}
