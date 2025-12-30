<script id="navigation-mobile">
  document.addEventListener("DOMContentLoaded", function () {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
      mobileMenuButton.addEventListener("click", function () {
        mobileMenu.classList.toggle("hidden");
        const isExpanded = !mobileMenu.classList.contains("hidden");
        mobileMenuButton.setAttribute("aria-expanded", isExpanded);
      });
    }
  });
</script>

<script id="smooth-scrolling">
  document.addEventListener("DOMContentLoaded", function () {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach((link) => {
      link.addEventListener("click", function (e) {
        e.preventDefault();
        const targetId = this.getAttribute("href");
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
          targetElement.scrollIntoView({
            behavior: "smooth",
            block: "start",
          });
        }
      });
    });
  });
</script>

<script id="enhanced-carousels">
  // Enhanced Carousel Functionality - Global function
  function initCarousel(config) {
    const track = document.querySelector(config.trackSelector);
    const items = document.querySelectorAll(config.itemsSelector);
    const prevBtn = document.querySelector(config.prevSelector);
    const nextBtn = document.querySelector(config.nextSelector);
    const dots = document.querySelectorAll(`${config.dotsSelector} .carousel-dot`);
    
    if (!track || !items.length) return;
    
    let currentIndex = 0;
    const itemsPerView = config.itemsPerView || 3;
    const maxIndex = Math.max(0, items.length - itemsPerView);
    
    function updateCarousel() {
      const translateX = -currentIndex * (config.itemWidth + config.itemGap);
      track.style.transform = `translateX(${translateX}px)`;
      
      // Update dots
      dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentIndex);
      });
      
      // Update button states
      if (prevBtn) prevBtn.disabled = currentIndex === 0;
      if (nextBtn) nextBtn.disabled = currentIndex >= maxIndex;
    }
    
    if (nextBtn) {
      nextBtn.addEventListener("click", () => {
        if (currentIndex < maxIndex) {
          currentIndex++;
          updateCarousel();
        }
      });
    }
    
    if (prevBtn) {
      prevBtn.addEventListener("click", () => {
        if (currentIndex > 0) {
          currentIndex--;
          updateCarousel();
        }
      });
    }
    
    // Dot navigation
    dots.forEach((dot, index) => {
      dot.addEventListener('click', () => {
        currentIndex = Math.min(index, maxIndex);
        updateCarousel();
      });
    });
    
    // Keyboard navigation
    track.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowLeft' && currentIndex > 0) {
        currentIndex--;
        updateCarousel();
      } else if (e.key === 'ArrowRight' && currentIndex < maxIndex) {
        currentIndex++;
        updateCarousel();
      }
    });
    
    updateCarousel();
  }

  document.addEventListener("DOMContentLoaded", function () {
    // Initialize all carousels
    initCarousel({
      trackSelector: '.circles-track',
      itemsSelector: '.circles-track > div',
      prevSelector: '.circle-prev',
      nextSelector: '.circle-next',
      dotsSelector: '#circles-dots',
      itemWidth: 320,
      itemGap: 32,
      itemsPerView: 3
    });
    
    initCarousel({
      trackSelector: '.teachers-track',
      itemsSelector: '.teachers-track > div',
      prevSelector: '.teacher-prev',
      nextSelector: '.teacher-next',
      dotsSelector: '#teachers-dots',
      itemWidth: 304,
      itemGap: 24,
      itemsPerView: 3
    });
    
    initCarousel({
      trackSelector: '.courses-track',
      itemsSelector: '.courses-track > div',
      prevSelector: '.course-prev',
      nextSelector: '.course-next',
      dotsSelector: '#courses-dots',
      itemWidth: 320,
      itemGap: 24,
      itemsPerView: 3
    });
    
    initCarousel({
      trackSelector: '.academic-teachers-track',
      itemsSelector: '.academic-teachers-track > div',
      prevSelector: '.academic-teacher-prev',
      nextSelector: '.academic-teacher-next',
      dotsSelector: '#academic-teachers-dots',
      itemWidth: 304,
      itemGap: 24,
      itemsPerView: 3
    });
  });
</script>

<script id="stats-animation">
  document.addEventListener("DOMContentLoaded", function () {
    const statsCounters = document.querySelectorAll(".stats-counter");
    const observerOptions = {
      threshold: 0.3,
      rootMargin: "0px 0px -50px 0px",
    };
    
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach((entry) => {
        if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
          const counter = entry.target;
          const finalValue = parseInt(counter.dataset.target) || 0;
          const duration = 2000; // 2 seconds
          const increment = Math.ceil(finalValue / (duration / 50));
          let currentValue = 0;
          
          // Mark as animated to prevent re-triggering
          counter.classList.add('animated');
          
          // Check if this is a percentage counter
          const isPercentage = finalValue <= 100 && counter.parentElement.querySelector('.stat-suffix')?.textContent === '%';
          
          const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
              currentValue = finalValue;
              clearInterval(timer);
            }
            
            // Format the number in English
            const displayValue = currentValue.toLocaleString('en-US');
            counter.textContent = displayValue;
          }, 50);
          
          observer.unobserve(counter);
        }
      });
    }, observerOptions);
    
    statsCounters.forEach((counter) => {
      observer.observe(counter);
    });
  });
</script>

