@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    $primaryColor = $currentAcademy?->primary_color ?? '#3B82F6';
@endphp

@if($currentAcademy && $currentAcademy->primary_color)
<style>
    :root {
        /* Override Filament's primary color variables directly */
        --primary-50: {{ $primaryColor }}19;    /* 10% opacity */
        --primary-100: {{ $primaryColor }}33;   /* 20% opacity */
        --primary-200: {{ $primaryColor }}4D;   /* 30% opacity */
        --primary-300: {{ $primaryColor }}66;   /* 40% opacity */
        --primary-400: {{ $primaryColor }}80;   /* 50% opacity */
        --primary-500: {{ $primaryColor }};     /* 100% opacity */
        --primary-600: {{ $primaryColor }};     /* Same as 500 for now */
        --primary-700: {{ $primaryColor }};     /* Same as 500 for now */
        --primary-800: {{ $primaryColor }};     /* Same as 500 for now */
        --primary-900: {{ $primaryColor }};     /* Same as 500 for now */
        --primary-950: {{ $primaryColor }}E6;   /* 90% opacity */
    }
    
    /* Force override any cached color values */
    .fi-btn-color-primary {
        --c-400: {{ $primaryColor }};
        --c-500: {{ $primaryColor }};
        --c-600: {{ $primaryColor }};
    }
    
    /* Navigation active states */
    .fi-sidebar-nav-item-active {
        --c-400: {{ $primaryColor }};
        --c-500: {{ $primaryColor }};
        --c-600: {{ $primaryColor }};
    }
    
    /* Tabs */
    .fi-tabs-tab-active {
        color: {{ $primaryColor }} !important;
        border-bottom-color: {{ $primaryColor }} !important;
    }
    
    /* Primary buttons */
    .bg-primary-600,
    .bg-primary-500 {
        background-color: {{ $primaryColor }} !important;
    }
    
    /* Text colors */
    .text-primary-600,
    .text-primary-500 {
        color: {{ $primaryColor }} !important;
    }
    
    /* Border colors */
    .border-primary-600,
    .border-primary-500 {
        border-color: {{ $primaryColor }} !important;
    }
    
    /* Ring colors */
    .ring-primary-600,
    .ring-primary-500 {
        --tw-ring-color: {{ $primaryColor }} !important;
    }
</style>
@endif 