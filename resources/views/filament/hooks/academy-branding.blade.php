@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    $brandColor = $currentAcademy?->brand_color ?? '#0ea5e9';
@endphp

{{-- SELECTIVE Academy Branding System - Precise Color Application --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    function applyAcademyColors() {
        const color = '{{ $brandColor }}';
        
        // CLEANUP: Remove problematic inline color styles from buttons first
        document.querySelectorAll('.fi-btn.fi-color-primary, .fi-ac-btn-action.fi-color-primary, .fi-ac-action.fi-color-primary').forEach(el => {
            // Remove any inline color that might override our styles
            el.style.removeProperty('color'); 
        });
        
        // BACKGROUND COLOR ELEMENTS (solid buttons, active sidebar items)
        const backgroundSelectors = [
            '.fi-sidebar-nav-item.fi-active',
            '.fi-sidebar-nav-item-active',
            '.fi-btn.fi-color-primary',
            '.fi-ac-btn.fi-color-primary', 
            'button[type="submit"]:not(.fi-btn-color-gray)',
            '.bg-primary-500',
            '[class*="bg-primary-5"]',
            '.fi-ta-actions .fi-btn.fi-color-primary', // Edit buttons in tables
            '.fi-ac-btn-action.fi-color-primary', // Action buttons
            '[data-action-color="primary"]' // Data table action buttons
        ];
        
        backgroundSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                // Skip checkboxes and input elements
                if (el.type === 'checkbox' || el.tagName === 'INPUT') return;
                el.style.setProperty('background-color', color, 'important');
                el.style.setProperty('color', 'white', 'important'); // Ensure white text on primary buttons
            });
        });
        
        // TEXT COLOR ELEMENTS (links, text buttons, headings)
        const textSelectors = [
            '.text-primary-500',
            '.text-primary-600',
            '[class*="text-primary-5"]',
            'a.fi-color-primary:not(.fi-btn):not(.fi-ac-btn-action):not(.fi-ac-action)',
            '.fi-ta-link.fi-color-primary:not(.fi-btn)',
            // Explicitly exclude all button classes to prevent color conflicts
            '.fi-color-primary:not(.fi-btn):not(.fi-ac-btn):not(.fi-ac-btn-action):not(.fi-ac-action):not(button):not([role="button"])'
        ];
        
        textSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                // Skip if element has any button-related classes or is a button
                if (el.classList.contains('fi-btn') || 
                    el.classList.contains('fi-ac-btn') || 
                    el.classList.contains('fi-ac-btn-action') || 
                    el.classList.contains('fi-ac-action') ||
                    el.tagName === 'BUTTON' ||
                    el.getAttribute('role') === 'button') {
                    return; // Skip this element
                }
                // Only apply text color, not background
                el.style.setProperty('color', color, 'important');
                el.style.removeProperty('background-color'); // Remove any background
            });
        });
        
        // BORDER COLOR ELEMENTS
        const borderSelectors = [
            '.border-primary-500',
            '[class*="border-primary-5"]'
        ];
        
        borderSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.style.setProperty('border-color', color, 'important');
            });
        });
        
        // EXCLUDE ELEMENTS (checkboxes, stats sections, etc.)
        const excludeSelectors = [
            'input[type="checkbox"]',
            '.fi-stats-overview', // Stats sections should remain transparent
            '[data-field-wrapper]' // Form field wrappers
        ];
        
        excludeSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                if (el.classList.contains('fi-color-primary')) {
                    // Remove background, keep only text color if needed
                    el.style.removeProperty('background-color');
                }
            });
        });
        
        // Update CSS custom properties
        document.documentElement.style.setProperty('--academy-primary', color);
    }
    
    // Function to clean up problematic inline styles
    function cleanupInlineStyles() {
        document.querySelectorAll('.fi-btn.fi-color-primary, .fi-ac-btn-action.fi-color-primary, .fi-ac-action.fi-color-primary').forEach(el => {
            // Remove any problematic inline color styles that aren't white
            if (el.style.color && el.style.color !== 'white' && el.style.color !== 'rgb(255, 255, 255)') {
                el.style.removeProperty('color');
            }
        });
    }
    
    // Apply colors immediately
    cleanupInlineStyles();
    applyAcademyColors();
    
    // Apply again after short delay (for elements that load later)
    setTimeout(() => {
        cleanupInlineStyles();
        applyAcademyColors();
    }, 100);
    setTimeout(() => {
        cleanupInlineStyles();
        applyAcademyColors();
    }, 500);
    
    // Watch for new elements (for dynamic content)
    const observer = new MutationObserver(function() {
        setTimeout(() => {
            cleanupInlineStyles();
            applyAcademyColors();
        }, 50);
    });
    observer.observe(document.body, { childList: true, subtree: true });
});
</script>

<style>
    :root {
        --academy-primary: {{ $brandColor }};
    }
    
    /* BACKGROUND COLORS - Only for solid buttons and active states */
    .fi-sidebar-nav-item.fi-active,
    .fi-sidebar-nav-item-active,
    .fi-btn.fi-color-primary,
    .fi-ac-btn.fi-color-primary,
    button[type="submit"]:not(.fi-btn-color-gray),
    .bg-primary-500,
    [class*="bg-primary-5"],
    .fi-ta-actions .fi-btn.fi-color-primary,
    .fi-ac-btn-action.fi-color-primary,
    [data-action-color="primary"],
    .fi-dropdown-list-item.fi-color-primary {
        background-color: {{ $brandColor }} !important;
        color: white !important; /* Ensure white text on primary buttons */
    }
    
    /* SIDEBAR ACTIVE ITEM TEXT - Ensure active sidebar items use primary color for text */
    .fi-sidebar-nav-item.fi-active .fi-sidebar-nav-item-label,
    .fi-sidebar-nav-item-active .fi-sidebar-nav-item-label,
    .fi-sidebar-nav-item.fi-active span,
    .fi-sidebar-nav-item-active span {
        color: white !important; /* Force white text on active sidebar items */
    }
    
    /* TEXT COLORS - For links, text buttons, headings */
    .text-primary-500,
    [class*="text-primary-5"],
    a.fi-color-primary,
    .fi-ta-link.fi-color-primary {
        color: {{ $brandColor }} !important;
        background-color: transparent !important; /* Ensure no background */
    }
    
    /* BORDER COLORS */
    .border-primary-500,
    [class*="border-primary-5"] {
        border-color: {{ $brandColor }} !important;
    }
    
    /* Focus states */
    .focus\:ring-primary-500:focus,
    [class*="focus:ring-primary-5"]:focus {
        --tw-ring-color: {{ $brandColor }} !important;
    }
    
    /* Additional button styling for data table actions */
    .fi-ta-actions button.fi-btn.fi-color-primary,
    .fi-ta-actions a.fi-btn.fi-color-primary,
    .fi-table .fi-ta-actions .fi-btn.fi-color-primary {
        background-color: {{ $brandColor }} !important;
        color: white !important;
        border-color: {{ $brandColor }} !important;
    }
    
    /* EXCLUDE ELEMENTS - Explicitly prevent coloring */
    input[type="checkbox"],
    input[type="radio"],
    .fi-stats-overview,
    .fi-stats-overview .fi-color-primary {
        background-color: transparent !important;
        background: transparent !important;
    }
    
    /* Text-only primary elements (no background) */
    .fi-color-primary:not(.fi-btn):not(.fi-ac-btn):not(button) {
        color: {{ $brandColor }} !important;
        background-color: color-mix(in srgb, {{ $brandColor }} 5%, transparent 90%) !important;
    }
</style>