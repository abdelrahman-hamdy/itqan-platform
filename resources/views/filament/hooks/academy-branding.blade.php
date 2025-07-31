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
            '[data-action-color="primary"]', // Data table action buttons
            '.fi-btn.fi-btn-color-primary', // Primary buttons with btn-color-primary class
            '.fi-btn.fi-color-custom.fi-btn-color-primary', // Custom colored primary buttons
            'a.fi-btn.fi-color-custom.fi-btn-color-primary', // Link buttons with custom primary color
            '.fi-ac-action.fi-color-primary' // Action buttons with primary color
        ];
        
        backgroundSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                // Skip checkboxes and input elements
                if (el.type === 'checkbox' || el.tagName === 'INPUT') return;
                
                // Force remove any conflicting inline color styles
                el.style.removeProperty('color');
                
                // Apply academy colors with maximum specificity
                el.style.setProperty('background-color', color, 'important');
                el.style.setProperty('color', 'white', 'important');
                el.style.setProperty('border-color', color, 'important');
                
                // Also update CSS custom properties if they exist
                if (el.style.getPropertyValue('--c-500')) {
                    el.style.setProperty('--c-400', color, 'important');
                    el.style.setProperty('--c-500', color, 'important');
                    el.style.setProperty('--c-600', color, 'important');
                }
            });
        });
        
        // TEXT COLOR ELEMENTS (links, text buttons, headings)
        const textSelectors = [
            '.text-primary-500',
            '.text-primary-600',
            '[class*="text-primary-5"]',
            'a.fi-color-primary',
            '.fi-ta-link.fi-color-primary',
            'button.fi-color-primary:not(.fi-btn)', // Text buttons, not solid buttons
            '.fi-color-primary:not(.fi-btn):not(.fi-ac-btn):not(button[type="submit"])'
        ];
        
        textSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
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
    
    // Apply colors immediately
    applyAcademyColors();
    
    // Apply again after short delay (for elements that load later)
    setTimeout(applyAcademyColors, 100);
    setTimeout(applyAcademyColors, 500);
    setTimeout(applyAcademyColors, 1000);
    setTimeout(applyAcademyColors, 2000);
    
    // Watch for DOM changes and reapply colors more intelligently
    const observer = new MutationObserver(function(mutations) {
        let shouldReapply = false;
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    // Check if the added node contains buttons or is a button itself
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && (
                            node.classList.contains('fi-btn') || 
                            node.classList.contains('fi-color-primary') ||
                            node.querySelector && node.querySelector('.fi-btn, .fi-color-primary')
                        )) {
                            shouldReapply = true;
                        }
                    }
                });
            }
            // Also check for attribute changes that might affect styling
            if (mutation.type === 'attributes' && 
                (mutation.attributeName === 'class' || mutation.attributeName === 'style')) {
                shouldReapply = true;
            }
        });
        if (shouldReapply) {
            setTimeout(applyAcademyColors, 50);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'style']
    });
    
    // Also listen for Livewire events if available
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('message.processed', () => {
            setTimeout(applyAcademyColors, 100);
        });
    }
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
    
    /* Comprehensive primary button styling - override inline styles */
    .fi-btn.fi-btn-color-primary,
    .fi-btn.fi-color-custom.fi-btn-color-primary,
    a.fi-btn.fi-color-custom.fi-btn-color-primary,
    button.fi-btn-color-primary,
    .fi-ac-action.fi-color-primary,
    .fi-ac-btn-action.fi-btn-color-primary {
        background-color: {{ $brandColor }} !important;
        color: white !important;
        border-color: {{ $brandColor }} !important;
        --c-400: {{ $brandColor }} !important;
        --c-500: {{ $brandColor }} !important;
        --c-600: {{ $brandColor }} !important;
    }
    
    /* Override hover states for custom primary buttons */
    .fi-btn.fi-btn-color-primary:hover,
    .fi-btn.fi-color-custom.fi-btn-color-primary:hover,
    a.fi-btn.fi-color-custom.fi-btn-color-primary:hover {
        background-color: {{ $brandColor }} !important;
        color: white !important;
        opacity: 0.9;
    }
    
    /* Maximum specificity for problematic buttons with inline styles */
    a.fi-btn.fi-color-custom.fi-btn-color-primary.fi-color-primary.fi-ac-action.fi-ac-btn-action,
    a.fi-btn.fi-color-custom.fi-btn-color-primary.fi-color-primary.fi-ac-action,
    a.fi-btn.fi-color-custom.fi-btn-color-primary.fi-color-primary,
    .fi-btn.fi-color-custom.fi-btn-color-primary.fi-color-primary.fi-ac-action.fi-ac-btn-action {
        background-color: {{ $brandColor }} !important;
        color: white !important;
        border-color: {{ $brandColor }} !important;
        --c-400: {{ $brandColor }} !important;
        --c-500: {{ $brandColor }} !important;
        --c-600: {{ $brandColor }} !important;
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
        background-color: transparent !important;
    }
</style>