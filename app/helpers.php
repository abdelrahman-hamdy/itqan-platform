<?php

if (!function_exists('current_academy')) {
    /**
     * Get the current academy from the resolved tenant
     */
    function current_academy(): ?\App\Models\Academy
    {
        return app()->bound('current_academy') ? app('current_academy') : null;
    }
}

if (!function_exists('academy_url')) {
    /**
     * Generate URL for an academy
     */
    function academy_url(\App\Models\Academy $academy, string $path = '/'): string
    {
        $protocol = app()->environment('local') ? 'http' : 'https';
        return $protocol . '://' . $academy->full_domain . $path;
    }
} 