import './bootstrap';
// Icon libraries imported via JS so Vite resolves font/svg url() references properly
// (CSS @import was processed by PostCSS/TailwindCSS which lost the URL resolution context)
import 'remixicon/fonts/remixicon.css';
import 'flag-icons/css/flag-icons.min.css';
import '../css/app.css';
import Alpine from 'alpinejs';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import tabsComponent from './components/tabs';
import { initStickySidebar } from './components/sticky-sidebar';
import { getCsrfToken, getCsrfHeaders, csrfFetch } from './utils/csrf';
import { LABELS_AR, CSS_CLASSES, ICONS } from './utils/constants';
import './components/navigation';
import './components/file-upload';

// Chart.js - bundled via Vite (exposes window.Chart)
import './chart-init';

// intl-tel-input - bundled via Vite (exposes window.intlTelInput)
import './phone-input';

// Alpine.js - Initialize only if Livewire hasn't already done so
// Livewire 3 with inject_assets: true bundles its own Alpine.
// For pages without Livewire, we need to start Alpine ourselves.
if (!window.Alpine) {
    window.Alpine = Alpine;
    // Start Alpine after a short delay to allow for any late initialization
    document.addEventListener('DOMContentLoaded', () => {
        // Double-check Livewire hasn't started Alpine in the meantime
        if (!window.Alpine._started) {
            window.Alpine.start();
        }
    });
}

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

// Make components globally available
// Alpine evaluates x-data expressions in global scope, so these must exist on window
window.sidebarState = sidebarState;
window.tabsComponent = tabsComponent;

// Register with Alpine.data for named component access
// Alpine is bundled with Livewire 3 (inject_assets: true in config/livewire.php)
function registerAlpineComponents() {
    if (window.Alpine && !window.Alpine._registeredComponents) {
        window.Alpine.data('tabsComponent', tabsComponent);
        window.Alpine.data('sidebarState', sidebarState);
        window.Alpine._registeredComponents = true;
    }
}

// Register immediately if Alpine is already available
registerAlpineComponents();

// Listen for alpine:init (fires BEFORE Alpine processes DOM)
// This is the primary registration point since Livewire bundles Alpine
document.addEventListener('alpine:init', registerAlpineComponents);

// ========================================
// Centralized Attendance Status Helper
// ========================================
// Single source of truth for attendance status display
// Matches backend AttendanceStatus enum in app/Enums/AttendanceStatus.php
// Uses centralized constants from utils/constants.js for localization
window.AttendanceStatus = {
    statuses: {
        'attended': { label: LABELS_AR.ATTENDANCE.ATTENDED, class: CSS_CLASSES.ATTENDANCE.ATTENDED, icon: ICONS.ATTENDANCE.ATTENDED },
        'late': { label: LABELS_AR.ATTENDANCE.LATE, class: CSS_CLASSES.ATTENDANCE.LATE, icon: ICONS.ATTENDANCE.LATE },
        'left': { label: LABELS_AR.ATTENDANCE.LEFT, class: CSS_CLASSES.ATTENDANCE.LEFT, icon: ICONS.ATTENDANCE.LEFT },
        'absent': { label: LABELS_AR.ATTENDANCE.ABSENT, class: CSS_CLASSES.ATTENDANCE.ABSENT, icon: ICONS.ATTENDANCE.ABSENT }
    },
    getLabel: (status) => window.AttendanceStatus.statuses[status]?.label || status,
    getBadgeClass: (status) => window.AttendanceStatus.statuses[status]?.class || 'bg-gray-100 text-gray-800',
    getIcon: (status) => window.AttendanceStatus.statuses[status]?.icon || 'ri-question-line',
    getAllStatuses: () => Object.keys(window.AttendanceStatus.statuses),
    isValid: (status) => status in window.AttendanceStatus.statuses
};
