@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    $primaryColorHex = $currentAcademy?->brand_color ?? '#3B82F6';
    
    // Convert hex to RGB for Filament CSS variables
    $hex = ltrim($primaryColorHex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $primaryColorRgb = "$r $g $b";
@endphp

{{-- Academy Branding System --}}

@if($currentAcademy)
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
    }
    
    /* Force override any cached color values */
    .fi-btn-color-primary {
        --c-400: {{ $primaryColorRgb }};
        --c-500: {{ $primaryColorRgb }};
        --c-600: {{ $primaryColorRgb }};
    }
    
    /* Navigation active states */
    .fi-sidebar-nav-item-active {
        --c-400: {{ $primaryColorRgb }};
        --c-500: {{ $primaryColorRgb }};
        --c-600: {{ $primaryColorRgb }};
    }
    
    /* Tabs */
    .fi-tabs-tab-active {
        color: {{ $primaryColorHex }} !important;
        border-bottom-color: {{ $primaryColorHex }} !important;
    }
    
    /* Primary buttons */
    .bg-primary-600,
    .bg-primary-500 {
        background-color: {{ $primaryColorHex }} !important;
    }
    
    /* Text colors */
    .text-primary-600,
    .text-primary-500 {
        color: {{ $primaryColorHex }} !important;
    }
    
    /* Border colors */
    .border-primary-600,
    .border-primary-500 {
        border-color: {{ $primaryColorHex }} !important;
    }
    
    /* Ring colors */
    .ring-primary-600,
    .ring-primary-500 {
        --tw-ring-color: {{ $primaryColorHex }} !important;
    }
</style>
@endif 