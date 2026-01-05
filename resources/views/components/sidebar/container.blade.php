@props([
    'sidebarId',
    'storageKey' => 'sidebarCollapsed',
])

<!-- Sidebar Container - Hidden on mobile, visible on md+ screens -->
<aside id="{{ $sidebarId }}"
       x-data="sidebarState('{{ $storageKey }}')"
       :class="{
           'w-80': !collapsed,
           'w-20': collapsed
       }"
       class="hidden md:block fixed top-20 h-[calc(100vh-5rem)] bg-white shadow-lg border-t border-gray-200 z-40 transition-all duration-300 ease-in-out"
       role="complementary"
       aria-label="{{ __('components.sidebar.aria_label') }}">

    <!-- Collapse Toggle Button (Desktop Only) -->
    <button @click="toggleCollapse()"
            class="hidden md:flex absolute top-4 z-50 p-2 bg-white opacity-70 hover:opacity-100 transition-all duration-300 border border-gray-200 items-center justify-center min-h-[40px] min-w-[40px]"
            aria-label="{{ __('components.sidebar.toggle_label') }}">
        @if(app()->getLocale() === 'ar')
            <i :class="collapsed ? 'ri-menu-fold-line' : 'ri-menu-unfold-line'" class="text-lg text-gray-600"></i>
        @else
            <i :class="collapsed ? 'ri-menu-unfold-line' : 'ri-menu-fold-line'" class="text-lg text-gray-600"></i>
        @endif
    </button>

    <!-- Scrollable Content Container -->
    <div class="h-full overflow-y-auto sidebar-scrollable pt-2 md:pt-0">
        <div :class="{ 'sidebar-collapsed-content': collapsed }">
            {{ $slot }}
        </div>
    </div>

</aside>

<!-- Tooltip Container (Desktop Collapsed Mode) -->
<div id="{{ $sidebarId }}-tooltip"
     x-data="{ show: false, text: '', top: 0 }"
     x-show="show"
     x-transition
     :style="{ top: top + 'px' }"
     class="fixed z-[60] px-3 py-2 text-sm text-white bg-gray-900 rounded-lg shadow-lg pointer-events-none">
    <span x-text="text"></span>
</div>

<style>
    /* Dynamic Content Wrapper - responds to sidebar state */
    .dynamic-content-wrapper {
        transition: max-width 0.3s ease-in-out;
    }

    /* When sidebar is expanded (default) - constrained width */
    body:not(.sidebar-collapsed) .dynamic-content-wrapper {
        max-width: 80rem; /* max-w-7xl */
        margin-left: auto;
        margin-right: auto;
    }

    /* When sidebar is collapsed - fluid width */
    body.sidebar-collapsed .dynamic-content-wrapper {
        max-width: 100%;
        margin-left: 1rem;
        margin-right: 1rem;
    }

    @media (min-width: 640px) {
        body.sidebar-collapsed .dynamic-content-wrapper {
            margin-left: 1.5rem;
            margin-right: 1.5rem;
        }
    }

    @media (min-width: 1024px) {
        body.sidebar-collapsed .dynamic-content-wrapper {
            margin-left: 2rem;
            margin-right: 2rem;
        }
    }

    /* Custom Scrollbar Styling */
    .sidebar-scrollable {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }

    .sidebar-scrollable::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-scrollable::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .sidebar-scrollable::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .sidebar-scrollable::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Sidebar collapsed content styles */
    .sidebar-collapsed-content #profile-section {
        padding: 1rem 0.5rem;
    }

    .sidebar-collapsed-content #profile-info,
    .sidebar-collapsed-content #student-info,
    .sidebar-collapsed-content .teacher-info {
        display: none;
    }

    .sidebar-collapsed-content .nav-text,
    .sidebar-collapsed-content h4,
    .sidebar-collapsed-content .nav-section-title {
        display: none;
    }

    .sidebar-collapsed-content .nav-item {
        justify-content: center;
        padding: 0.75rem;
        min-height: 50px;
    }

    .sidebar-collapsed-content .nav-item i {
        margin: 0;
        font-size: 1.25rem;
    }

    .sidebar-collapsed-content .mb-6 {
        margin-bottom: 0.5rem;
    }

    .sidebar-collapsed-content .nav-section {
        padding: 0.25rem;
    }
