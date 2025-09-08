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
            if (document.querySelector(".testimonials-carousel")) {
                const carousel = document.querySelector("#testimonials-track");
                const dots = document.querySelectorAll(".carousel-dot");
                const prevBtn = document.querySelector("#carousel-prev");
                const nextBtn = document.querySelector("#carousel-next");
                let currentIndex = 0;
                let isAnimating = false;
                const totalItems = carousel.children.length;

                // Determine items per view based on screen size
                function getItemsPerView() {
                    const viewportWidth = window.innerWidth;
                    if (viewportWidth >= 1024) {
                        return 3; // Large screens: 3 items
                    } else if (viewportWidth >= 640) {
                        return 2; // Medium screens: 2 items
                    } else {
                        return 1; // Small screens: 1 item
                    }
                }

                let itemsPerView = getItemsPerView();

                // Update carousel position and dots
                function updateCarousel() {
                    if (isAnimating) return;
                    isAnimating = true;

                    // Calculate item width based on responsive classes
                    const itemsPerView = getItemsPerView();
                    const itemWidth = 100 / itemsPerView; // Percentage width per item
                    const translateX = currentIndex * itemWidth; // Scroll 1 item at a time

                    carousel.style.transition = "transform 0.3s ease-in-out";
                    carousel.style.transform = `translateX(${translateX}%)`;

                    // Update dots - show active dot based on current position
                    const currentDots = document.querySelectorAll(".carousel-dot");
                    currentDots.forEach((dot, index) => {
                        if (index === currentIndex) {
                            dot.classList.add("bg-primary");
                            dot.classList.remove("bg-primary/20");
                        } else {
                            dot.classList.remove("bg-primary");
                            dot.classList.add("bg-primary/20");
                        }
                    });

                    setTimeout(() => {
                        isAnimating = false;
                    }, 300);
                }

                // Handle next button - infinite scroll
                function handleNext() {
                    if (isAnimating) return;
                    const maxIndex = calculateDotCount() - 1;
                    currentIndex = (currentIndex + 1) % (maxIndex + 1);
                    updateCarousel();
                }

                function handlePrev() {
                    if (isAnimating) return;
                    const maxIndex = calculateDotCount() - 1;
                    currentIndex = (currentIndex - 1 + (maxIndex + 1)) % (maxIndex + 1);
                    updateCarousel();
                }

                // Add event listeners for navigation buttons (reversed for RTL)
                nextBtn.addEventListener("click", handleNext); // Left arrow goes to previous in RTL
                prevBtn.addEventListener("click", handlePrev); // Right arrow goes to next in RTL

                // Dot navigation - update after dots are recreated
                function updateDotListeners() {
                    const updatedDots = document.querySelectorAll(".carousel-dot");
                    updatedDots.forEach((dot, index) => {
                        // Remove existing listeners to prevent duplicates
                        dot.replaceWith(dot.cloneNode(true));
                    });

                    // Re-add listeners to new elements
                    const newDots = document.querySelectorAll(".carousel-dot");
                    newDots.forEach((dot, index) => {
                        dot.addEventListener("click", () => {
                            if (isAnimating) return;
                            currentIndex = index;
                            updateCarousel();
                        });
                    });
                }

                // Calculate appropriate number of dots based on screen size and total items
                function calculateDotCount() {
                    const itemsPerView = getItemsPerView();
                    // Calculate how many positions we can move to without showing empty space
                    const maxPositions = Math.max(1, totalItems - itemsPerView + 1);
                    return maxPositions;
                }

                // Update dot count based on calculated positions
                function updateDotCount() {
                    const dotContainer = document.getElementById('carousel-dots');
                    const currentDots = dotContainer.querySelectorAll('.carousel-dot');
                    const requiredDots = calculateDotCount();

                    // Remove extra dots
                    for (let i = currentDots.length - 1; i >= requiredDots; i--) {
                        currentDots[i].remove();
                    }

                    // Add missing dots
                    for (let i = currentDots.length; i < requiredDots; i++) {
                        const dot = document.createElement('button');
                        dot.className = 'carousel-dot w-3 h-3 rounded-full bg-primary/20 transition-all duration-300';
                        dot.setAttribute('data-index', i);
                        dotContainer.appendChild(dot);
                    }

                    // Update dot listeners after recreating dots
                    updateDotListeners();

                    // Apply active state to current dot after recreation
                    setTimeout(() => {
                        const newDots = document.querySelectorAll(".carousel-dot");
                        newDots.forEach((dot, index) => {
                            if (index === currentIndex) {
                                dot.classList.add("bg-primary");
                                dot.classList.remove("bg-primary/20");
                            } else {
                                dot.classList.remove("bg-primary");
                                dot.classList.add("bg-primary/20");
                            }
                        });
                    }, 10);
                }

                // Initial dot count setup
                updateDotCount();

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
                const carouselContainer = document.querySelector(".testimonials-carousel");
                carouselContainer.addEventListener("mouseenter", stopAutoplay);
                carouselContainer.addEventListener("mouseleave", startAutoplay);

                // Handle window resize
                window.addEventListener('resize', () => {
                    const newItemsPerView = getItemsPerView();
                    if (newItemsPerView !== itemsPerView) {
                        itemsPerView = newItemsPerView;
                        // Update dot count for new screen size
                        updateDotCount();
                        // Adjust current index if needed
                        const maxIndex = calculateDotCount() - 1;
                        if (currentIndex > maxIndex) {
                            currentIndex = maxIndex;
                        }
                        updateCarousel();
                    }
                });

                // Initialize
                updateCarousel();
                startAutoplay();
            }
        });
    </script> 