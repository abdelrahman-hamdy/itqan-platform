@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    
    // Use academy color if available, otherwise use Filament's default blue
    $primaryColorHex = $currentAcademy?->brand_color ?? '#3B82F6';
    
    // Convert hex to RGB for Filament CSS variables
    $hex = ltrim($primaryColorHex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $primaryColorRgb = "$r $g $b";
    
    // Calculate proper color shades using better algorithm
    function calculateShade($r, $g, $b, $factor) {
        if ($factor > 0) {
            // Lighter shades
            $r = min(255, $r + (255 - $r) * ($factor / 10));
            $g = min(255, $g + (255 - $g) * ($factor / 10));
            $b = min(255, $b + (255 - $b) * ($factor / 10));
        } else {
            // Darker shades
            $factor = abs($factor);
            $r = max(0, $r * (1 - $factor / 10));
            $g = max(0, $g * (1 - $factor / 10));
            $b = max(0, $b * (1 - $factor / 10));
        }
        return round($r) . ' ' . round($g) . ' ' . round($b);
    }
@endphp

{{-- Academy Branding System - Proper Filament Color Integration --}}
<style>
    :root {
        /* Core Filament primary color CSS custom properties */
        --primary-50: {{ calculateShade($r, $g, $b, 9) }};
        --primary-100: {{ calculateShade($r, $g, $b, 8) }};
        --primary-200: {{ calculateShade($r, $g, $b, 6) }};
        --primary-300: {{ calculateShade($r, $g, $b, 4) }};
        --primary-400: {{ calculateShade($r, $g, $b, 2) }};
        --primary-500: {{ $primaryColorRgb }};
        --primary-600: {{ calculateShade($r, $g, $b, -2) }};
        --primary-700: {{ calculateShade($r, $g, $b, -4) }};
        --primary-800: {{ calculateShade($r, $g, $b, -6) }};
        --primary-900: {{ calculateShade($r, $g, $b, -8) }};
        --primary-950: {{ calculateShade($r, $g, $b, -9) }};
    }

    /* Universal Filament component color variables */
    [class*="fi-color-primary"],
    [class*="fi-btn-color-primary"],
    [class*="fi-ac-btn-color-primary"],
    [class*="fi-ta-link-color-primary"],
    [class*="fi-badge-color-primary"],
    [class*="fi-no-color-primary"],
    .fi-color-primary,
    .fi-btn-color-primary,
    .fi-ac-btn-color-primary,
    .fi-ta-link-color-primary,
    .fi-badge-color-primary,
    .fi-no-color-primary {
        --c-50: var(--primary-50);
        --c-100: var(--primary-100);
        --c-200: var(--primary-200);
        --c-300: var(--primary-300);
        --c-400: var(--primary-400);
        --c-500: var(--primary-500);
        --c-600: var(--primary-600);
        --c-700: var(--primary-700);
        --c-800: var(--primary-800);
        --c-900: var(--primary-900);
        --c-950: var(--primary-950);
    }

    /* Sidebar navigation specific targeting */
    .fi-sidebar-nav-item-active,
    .fi-sidebar-nav-item.fi-active {
        --c-50: var(--primary-50);
        --c-500: var(--primary-500);
        --c-600: var(--primary-600);
    }

    /* Additional component targeting for better coverage */
    .fi-btn.fi-color-primary,
    .fi-ac-btn.fi-color-primary,
    .fi-ta-link.fi-color-primary,
    .fi-badge.fi-color-primary,
    .fi-no.fi-color-primary {
        --c-50: var(--primary-50);
        --c-100: var(--primary-100);
        --c-200: var(--primary-200);
        --c-300: var(--primary-300);
        --c-400: var(--primary-400);
        --c-500: var(--primary-500);
        --c-600: var(--primary-600);
        --c-700: var(--primary-700);
        --c-800: var(--primary-800);
        --c-900: var(--primary-900);
        --c-950: var(--primary-950);
    }
</style>
{{-- Always show branding styles for consistency --}} 