<?php

namespace App\Helpers;

use App\Models\Academy;
use Illuminate\Support\Facades\Storage;

/**
 * Favicon Helper
 *
 * Provides centralized favicon management across the entire application.
 * Usage: FaviconHelper::get() or getFavicon() helper function
 */
class FaviconHelper
{
    /**
     * Get the favicon URL for the current academy context
     *
     * @param Academy|null $academy Academy instance (auto-resolves if null)
     * @return string Favicon URL (academy favicon or default)
     */
    public static function get(?Academy $academy = null): string
    {
        // Auto-resolve academy if not provided
        if (!$academy) {
            $academy = static::resolveAcademy();
        }

        // Return academy favicon if exists
        if ($academy?->favicon) {
            return Storage::url($academy->favicon);
        }

        // Fallback to default favicon
        return asset('favicon.ico');
    }

    /**
     * Get favicon HTML link tag
     *
     * @param Academy|null $academy Academy instance (auto-resolves if null)
     * @return string HTML link tag for favicon
     */
    public static function linkTag(?Academy $academy = null): string
    {
        $url = static::get($academy);
        $type = str_ends_with($url, '.svg') ? 'image/svg+xml' : 'image/png';

        return sprintf(
            '<link rel="icon" type="%s" href="%s">',
            htmlspecialchars($type, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Check if academy has a custom favicon
     *
     * @param Academy|null $academy Academy instance (auto-resolves if null)
     * @return bool True if academy has custom favicon
     */
    public static function hasCustom(?Academy $academy = null): bool
    {
        if (!$academy) {
            $academy = static::resolveAcademy();
        }

        return !empty($academy?->favicon);
    }

    /**
     * Get the raw storage path (without URL conversion)
     *
     * @param Academy|null $academy Academy instance (auto-resolves if null)
     * @return string|null Storage path or null
     */
    public static function getStoragePath(?Academy $academy = null): ?string
    {
        if (!$academy) {
            $academy = static::resolveAcademy();
        }

        return $academy?->favicon;
    }

    /**
     * Resolve academy from current context
     * Priority: authenticated user > first academy
     *
     * @return Academy|null
     */
    protected static function resolveAcademy(): ?Academy
    {
        // Try authenticated user's academy
        if (auth()->check() && auth()->user()->academy) {
            return auth()->user()->academy;
        }

        // Try tenant from Filament context
        if (function_exists('filament') && filament()->getTenant()) {
            return filament()->getTenant();
        }

        // Fallback to first academy
        return Academy::first();
    }
}