<script id="testimonials-carousel">
        document.addEventListener("DOMContentLoaded", function () {
            const carouselContainer = document.querySelector(".testimonials-carousel");
            if (carouselContainer) {
                const carousel = document.querySelector("#testimonials-track");
                const prevBtn = document.querySelector("#carousel-prev");
                const nextBtn = document.querySelector("#carousel-next");
                const dotContainer = document.getElementById('carousel-dots');

                let currentSlide = 0; // Current slide index (0-based)
                let isAnimating = false;
                const totalItems = carousel.children.length;

                // Get brand colors from data attributes
                const brandColor = carouselContainer.dataset.brandColor || '#0ea5e9';
                const brandColorLight = carouselContainer.dataset.brandColorLight || '#bae6fd';

                // Get items per view from data attributes (with fallbacks)
                const itemsMobile = parseInt(carouselContainer.dataset.itemsMobile) || 1;
                const itemsTablet = parseInt(carouselContainer.dataset.itemsTablet) || 2;
                const itemsDesktop = parseInt(carouselContainer.dataset.itemsDesktop) || 3;

                // Calculate items per view based on responsive data attributes
                function getItemsPerView() {
                    const width = window.innerWidth;
                    // Match Tailwind breakpoints
                    if (width >= 1024) return itemsDesktop; // lg
                    if (width >= 768) return itemsTablet;   // md
                    return itemsMobile; // mobile
                }

                // Calculate total slides
                function getTotalSlides() {
                    const itemsPerView = getItemsPerView();
                    return Math.ceil(totalItems / itemsPerView);
                }

                // Update carousel position and dots
                function updateCarousel() {
                    if (isAnimating) return;
                    isAnimating = true;

                    const items = carousel.children;
                    if (!items.length) {
                        isAnimating = false;
                        return;
                    }

                    const itemsPerView = getItemsPerView();
                    const totalSlides = getTotalSlides();

                    // Clamp slide to valid range
                    currentSlide = Math.max(0, Math.min(currentSlide, totalSlides - 1));

                    // Calculate translateX by getting the offset of the target item
                    const targetItemIndex = currentSlide * itemsPerView;
                    const targetItem = items[Math.min(targetItemIndex, items.length - 1)];
                    const translateX = targetItem ? targetItem.offsetLeft : 0;

                    carousel.style.transition = "transform 0.4s ease-in-out";
                    carousel.style.transform = `translateX(-${translateX}px)`;

                    // Update dots
                    updateDots();

                    setTimeout(() => {
                        isAnimating = false;
                    }, 400);
                }

                // Update dot active states
                function updateDots() {
                    const dots = dotContainer.querySelectorAll('.carousel-dot');
                    dots.forEach((dot, index) => {
                        if (index === currentSlide) {
                            dot.style.backgroundColor = brandColor;
                            dot.style.transform = 'scale(1.2)';
                        } else {
                            dot.style.backgroundColor = brandColorLight;
                            dot.style.transform = 'scale(1)';
                        }
                    });
                }

                // Handle next button - go to next slide
                function handleNext() {
                    if (isAnimating) return;
                    const totalSlides = getTotalSlides();
                    if (currentSlide < totalSlides - 1) {
                        currentSlide++;
                    } else {
                        currentSlide = 0; // Loop back to start
                    }
                    updateCarousel();
                }

                // Handle prev button - go to previous slide
                function handlePrev() {
                    if (isAnimating) return;
                    const totalSlides = getTotalSlides();
                    if (currentSlide > 0) {
                        currentSlide--;
                    } else {
                        currentSlide = totalSlides - 1; // Loop to end
                    }
                    updateCarousel();
                }

                // Add event listeners for navigation buttons
                nextBtn.addEventListener("click", handleNext);
                prevBtn.addEventListener("click", handlePrev);

                // Create and update dots
                function createDots() {
                    if (!dotContainer) return;

                    const totalSlides = getTotalSlides();

                    // Clear existing dots
                    dotContainer.innerHTML = '';

                    // Create dots for each slide
                    for (let i = 0; i < totalSlides; i++) {
                        const dot = document.createElement('button');
                        dot.className = 'carousel-dot w-3 h-3 rounded-full transition-all duration-300 cursor-pointer';
                        dot.style.backgroundColor = i === currentSlide ? brandColor : brandColorLight;
                        if (i === currentSlide) dot.style.transform = 'scale(1.2)';
                        dot.setAttribute('aria-label', `Go to slide ${i + 1}`);
                        dot.addEventListener('click', () => {
                            if (isAnimating) return;
                            currentSlide = i;
                            updateCarousel();
                        });
                        dotContainer.appendChild(dot);
                    }
                }

                // Autoplay functionality
                let autoplayInterval = null;
                function startAutoplay() {
                    if (autoplayInterval) clearInterval(autoplayInterval);
                    autoplayInterval = setInterval(handleNext, 5000);
                }

                function stopAutoplay() {
                    if (autoplayInterval) {
                        clearInterval(autoplayInterval);
                        autoplayInterval = null;
                    }
                }

                // Pause autoplay on hover
                carouselContainer.addEventListener("mouseenter", stopAutoplay);
                carouselContainer.addEventListener("mouseleave", startAutoplay);

                // Handle window resize with debounce
                let resizeTimeout;
                window.addEventListener('resize', () => {
                    clearTimeout(resizeTimeout);
                    resizeTimeout = setTimeout(() => {
                        const totalSlides = getTotalSlides();

                        // Adjust current slide if needed
                        if (currentSlide >= totalSlides) {
                            currentSlide = totalSlides - 1;
                        }

                        createDots();
                        isAnimating = false;
                        updateCarousel();
                    }, 150);
                });

                // Initialize
                requestAnimationFrame(() => {
                    createDots();
                    updateCarousel();
                    startAutoplay();
                });
            }
        });
    </script> 