@php
    use App\Services\AcademyContextService;
    $currentAcademy = AcademyContextService::getCurrentAcademy();
    $brandColor = $currentAcademy?->brand_color ?? '#0ea5e9';
@endphp

{{-- SIMPLE Academy Branding System - Using Direct JavaScript/CSS --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Apply academy color to all primary elements
    function applyAcademyColors() {
        const color = '{{ $brandColor }}';
        
        // Get all elements that should have the academy color
        const selectors = [
            '.fi-sidebar-nav-item.fi-active',
            '.fi-sidebar-nav-item-active', 
            '.fi-btn.fi-color-primary',
            '.fi-ac-btn.fi-color-primary',
            'button[type="submit"]:not(.fi-btn-color-gray)',
            '.bg-primary-500',
            '.text-primary-500',
            '.border-primary-500',
            '[class*="bg-primary-5"]',
            '[class*="text-primary-5"]',
            '[class*="border-primary-5"]',
            '[class*="fi-color-primary"]',
            '[class*="fi-btn-color-primary"]'
        ];
        
        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                if (selector.includes('bg-') || selector.includes('fi-btn') || el.tagName === 'BUTTON') {
                    el.style.setProperty('background-color', color, 'important');
                } else if (selector.includes('text-')) {
                    el.style.setProperty('color', color, 'important');
                } else if (selector.includes('border-')) {
                    el.style.setProperty('border-color', color, 'important');
                }
            });
        });
        
        // Also update CSS custom properties
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
    
    /* Direct CSS overrides for academy branding */
    .fi-sidebar-nav-item.fi-active,
    .fi-sidebar-nav-item-active,
    .fi-btn.fi-color-primary,
    .fi-ac-btn.fi-color-primary,
    button[type="submit"]:not(.fi-btn-color-gray),
    .bg-primary-500,
    [class*="bg-primary-5"] {
        background-color: {{ $brandColor }} !important;
    }
    
    .text-primary-500,
    [class*="text-primary-5"] {
        color: {{ $brandColor }} !important;
    }
    
    .border-primary-500,
    [class*="border-primary-5"] {
        border-color: {{ $brandColor }} !important;
    }
    
    /* Focus states */
    .focus\:ring-primary-500:focus,
    [class*="focus:ring-primary-5"]:focus {
        --tw-ring-color: {{ $brandColor }} !important;
    }
    
    /* Filament specific overrides */
    [class*="fi-color-primary"],
    [class*="fi-btn-color-primary"] {
        background-color: {{ $brandColor }} !important;
    }
</style>