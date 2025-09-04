import './bootstrap';
import '../css/app.css';
import AOS from 'aos';
import 'aos/dist/aos.css';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

// Initialize AOS
AOS.init({
    duration: 1000,
    once: true,
    offset: 100
});

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger);
