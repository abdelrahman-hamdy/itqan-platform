@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    
    // Use academy color if available, otherwise use default blue
    $primaryColorHex = $currentAcademy?->brand_color ?? '#3B82F6';
    
    // Convert hex to RGB for Filament CSS variables
    $hex = ltrim($primaryColorHex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $primaryColorRgb = "$r $g $b";
@endphp

{{-- Academy Branding System - Comprehensive Color Override --}}
<style>
    /* Core CSS Custom Properties - Foundation */
    :root {
        --primary-50: {{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }};
        --primary-100: {{ min(255, $r + 30) }} {{ min(255, $g + 30) }} {{ min(255, $b + 30) }};
        --primary-200: {{ min(255, $r + 20) }} {{ min(255, $g + 20) }} {{ min(255, $b + 20) }};
        --primary-300: {{ min(255, $r + 10) }} {{ min(255, $g + 10) }} {{ min(255, $b + 10) }};
        --primary-400: {{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }};
        --primary-500: {{ $primaryColorRgb }};
        --primary-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
        --primary-700: {{ max(0, $r - 40) }} {{ max(0, $g - 40) }} {{ max(0, $b - 40) }};
        --primary-800: {{ max(0, $r - 60) }} {{ max(0, $g - 60) }} {{ max(0, $b - 60) }};
        --primary-900: {{ max(0, $r - 80) }} {{ max(0, $g - 80) }} {{ max(0, $b - 80) }};
        --primary-950: {{ max(0, $r - 100) }} {{ max(0, $g - 100) }} {{ max(0, $b - 100) }};
    }

    /* Universal Filament Component Color Override */
    [class*="fi-color-primary"], 
    [class*="fi-btn-color-primary"],
    [class*="fi-ac-btn-color-primary"],
    [class*="fi-ta-link-color-primary"],
    [class*="fi-badge-color-primary"] {
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

    /* Focus States for All Primary Elements */
    [class*="ring-primary-"],
    input:focus,
    select:focus,
    textarea:focus,
    button:focus,
    [role="button"]:focus {
        --tw-ring-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Comprehensive Tailwind Utility Override */
    .bg-primary-500 { background-color: rgb({{ $primaryColorRgb }}) !important; }
    .bg-primary-600 { background-color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important; }
    .text-primary-500 { color: rgb({{ $primaryColorRgb }}) !important; }
    .text-primary-600 { color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important; }
    .border-primary-500 { border-color: rgb({{ $primaryColorRgb }}) !important; }
    .border-primary-600 { border-color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important; }

    /* Direct Element Targeting - Most Aggressive */
    button[type="submit"],
    .fi-btn-primary,
    .fi-btn--color-primary,
    [data-filament-color="primary"] {
        background-color: rgb({{ $primaryColorRgb }}) !important;
        border-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Table and Action Overrides */
    .fi-ta-btn-primary,
    .fi-ac-btn-primary,
    .fi-table .fi-ta-action button[data-filament-color="primary"] {
        background-color: rgb({{ $primaryColorRgb }}) !important;
        color: white !important;
    }

    /* Form Control Overrides */
    .fi-input:focus,
    .fi-select:focus,
    .fi-textarea:focus {
        border-color: rgb({{ $primaryColorRgb }}) !important;
        box-shadow: 0 0 0 1px rgb({{ $primaryColorRgb }}) !important;
    }

    /* Checkbox and Toggle Overrides */
    input[type="checkbox"]:checked {
        background-color: rgb({{ $primaryColorRgb }}) !important;
        border-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Active Navigation Override */
    .fi-sidebar-nav-item-active {
        background-color: rgb({{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }}) !important;
        color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important;
    }

    /* Tabs Override */
    .fi-tabs-tab[aria-selected="true"] {
        color: rgb({{ $primaryColorRgb }}) !important;
        border-bottom-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Debug: Force override any cached styles */
    * {
        --academy-primary: rgb({{ $primaryColorRgb }}) !important;
    }
</style>
{{-- Always show branding styles for consistency --}} 