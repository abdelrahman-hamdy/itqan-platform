<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Platform Settings Model (Singleton)
 *
 * Stores platform-wide settings for identity and contact information
 */
class PlatformSettings extends Model
{
    protected $fillable = [
        'logo',
        'favicon',
        'email',
        'phone',
        'address',
        'working_hours',
        'social_links',
    ];

    protected $casts = [
        'social_links' => 'array',
    ];

    /**
     * Get the singleton instance of platform settings
     */
    public static function instance(): self
    {
        return Cache::remember('platform_settings', config('business.cache.academy_context_ttl', 3600), function () {
            return static::firstOrCreate([], [
                'social_links' => [],
            ]);
        });
    }

    /**
     * Clear the cached settings
     */
    public static function clearCache(): void
    {
        Cache::forget('platform_settings');
    }

    /**
     * Override save to clear cache
     */
    public function save(array $options = [])
    {
        $result = parent::save($options);
        static::clearCache();

        return $result;
    }

    /**
     * Override update to clear cache
     */
    public function update(array $attributes = [], array $options = [])
    {
        $result = parent::update($attributes, $options);
        static::clearCache();

        return $result;
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/'.$this->logo) : null;
    }

    /**
     * Get favicon URL
     */
    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon ? asset('storage/'.$this->favicon) : null;
    }

    /**
     * Get social links as collection
     */
    public function getSocialLinksCollection(): \Illuminate\Support\Collection
    {
        return collect($this->social_links ?? []);
    }
}
