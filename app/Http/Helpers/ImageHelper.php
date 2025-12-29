<?php

namespace App\Http\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Image Helper for Mobile API
 *
 * Provides standardized image URL structures with size variants
 * for optimal mobile app performance and bandwidth usage.
 */
class ImageHelper
{
    /**
     * Standard image sizes for mobile apps
     */
    public const SIZES = [
        'thumb' => 100,
        'small' => 200,
        'medium' => 400,
        'large' => 800,
    ];

    /**
     * Get image URLs with size variants
     *
     * Returns structured URL object for mobile apps to choose
     * the appropriate size based on display context.
     *
     * @param string|null $path Original image path
     * @param string $disk Storage disk name
     * @return array|null Image URL variants or null if no image
     */
    public static function getImageUrls(?string $path, string $disk = 'public'): ?array
    {
        if (empty($path)) {
            return null;
        }

        $baseUrl = self::getBaseUrl($path, $disk);

        if (!$baseUrl) {
            return null;
        }

        // Check if size variants exist
        $pathInfo = pathinfo($path);
        $baseName = $pathInfo['dirname'] . '/' . $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? 'jpg';

        $variants = [];
        foreach (self::SIZES as $size => $pixels) {
            $variantPath = "{$baseName}_{$size}.{$extension}";
            if (Storage::disk($disk)->exists($variantPath)) {
                $variants[$size] = Storage::disk($disk)->url($variantPath);
            }
        }

        // If no variants exist, return original only
        if (empty($variants)) {
            return [
                'original' => $baseUrl,
                'thumb' => $baseUrl,
                'small' => $baseUrl,
                'medium' => $baseUrl,
                'large' => $baseUrl,
            ];
        }

        // Fill in missing variants with closest available or original
        $variants['original'] = $baseUrl;

        return $variants;
    }

    /**
     * Get avatar URLs with standard variants
     *
     * Convenience method for user avatars with default fallback.
     *
     * @param string|null $path Avatar path
     * @param string|null $name User name for initials fallback
     * @return array Avatar URL variants
     */
    public static function getAvatarUrls(?string $path, ?string $name = null): array
    {
        $urls = self::getImageUrls($path);

        if ($urls) {
            return $urls;
        }

        // Generate UI Avatars fallback with different sizes
        $initials = self::getInitials($name ?? 'U');
        $baseUrl = 'https://ui-avatars.com/api/';

        return [
            'original' => "{$baseUrl}?name={$initials}&size=400&background=random",
            'thumb' => "{$baseUrl}?name={$initials}&size=100&background=random",
            'small' => "{$baseUrl}?name={$initials}&size=200&background=random",
            'medium' => "{$baseUrl}?name={$initials}&size=400&background=random",
            'large' => "{$baseUrl}?name={$initials}&size=800&background=random",
        ];
    }

    /**
     * Get a single image URL (backward compatible)
     *
     * @param string|null $path Image path
     * @param string $disk Storage disk
     * @return string|null Image URL or null
     */
    public static function getSingleUrl(?string $path, string $disk = 'public'): ?string
    {
        return self::getBaseUrl($path, $disk);
    }

    /**
     * Get base URL for an image path
     */
    private static function getBaseUrl(?string $path, string $disk): ?string
    {
        if (empty($path)) {
            return null;
        }

        // If already a full URL, return as-is
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // Check if file exists
        if (!Storage::disk($disk)->exists($path)) {
            return null;
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Get initials from a name
     */
    private static function getInitials(string $name): string
    {
        $words = explode(' ', trim($name));
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            if (!empty($word)) {
                $initials .= mb_substr($word, 0, 1);
            }
        }

        return urlencode($initials ?: 'U');
    }
}