</style>

<script>
// Sidebar state component - fallback for pages not using Vite bundle
// If using Vite bundle, sidebarState is already registered in app.js
(function() {
    const sidebarStateComponent = (storageKey) => ({
        collapsed: localStorage.getItem(storageKey) === 'true',

        init() {
            // Store reference for other components
            if (window.Alpine && window.Alpine.store) {
                window.Alpine.store('sidebar', {
                    collapsed: this.collapsed
                });
            }

            // Watch for collapse changes
            this.$watch('collapsed', (value) => {
                if (window.Alpine && window.Alpine.store) {
                    window.Alpine.store('sidebar').collapsed = value;
                }
                // Update main content margin and body class on desktop
                this.updateMainContentMargin();
                this.updateBodyClass();
            });

            // Initial updates
            this.updateMainContentMargin();
            this.updateBodyClass();
        },

        toggleCollapse() {
            this.collapsed = !this.collapsed;
            localStorage.setItem(storageKey, this.collapsed);
        },

        updateMainContentMargin() {
            // Main content margins are now handled by CSS based on dir attribute
            // and the sidebar-collapsed class on body
            // This function is kept for compatibility but does nothing
        },

        updateBodyClass() {
            if (this.collapsed) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }
    });

    // Make available globally (for x-data evaluation)
    if (!window.sidebarState) {
        window.sidebarState = sidebarStateComponent;
    }

    // Register with Alpine.data if Alpine is available and not already registered
    function registerSidebarState() {
        if (window.Alpine && !window.Alpine._registeredSidebarStateInline) {
            window.Alpine.data('sidebarState', sidebarStateComponent);
            window.Alpine._registeredSidebarStateInline = true;
        }
    }

    // Try to register immediately
    registerSidebarState();

    // Also listen for alpine:init (for pages loading Alpine later)
    document.addEventListener('alpine:init', registerSidebarState);
})();

// Tooltip functionality for collapsed sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('{{ $sidebarId }}');
    const tooltip = document.getElementById('{{ $sidebarId }}-tooltip');

    if (!sidebar || !tooltip) return;

    let hideTimeout = null;

    // Get Alpine.js data from tooltip element
    function getTooltipData() {
        return Alpine.$data(tooltip);
    }

    function showTooltip(navItem) {
        // Cancel any pending hide
        if (hideTimeout) {
            clearTimeout(hideTimeout);
            hideTimeout = null;
        }

        const tooltipContent = navItem.getAttribute('data-tooltip');
        if (tooltipContent) {
            const rect = navItem.getBoundingClientRect();
            const tooltipData = getTooltipData();
            if (tooltipData) {
                tooltipData.text = tooltipContent;
                tooltipData.top = rect.top + (rect.height / 2) - 16;
                tooltipData.show = true;
            }
        }
    }

    function hideTooltip() {
        // Cancel any pending hide first
        if (hideTimeout) {
            clearTimeout(hideTimeout);
        }
        // Schedule hide with small delay
        hideTimeout = setTimeout(() => {
            const tooltipData = getTooltipData();
            if (tooltipData) {
                tooltipData.show = false;
            }
            hideTimeout = null;
        }, 150);
    }

    // Use mouseover/mouseout for proper event delegation (they bubble)
    sidebar.addEventListener('mouseover', function(e) {
        const navItem = e.target.closest('.nav-item');
        if (!navItem) return;

        const isCollapsed = sidebar.classList.contains('w-20');
        if (!isCollapsed || window.innerWidth < 768) return;

        showTooltip(navItem);
    });

    sidebar.addEventListener('mouseout', function(e) {
        const navItem = e.target.closest('.nav-item');
        const relatedTarget = e.relatedTarget;

        // Check if we're moving to another nav-item
        const targetNavItem = relatedTarget ? relatedTarget.closest('.nav-item') : null;

        // Only schedule hide if we're not moving to another nav-item
        if (navItem && !targetNavItem) {
            hideTooltip();
        }
    });
});
</script>
