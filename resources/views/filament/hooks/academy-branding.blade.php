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
            '.fi-btn-color-success', // Save button (change from green to primary)
            'button.fi-btn-color-success' // Save button variants
        ];
        
        backgroundSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                // Skip checkboxes and input elements
                if (el.type === 'checkbox' || el.tagName === 'INPUT') return;
                el.style.setProperty('background-color', color, 'important');
                
                // For buttons with primary background, ensure white text
                if (el.tagName === 'BUTTON' || el.classList.contains('fi-btn')) {
                    el.style.setProperty('color', 'white', 'important');
                }
            });
        });
        
        // TEXT COLOR ELEMENTS (links, text buttons, headings)
        const textSelectors = [
            '.text-primary-500',
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
        
        // SUBTLE BACKGROUND ELEMENTS (labels with borders)
        const subtleBackgroundSelectors = [
            '.fi-badge.fi-color-primary',
            '.fi-color-primary[class*="border"]',
            'span.fi-color-primary[class*="border"]'
        ];
        
        // Calculate subtle shade (10% opacity of primary color)
        const primaryRgb = color.replace('#', '').match(/.{2}/g).map(hex => parseInt(hex, 16));
        const subtleBackground = `rgba(${primaryRgb[0]}, ${primaryRgb[1]}, ${primaryRgb[2]}, 0.1)`;
        
        subtleBackgroundSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.style.setProperty('background-color', subtleBackground, 'important');
                el.style.setProperty('color', color, 'important');
            });
        });
        
        // TOGGLES - Change from blue to primary color
        const toggleSelectors = [
            '[role="switch"][data-state="checked"]',
            '.fi-toggle-switch[data-state="checked"]',
            'button[role="switch"][data-state="checked"]'
        ];
        
        toggleSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                el.style.setProperty('background-color', color, 'important');
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
    
    // Watch for new elements (for dynamic content)
    const observer = new MutationObserver(function() {
        setTimeout(applyAcademyColors, 50);
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
    .fi-btn-color-success,
    button.fi-btn-color-success {
        background-color: {{ $brandColor }} !important;
        color: white !important; /* Ensure white text on primary background */
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
    
    /* EXCLUDE ELEMENTS - Explicitly prevent coloring */
    input[type="checkbox"],
    input[type="radio"],
    .fi-stats-overview,
    .fi-stats-overview .fi-color-primary {
        background-color: transparent !important;
        background: transparent !important;
    }
    
    /* Text-only primary elements (no background) */
    .fi-color-primary:not(.fi-btn):not(.fi-ac-btn):not(button):not(.fi-badge) {
        color: {{ $brandColor }} !important;
        background-color: transparent !important;
    }
    
    /* SUBTLE BACKGROUND ELEMENTS - Labels with borders get subtle primary shade */
    .fi-badge.fi-color-primary,
    .fi-color-primary[class*="border"],
    span.fi-color-primary[class*="border"] {
        background-color: rgba({{ hexdec(substr(ltrim($brandColor, '#'), 0, 2)) }}, {{ hexdec(substr(ltrim($brandColor, '#'), 2, 2)) }}, {{ hexdec(substr(ltrim($brandColor, '#'), 4, 2)) }}, 0.1) !important;
        color: {{ $brandColor }} !important;
        border-color: {{ $brandColor }} !important;
    }
    
    /* TOGGLES - Change from blue to primary color */
    [role="switch"][data-state="checked"],
    .fi-toggle-switch[data-state="checked"],
    button[role="switch"][data-state="checked"],
    [data-state="checked"] .fi-toggle-switch-thumb {
        background-color: {{ $brandColor }} !important;
    }
    
    /* Additional toggle targeting for Filament switches */
    [class*="fi-toggle"] [data-state="checked"],
    [class*="toggle"] [data-state="checked"] {
        background-color: {{ $brandColor }} !important;
    }
</style>