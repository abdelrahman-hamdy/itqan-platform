import './bootstrap';
import '../css/app.css';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import tabsComponent from './components/tabs';
import { initStickySidebar } from './components/sticky-sidebar';
import { getCsrfToken, getCsrfHeaders, csrfFetch } from './utils/csrf';
import './components/navigation';
import './components/file-upload';

// Expose CSRF utilities globally for public/js files
window.getCsrfToken = getCsrfToken;
window.getCsrfHeaders = getCsrfHeaders;
window.csrfFetch = csrfFetch;

// Initialize AOS
AOS.init({
    duration: 1000,
    once: true,
    offset: 100
});

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger);

// Initialize sticky sidebar
initStickySidebar();

// Make it globally available
window.initStickySidebar = initStickySidebar;

// Sidebar state component for collapsible sidebars
const sidebarState = (storageKey) => ({
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
        const mainContent = document.getElementById('main-content');
        if (mainContent && window.innerWidth >= 768) {
            // Use CSS class-based approach instead of inline styles
            mainContent.classList.remove('md:mr-80', 'md:mr-20');
            mainContent.classList.add(this.collapsed ? 'md:mr-20' : 'md:mr-80');
        }
    },

    updateBodyClass() {
        // Toggle body class for dynamic content wrapper
        if (this.collapsed) {
            document.body.classList.add('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
    }
});

// Make sidebarState globally available
window.sidebarState = sidebarState;

// Register Alpine components
// CRITICAL: Make tabsComponent available globally IMMEDIATELY so Alpine can find it
// Alpine evaluates x-data expressions in global scope, so window.tabsComponent must exist
window.tabsComponent = tabsComponent;

// Also register with Alpine.data for more complex scenarios
function registerAlpineComponents() {
    if (window.Alpine) {
        if (!window.Alpine._registeredTabsComponent) {
            window.Alpine.data('tabsComponent', tabsComponent);
            window.Alpine._registeredTabsComponent = true;
        }
        if (!window.Alpine._registeredSidebarState) {
            window.Alpine.data('sidebarState', sidebarState);
            window.Alpine._registeredSidebarState = true;
        }
    }
}

// Strategy 1: Register immediately if Alpine is already available
registerAlpineComponents();

// Strategy 2: Listen for alpine:init (fires BEFORE Alpine processes DOM)
document.addEventListener('alpine:init', registerAlpineComponents);

// Strategy 3: Listen for livewire:init (Livewire 3 event)
document.addEventListener('livewire:init', registerAlpineComponents);

// Strategy 4: Listen for livewire:navigated (for Livewire SPA navigation)
document.addEventListener('livewire:navigated', registerAlpineComponents);

// Strategy 5: Poll for Alpine if it loads asynchronously (fallback)
if (!window.Alpine) {
    const checkAlpine = setInterval(() => {
        if (window.Alpine) {
            clearInterval(checkAlpine);
            registerAlpineComponents();
        }
    }, 10);
    // Stop checking after 5 seconds
    setTimeout(() => clearInterval(checkAlpine), 5000);
}

// ========================================
// Centralized Attendance Status Helper
// ========================================
// Single source of truth for attendance status display
// Matches backend AttendanceStatus enum in app/Enums/AttendanceStatus.php
window.AttendanceStatus = {
    statuses: {
        'attended': { label: 'حاضر', class: 'bg-green-100 text-green-800', icon: 'ri-check-line' },
        'late': { label: 'متأخر', class: 'bg-yellow-100 text-yellow-800', icon: 'ri-time-line' },
        'left': { label: 'غادر مبكراً', class: 'bg-orange-100 text-orange-800', icon: 'ri-logout-box-line' },
        'absent': { label: 'غائب', class: 'bg-red-100 text-red-800', icon: 'ri-close-line' }
    },
    getLabel: (status) => window.AttendanceStatus.statuses[status]?.label || status,
    getBadgeClass: (status) => window.AttendanceStatus.statuses[status]?.class || 'bg-gray-100 text-gray-800',
    getIcon: (status) => window.AttendanceStatus.statuses[status]?.icon || 'ri-question-line',
    getAllStatuses: () => Object.keys(window.AttendanceStatus.statuses),
    isValid: (status) => status in window.AttendanceStatus.statuses
};
