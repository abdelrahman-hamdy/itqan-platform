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

{{-- Academy Branding System - Always apply colors --}}
<style>
    :root {
        /* Override Filament's primary color variables with RGB values */
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

        /* Filament specific color variables */
        --fi-color-primary-50: {{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }};
        --fi-color-primary-100: {{ min(255, $r + 30) }} {{ min(255, $g + 30) }} {{ min(255, $b + 30) }};
        --fi-color-primary-200: {{ min(255, $r + 20) }} {{ min(255, $g + 20) }} {{ min(255, $b + 20) }};
        --fi-color-primary-300: {{ min(255, $r + 10) }} {{ min(255, $g + 10) }} {{ min(255, $b + 10) }};
        --fi-color-primary-400: {{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }};
        --fi-color-primary-500: {{ $primaryColorRgb }};
        --fi-color-primary-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
        --fi-color-primary-700: {{ max(0, $r - 40) }} {{ max(0, $g - 40) }} {{ max(0, $b - 40) }};
        --fi-color-primary-800: {{ max(0, $r - 60) }} {{ max(0, $g - 60) }} {{ max(0, $b - 60) }};
        --fi-color-primary-900: {{ max(0, $r - 80) }} {{ max(0, $g - 80) }} {{ max(0, $b - 80) }};
        --fi-color-primary-950: {{ max(0, $r - 100) }} {{ max(0, $g - 100) }} {{ max(0, $b - 100) }};
    }

    /* Filament Component Color Overrides */
    .fi-color-primary {
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

    /* Button color overrides */
    .fi-btn-color-primary,
    .fi-btn.fi-color-primary {
        --c-400: {{ $primaryColorRgb }};
        --c-500: {{ $primaryColorRgb }};
        --c-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
    }

    /* Navigation active states */
    .fi-sidebar-nav-item-active,
    .fi-sidebar-nav-item.fi-active {
        --c-400: {{ $primaryColorRgb }};
        --c-500: {{ $primaryColorRgb }};
        --c-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
    }

    /* Form inputs and controls */
    .fi-input-wrp:focus-within,
    .fi-select:focus-within,
    .fi-textarea:focus-within {
        --c-600: {{ $primaryColorRgb }};
        border-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Checkbox and radio controls */
    .fi-checkbox input:checked,
    .fi-radio input:checked {
        background-color: rgb({{ $primaryColorRgb }}) !important;
        border-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Toggle switches */
    .fi-toggle input:checked + .fi-toggle-slider {
        background-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Table actions and links */
    .fi-ta-link-color-primary,
    .fi-table .fi-ta-link.fi-color-primary {
        --c-500: {{ $primaryColorRgb }};
        --c-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
    }

    /* Action button colors */
    .fi-ac-btn-color-primary,
    .fi-ac-btn.fi-color-primary {
        --c-500: {{ $primaryColorRgb }};
        --c-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
    }

    /* Tabs */
    .fi-tabs-tab-active,
    .fi-tabs .fi-tabs-tab.fi-active {
        color: rgb({{ $primaryColorRgb }}) !important;
        border-bottom-color: rgb({{ $primaryColorRgb }}) !important;
    }

    /* Badge colors */
    .fi-badge-color-primary,
    .fi-badge.fi-color-primary {
        --c-50: {{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }};
        --c-400: {{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }};
        --c-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
    }

    /* Notification colors */
    .fi-no-color-primary {
        --c-50: {{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }};
        --c-400: {{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }};
        --c-600: {{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }};
    }

    /* Global primary color utilities */
    .bg-primary-50 { background-color: rgb({{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }}) !important; }
    .bg-primary-100 { background-color: rgb({{ min(255, $r + 30) }} {{ min(255, $g + 30) }} {{ min(255, $b + 30) }}) !important; }
    .bg-primary-200 { background-color: rgb({{ min(255, $r + 20) }} {{ min(255, $g + 20) }} {{ min(255, $b + 20) }}) !important; }
    .bg-primary-300 { background-color: rgb({{ min(255, $r + 10) }} {{ min(255, $g + 10) }} {{ min(255, $b + 10) }}) !important; }
    .bg-primary-400 { background-color: rgb({{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }}) !important; }
    .bg-primary-500 { background-color: rgb({{ $primaryColorRgb }}) !important; }
    .bg-primary-600 { background-color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important; }
    .bg-primary-700 { background-color: rgb({{ max(0, $r - 40) }} {{ max(0, $g - 40) }} {{ max(0, $b - 40) }}) !important; }
    .bg-primary-800 { background-color: rgb({{ max(0, $r - 60) }} {{ max(0, $g - 60) }} {{ max(0, $b - 60) }}) !important; }
    .bg-primary-900 { background-color: rgb({{ max(0, $r - 80) }} {{ max(0, $g - 80) }} {{ max(0, $b - 80) }}) !important; }

    .text-primary-50 { color: rgb({{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }}) !important; }
    .text-primary-100 { color: rgb({{ min(255, $r + 30) }} {{ min(255, $g + 30) }} {{ min(255, $b + 30) }}) !important; }
    .text-primary-200 { color: rgb({{ min(255, $r + 20) }} {{ min(255, $g + 20) }} {{ min(255, $b + 20) }}) !important; }
    .text-primary-300 { color: rgb({{ min(255, $r + 10) }} {{ min(255, $g + 10) }} {{ min(255, $b + 10) }}) !important; }
    .text-primary-400 { color: rgb({{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }}) !important; }
    .text-primary-500 { color: rgb({{ $primaryColorRgb }}) !important; }
    .text-primary-600 { color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important; }
    .text-primary-700 { color: rgb({{ max(0, $r - 40) }} {{ max(0, $g - 40) }} {{ max(0, $b - 40) }}) !important; }
    .text-primary-800 { color: rgb({{ max(0, $r - 60) }} {{ max(0, $g - 60) }} {{ max(0, $b - 60) }}) !important; }
    .text-primary-900 { color: rgb({{ max(0, $r - 80) }} {{ max(0, $g - 80) }} {{ max(0, $b - 80) }}) !important; }

    .border-primary-50 { border-color: rgb({{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }}) !important; }
    .border-primary-100 { border-color: rgb({{ min(255, $r + 30) }} {{ min(255, $g + 30) }} {{ min(255, $b + 30) }}) !important; }
    .border-primary-200 { border-color: rgb({{ min(255, $r + 20) }} {{ min(255, $g + 20) }} {{ min(255, $b + 20) }}) !important; }
    .border-primary-300 { border-color: rgb({{ min(255, $r + 10) }} {{ min(255, $g + 10) }} {{ min(255, $b + 10) }}) !important; }
    .border-primary-400 { border-color: rgb({{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }}) !important; }
    .border-primary-500 { border-color: rgb({{ $primaryColorRgb }}) !important; }
    .border-primary-600 { border-color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important; }
    .border-primary-700 { border-color: rgb({{ max(0, $r - 40) }} {{ max(0, $g - 40) }} {{ max(0, $b - 40) }}) !important; }
    .border-primary-800 { border-color: rgb({{ max(0, $r - 60) }} {{ max(0, $g - 60) }} {{ max(0, $b - 60) }}) !important; }
    .border-primary-900 { border-color: rgb({{ max(0, $r - 80) }} {{ max(0, $g - 80) }} {{ max(0, $b - 80) }}) !important; }

    /* Ring colors for focus states */
    .ring-primary-50 { --tw-ring-color: rgb({{ min(255, $r + 40) }} {{ min(255, $g + 40) }} {{ min(255, $b + 40) }}) !important; }
    .ring-primary-100 { --tw-ring-color: rgb({{ min(255, $r + 30) }} {{ min(255, $g + 30) }} {{ min(255, $b + 30) }}) !important; }
    .ring-primary-200 { --tw-ring-color: rgb({{ min(255, $r + 20) }} {{ min(255, $g + 20) }} {{ min(255, $b + 20) }}) !important; }
    .ring-primary-300 { --tw-ring-color: rgb({{ min(255, $r + 10) }} {{ min(255, $g + 10) }} {{ min(255, $b + 10) }}) !important; }
    .ring-primary-400 { --tw-ring-color: rgb({{ max(0, $r - 10) }} {{ max(0, $g - 10) }} {{ max(0, $b - 10) }}) !important; }
    .ring-primary-500 { --tw-ring-color: rgb({{ $primaryColorRgb }}) !important; }
    .ring-primary-600 { --tw-ring-color: rgb({{ max(0, $r - 20) }} {{ max(0, $g - 20) }} {{ max(0, $b - 20) }}) !important; }
    .ring-primary-700 { --tw-ring-color: rgb({{ max(0, $r - 40) }} {{ max(0, $g - 40) }} {{ max(0, $b - 40) }}) !important; }
    .ring-primary-800 { --tw-ring-color: rgb({{ max(0, $r - 60) }} {{ max(0, $g - 60) }} {{ max(0, $b - 60) }}) !important; }
    .ring-primary-900 { --tw-ring-color: rgb({{ max(0, $r - 80) }} {{ max(0, $g - 80) }} {{ max(0, $b - 80) }}) !important; }
</style>
{{-- Always show branding styles for consistency --}} 