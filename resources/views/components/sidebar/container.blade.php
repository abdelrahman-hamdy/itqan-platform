@props([
    'sidebarId',
    'storageKey' => 'sidebarCollapsed',
])

<!-- Sidebar Container -->
<aside id="{{ $sidebarId }}"
       x-data="sidebarState('{{ $storageKey }}')"
       :class="{
           'w-80': !collapsed,
           'w-20': collapsed,
           'translate-x-full': !mobileOpen,
           'translate-x-0': mobileOpen
       }"
       class="fixed right-0 top-20 h-[calc(100vh-5rem)] bg-white shadow-lg border-l border-t border-gray-200 z-40 transition-all duration-300 ease-in-out md:translate-x-0"
       role="complementary"
       aria-label="قائمة جانبية"
       @toggle-mobile-sidebar.window="mobileOpen = !mobileOpen"
       @close-mobile-sidebar.window="mobileOpen = false">

    <!-- Collapse Toggle Button (Desktop Only) -->
    <button @click="toggleCollapse()"
            class="hidden md:flex absolute top-4 -left-10 z-50 p-2 bg-white opacity-70 hover:opacity-100 rounded-r-lg transition-all duration-300 border border-r-0 border-gray-200 items-center justify-center min-h-[40px] min-w-[40px]"
            aria-label="طي/فتح القائمة الجانبية">
        <i :class="collapsed ? 'ri-menu-unfold-line' : 'ri-menu-fold-line'" class="text-lg text-gray-600"></i>
    </button>

    <!-- Close Button (Mobile Only) -->
    <button @click="mobileOpen = false"
            class="md:hidden absolute top-4 left-4 z-50 p-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors min-h-[44px] min-w-[44px] flex items-center justify-center"
            aria-label="إغلاق القائمة">
        <i class="ri-close-line text-xl text-gray-600"></i>
    </button>

    <!-- Scrollable Content Container -->
    <div class="h-full overflow-y-auto sidebar-scrollable pt-2 md:pt-0">
        <div :class="{ 'sidebar-collapsed-content': collapsed }">
            {{ $slot }}
        </div>
    </div>

</aside>

<!-- Mobile Sidebar Overlay -->
<div x-data
     x-show="$store.sidebar?.mobileOpen"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="$dispatch('close-mobile-sidebar')"
     class="fixed inset-0 bg-black/50 z-30 md:hidden"
     id="{{ $sidebarId }}-overlay"></div>

<!-- Tooltip Container (Desktop Collapsed Mode) -->
<div id="{{ $sidebarId }}-tooltip"
     x-data="{ show: false, text: '', top: 0 }"
     x-show="show"
     x-transition
     :style="{ top: top + 'px' }"
     class="fixed z-[60] right-24 px-3 py-2 text-sm text-white bg-gray-900 rounded-lg shadow-lg pointer-events-none">
    <span x-text="text"></span>
</div>

<style>
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
document.addEventListener('alpine:init', () => {
    Alpine.data('sidebarState', (storageKey) => ({
        collapsed: localStorage.getItem(storageKey) === 'true',
        mobileOpen: false,

        init() {
            // Store reference for overlay access
            Alpine.store('sidebar', {
                mobileOpen: this.mobileOpen,
                collapsed: this.collapsed
            });

            // Watch for changes
            this.$watch('mobileOpen', (value) => {
                Alpine.store('sidebar').mobileOpen = value;
                // Prevent body scroll when mobile sidebar is open
                document.body.style.overflow = value ? 'hidden' : '';
            });

            this.$watch('collapsed', (value) => {
                Alpine.store('sidebar').collapsed = value;
                // Update main content margin on desktop
                this.updateMainContentMargin();
            });

            // Initial margin update
            this.updateMainContentMargin();

            // Listen for mobile toggle from external button
            const mobileToggle = document.getElementById('sidebar-toggle-mobile');
            if (mobileToggle) {
                mobileToggle.addEventListener('click', () => {
                    this.mobileOpen = !this.mobileOpen;
                });
            }
        },

        toggleCollapse() {
            this.collapsed = !this.collapsed;
            localStorage.setItem(storageKey, this.collapsed);
        },

        updateMainContentMargin() {
            const mainContent = document.getElementById('main-content');
            if (mainContent && window.innerWidth >= 768) {
                // Use CSS class-based approach instead of inline styles
                mainContent.classList.remove('md:mr-80', 'md:mr-20');
                mainContent.classList.add(this.collapsed ? 'md:mr-20' : 'md:mr-80');
            }
        }
    }));
});

// Tooltip functionality for collapsed sidebar
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('{{ $sidebarId }}');
    const tooltip = document.getElementById('{{ $sidebarId }}-tooltip');

    if (!sidebar || !tooltip) return;

    // Use event delegation for nav items
    sidebar.addEventListener('mouseenter', function(e) {
        const navItem = e.target.closest('.nav-item');
        if (!navItem) return;

        const isCollapsed = sidebar.classList.contains('w-20');
        if (!isCollapsed || window.innerWidth < 768) return;

        const tooltipContent = navItem.getAttribute('data-tooltip');
        if (tooltipContent) {
            const rect = navItem.getBoundingClientRect();
            tooltip.__x.$data.text = tooltipContent;
            tooltip.__x.$data.top = rect.top + (rect.height / 2) - 16;
            tooltip.__x.$data.show = true;
        }
    }, true);

    sidebar.addEventListener('mouseleave', function(e) {
        const navItem = e.target.closest('.nav-item');
        if (navItem && tooltip.__x) {
            setTimeout(() => {
                tooltip.__x.$data.show = false;
            }, 100);
        }
    }, true);
});
</script>
