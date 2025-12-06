/**
 * Sticky Sidebar - Using StickySidebar Library
 * https://github.com/abouolia/sticky-sidebar
 * Pure JavaScript, high performance sticky sidebar
 */

import StickySidebar from 'sticky-sidebar-v2';

export function initStickySidebar() {
    try {
        const containers = document.querySelectorAll('[data-sticky-container]');
        if (!containers.length) return;

        function getHeaderHeight() {
            const header = document.querySelector('header') || document.querySelector('nav');
            return header ? header.offsetHeight : 0;
        }

        const instances = [];

        containers.forEach(container => {
            try {
                const sidebar = container.querySelector('[data-sticky-sidebar]');
                if (!sidebar) return;

                const headerHeight = getHeaderHeight();
                const topGap = headerHeight + 24;

                // Initialize StickySidebar with error handling
                const instance = new StickySidebar(sidebar, {
                    containerSelector: '[data-sticky-container]',
                    topSpacing: topGap,
                    bottomSpacing: 24,
                    resizeSensor: true,
                    stickyClass: 'is-affixed',
                    minWidth: 1024 // Only enable on desktop (lg breakpoint)
                });

                instances.push(instance);

                // Store destroy function
                sidebar._stickySidebarDestroy = () => {
                    try {
                        instance.destroy();
                    } catch (error) {
                        console.error('[Sticky Sidebar] Error destroying instance:', error);
                    }
                };
            } catch (error) {
                console.error('[Sticky Sidebar] Error initializing sidebar:', error);
            }
        });

        // Store instances for later cleanup
        return instances;
    } catch (error) {
        console.error('[Sticky Sidebar] Fatal error during initialization:', error);
        return [];
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initStickySidebar);
} else {
    initStickySidebar();
}

document.addEventListener('livewire:navigated', () => {
    try {
        document.querySelectorAll('[data-sticky-sidebar]').forEach(sidebar => {
            sidebar._stickySidebarDestroy?.();
        });
        initStickySidebar();
    } catch (error) {
        console.error('[Sticky Sidebar] Error during Livewire navigation:', error);
    }
});

export default initStickySidebar;
