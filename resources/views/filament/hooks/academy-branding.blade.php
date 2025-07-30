@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    $primaryColor = $currentAcademy?->primary_color ?? '#3B82F6';
    
    // Convert hex to RGB for CSS custom properties
    $hex = ltrim($primaryColor, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
@endphp

<style>
    :root {
        --primary-50: rgb({{ min(255, $r + 40) }}, {{ min(255, $g + 40) }}, {{ min(255, $b + 40) }});
        --primary-100: rgb({{ min(255, $r + 30) }}, {{ min(255, $g + 30) }}, {{ min(255, $b + 30) }});
        --primary-200: rgb({{ min(255, $r + 20) }}, {{ min(255, $g + 20) }}, {{ min(255, $b + 20) }});
        --primary-300: rgb({{ min(255, $r + 10) }}, {{ min(255, $g + 10) }}, {{ min(255, $b + 10) }});
        --primary-400: rgb({{ max(0, $r - 10) }}, {{ max(0, $g - 10) }}, {{ max(0, $b - 10) }});
        --primary-500: rgb({{ $r }}, {{ $g }}, {{ $b }});
        --primary-600: rgb({{ max(0, $r - 20) }}, {{ max(0, $g - 20) }}, {{ max(0, $b - 20) }});
        --primary-700: rgb({{ max(0, $r - 40) }}, {{ max(0, $g - 40) }}, {{ max(0, $b - 40) }});
        --primary-800: rgb({{ max(0, $r - 60) }}, {{ max(0, $g - 60) }}, {{ max(0, $b - 60) }});
        --primary-900: rgb({{ max(0, $r - 80) }}, {{ max(0, $g - 80) }}, {{ max(0, $b - 80) }});
        --primary-950: rgb({{ max(0, $r - 100) }}, {{ max(0, $g - 100) }}, {{ max(0, $b - 100) }});
    }
    
    /* Override Filament's primary colors */
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
    
    /* Apply academy primary color to key Filament components */
    .fi-btn-color-primary,
    .fi-badge-color-primary,
    .fi-sidebar-nav-item-active,
    .fi-tabs-tab-active,
    .fi-color-primary {
        --c-500: var(--primary-500) !important;
        --c-600: var(--primary-600) !important;
        --c-700: var(--primary-700) !important;
    }
    
    /* Primary buttons */
    .fi-btn-color-primary {
        background-color: rgb(var(--primary-500)) !important;
        border-color: rgb(var(--primary-500)) !important;
    }
    
    .fi-btn-color-primary:hover {
        background-color: rgb(var(--primary-600)) !important;
        border-color: rgb(var(--primary-600)) !important;
    }
    
    /* Active navigation items */
    .fi-sidebar-nav-item-active .fi-sidebar-nav-item-button {
        background-color: rgb(var(--primary-100)) !important;
        color: rgb(var(--primary-700)) !important;
    }
    
    .fi-sidebar-nav-item-active .fi-sidebar-nav-item-icon {
        color: rgb(var(--primary-600)) !important;
    }
    
    /* Focus states */
    .fi-fo-field-wrp:focus-within .fi-fo-field-wrp-label,
    .fi-fo-field-wrp:focus-within .fi-fo-field-wrp-helper-text {
        color: rgb(var(--primary-600)) !important;
    }
    
    /* Form field focus borders */
    .fi-input:focus,
    .fi-select-input:focus,
    .fi-textarea:focus {
        border-color: rgb(var(--primary-500)) !important;
        box-shadow: 0 0 0 1px rgb(var(--primary-500)) !important;
    }
    
    /* Checkbox and radio buttons */
    .fi-checkbox-input:checked,
    .fi-radio-input:checked {
        background-color: rgb(var(--primary-500)) !important;
        border-color: rgb(var(--primary-500)) !important;
    }
    
    /* Table row actions */
    .fi-ta-actions .fi-btn-color-primary {
        color: rgb(var(--primary-600)) !important;
    }
    
    /* Badges */
    .fi-badge-color-primary {
        background-color: rgb(var(--primary-100)) !important;
        color: rgb(var(--primary-700)) !important;
    }
    
    /* Links */
    .fi-link {
        color: rgb(var(--primary-600)) !important;
    }
    
    .fi-link:hover {
        color: rgb(var(--primary-700)) !important;
    }
    
    /* Tabs */
    .fi-tabs-tab-active {
        color: rgb(var(--primary-600)) !important;
        border-bottom-color: rgb(var(--primary-500)) !important;
    }
    
    /* Top navigation active items */
    .fi-topbar-item-active {
        color: rgb(var(--primary-600)) !important;
    }
    
    /* Progress bars and loading indicators */
    .fi-progress-bar-bar {
        background-color: rgb(var(--primary-500)) !important;
    }
    
    /* Additional Filament components */
    .fi-dropdown-list-item:hover,
    .fi-dropdown-list-item:focus {
        background-color: rgb(var(--primary-50)) !important;
    }
    
    .fi-ta-header-cell-sort-button:hover,
    .fi-ta-header-cell-sort-button:focus {
        color: rgb(var(--primary-600)) !important;
    }
    
    .fi-pagination-nav-item-active {
        background-color: rgb(var(--primary-500)) !important;
        border-color: rgb(var(--primary-500)) !important;
        color: white !important;
    }
    
    .fi-modal-header {
        border-color: rgb(var(--primary-200)) !important;
    }
    
    /* Notification and alert primary colors */
    .fi-notification-color-primary {
        background-color: rgb(var(--primary-100)) !important;
        border-color: rgb(var(--primary-300)) !important;
        color: rgb(var(--primary-700)) !important;
    }
    
    /* Form validation success states */
    .fi-fo-field-wrp-error-icon {
        color: rgb(var(--primary-500)) !important;
    }
    
    /* Toggle switches */
    .fi-toggle-switch-input:checked + .fi-toggle-switch-track {
        background-color: rgb(var(--primary-500)) !important;
    }
    
    /* File upload progress */
    .fi-fo-file-upload-progress-bar {
        background-color: rgb(var(--primary-500)) !important;
    }
    
    /* Search input focus */
    .fi-global-search-input:focus {
        border-color: rgb(var(--primary-500)) !important;
        box-shadow: 0 0 0 1px rgb(var(--primary-500)) !important;
    }
    
    /* Dark mode adjustments */
    .dark .fi-sidebar-nav-item-active .fi-sidebar-nav-item-button {
        background-color: rgb(var(--primary-900)) !important;
        color: rgb(var(--primary-300)) !important;
    }
    
    .dark .fi-sidebar-nav-item-active .fi-sidebar-nav-item-icon {
        color: rgb(var(--primary-400)) !important;
    }
    
    .dark .fi-badge-color-primary {
        background-color: rgb(var(--primary-900)) !important;
        color: rgb(var(--primary-300)) !important;
    }
    
    .dark .fi-tabs-tab-active {
        color: rgb(var(--primary-400)) !important;
        border-bottom-color: rgb(var(--primary-500)) !important;
    }
    
    .dark .fi-dropdown-list-item:hover,
    .dark .fi-dropdown-list-item:focus {
        background-color: rgb(var(--primary-900)) !important;
    }
    
    .dark .fi-notification-color-primary {
        background-color: rgb(var(--primary-900)) !important;
        border-color: rgb(var(--primary-700)) !important;
        color: rgb(var(--primary-300)) !important;
    }
</style>

@if($currentAcademy)
    <!-- Additional academy-specific styling -->
    <style>
        /* Brand logo styling if academy has logo */
        @if($currentAcademy->logo)
        .fi-logo img {
            content: url('{{ $currentAcademy->logo }}') !important;
            max-height: 2rem !important;
            width: auto !important;
        }
        @endif
        
        /* Update brand name */
        .fi-logo::after {
            content: '{{ $currentAcademy->name }}' !important;
            font-weight: 600 !important;
            color: rgb(var(--primary-600)) !important;
            margin-left: 0.5rem !important;
        }
    </style>
@endif 