@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    
    // Use academy color if available and not empty, otherwise use Filament's default blue
    $brandColor = $currentAcademy?->brand_color;
    if (empty($brandColor) || $brandColor === null || $brandColor === '') {
        $primaryColorHex = '#3B82F6'; // Default Filament blue
    } else {
        $primaryColorHex = $brandColor;
    }
    
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

{{-- Academy Branding System - Comprehensive Filament Color Integration --}}
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

    /* Global color override - Most aggressive approach */
    * {
        --c-50: var(--primary-50) !important;
        --c-100: var(--primary-100) !important;
        --c-200: var(--primary-200) !important;
        --c-300: var(--primary-300) !important;
        --c-400: var(--primary-400) !important;
        --c-500: var(--primary-500) !important;
        --c-600: var(--primary-600) !important;
        --c-700: var(--primary-700) !important;
        --c-800: var(--primary-800) !important;
        --c-900: var(--primary-900) !important;
        --c-950: var(--primary-950) !important;
    }

    /* Filament specific targeting */
    [class*="fi-"],
    [class*="filament"],
    [data-filament-color="primary"] {
        --c-50: var(--primary-50) !important;
        --c-100: var(--primary-100) !important;
        --c-200: var(--primary-200) !important;
        --c-300: var(--primary-300) !important;
        --c-400: var(--primary-400) !important;
        --c-500: var(--primary-500) !important;
        --c-600: var(--primary-600) !important;
        --c-700: var(--primary-700) !important;
        --c-800: var(--primary-800) !important;
        --c-900: var(--primary-900) !important;
        --c-950: var(--primary-950) !important;
    }

    /* Direct CSS class overrides for common patterns */
    .bg-primary-50 { background-color: rgb({{ calculateShade($r, $g, $b, 9) }}) !important; }
    .bg-primary-100 { background-color: rgb({{ calculateShade($r, $g, $b, 8) }}) !important; }
    .bg-primary-200 { background-color: rgb({{ calculateShade($r, $g, $b, 6) }}) !important; }
    .bg-primary-300 { background-color: rgb({{ calculateShade($r, $g, $b, 4) }}) !important; }
    .bg-primary-400 { background-color: rgb({{ calculateShade($r, $g, $b, 2) }}) !important; }
    .bg-primary-500 { background-color: rgb({{ $primaryColorRgb }}) !important; }
    .bg-primary-600 { background-color: rgb({{ calculateShade($r, $g, $b, -2) }}) !important; }
    .bg-primary-700 { background-color: rgb({{ calculateShade($r, $g, $b, -4) }}) !important; }
    .bg-primary-800 { background-color: rgb({{ calculateShade($r, $g, $b, -6) }}) !important; }
    .bg-primary-900 { background-color: rgb({{ calculateShade($r, $g, $b, -8) }}) !important; }

    .text-primary-50 { color: rgb({{ calculateShade($r, $g, $b, 9) }}) !important; }
    .text-primary-100 { color: rgb({{ calculateShade($r, $g, $b, 8) }}) !important; }
    .text-primary-200 { color: rgb({{ calculateShade($r, $g, $b, 6) }}) !important; }
    .text-primary-300 { color: rgb({{ calculateShade($r, $g, $b, 4) }}) !important; }
    .text-primary-400 { color: rgb({{ calculateShade($r, $g, $b, 2) }}) !important; }
    .text-primary-500 { color: rgb({{ $primaryColorRgb }}) !important; }
    .text-primary-600 { color: rgb({{ calculateShade($r, $g, $b, -2) }}) !important; }
    .text-primary-700 { color: rgb({{ calculateShade($r, $g, $b, -4) }}) !important; }
    .text-primary-800 { color: rgb({{ calculateShade($r, $g, $b, -6) }}) !important; }
    .text-primary-900 { color: rgb({{ calculateShade($r, $g, $b, -8) }}) !important; }

    .border-primary-50 { border-color: rgb({{ calculateShade($r, $g, $b, 9) }}) !important; }
    .border-primary-100 { border-color: rgb({{ calculateShade($r, $g, $b, 8) }}) !important; }
    .border-primary-200 { border-color: rgb({{ calculateShade($r, $g, $b, 6) }}) !important; }
    .border-primary-300 { border-color: rgb({{ calculateShade($r, $g, $b, 4) }}) !important; }
    .border-primary-400 { border-color: rgb({{ calculateShade($r, $g, $b, 2) }}) !important; }
    .border-primary-500 { border-color: rgb({{ $primaryColorRgb }}) !important; }
    .border-primary-600 { border-color: rgb({{ calculateShade($r, $g, $b, -2) }}) !important; }
    .border-primary-700 { border-color: rgb({{ calculateShade($r, $g, $b, -4) }}) !important; }
    .border-primary-800 { border-color: rgb({{ calculateShade($r, $g, $b, -6) }}) !important; }
    .border-primary-900 { border-color: rgb({{ calculateShade($r, $g, $b, -8) }}) !important; }

    /* Ring colors for focus states */
    .ring-primary-50 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, 9) }}) !important; }
    .ring-primary-100 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, 8) }}) !important; }
    .ring-primary-200 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, 6) }}) !important; }
    .ring-primary-300 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, 4) }}) !important; }
    .ring-primary-400 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, 2) }}) !important; }
    .ring-primary-500 { --tw-ring-color: rgb({{ $primaryColorRgb }}) !important; }
    .ring-primary-600 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, -2) }}) !important; }
    .ring-primary-700 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, -4) }}) !important; }
    .ring-primary-800 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, -6) }}) !important; }  
    .ring-primary-900 { --tw-ring-color: rgb({{ calculateShade($r, $g, $b, -8) }}) !important; }
</style>
{{-- Always show branding styles for consistency --}} 