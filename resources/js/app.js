import './bootstrap';
import '../css/app.css';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import tabsComponent from './components/tabs';
import { initStickySidebar } from './components/sticky-sidebar';

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

// Register Alpine components
// Check if Alpine is already available (loaded before this script)
if (window.Alpine) {
    console.log('[App] Alpine already loaded, registering tabs component immediately');
    window.Alpine.data('tabsComponent', tabsComponent);
} else {
    // Wait for Alpine to initialize via Livewire
    document.addEventListener('livewire:init', () => {
        console.log('[App] Livewire initialized, registering tabs component');
        window.Alpine.data('tabsComponent', tabsComponent);
    });

    // Fallback: Also listen for alpine:init in case Livewire isn't used
    document.addEventListener('alpine:init', () => {
        console.log('[App] Alpine initialized, registering tabs component');
        window.Alpine.data('tabsComponent', tabsComponent);
    });
}
