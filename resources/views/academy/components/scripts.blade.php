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
        if (!carouselContainer) return;

        const track = document.querySelector("#testimonials-track");
        const prevBtn = document.querySelector("#carousel-prev");
        const nextBtn = document.querySelector("#carousel-next");
        const dotContainer = document.getElementById('carousel-dots');
        const items = track.querySelectorAll('.carousel-item');

        if (!track || !items.length) return;

        let currentIndex = 0;
        let isAnimating = false;
        const totalItems = items.length;

        // Get brand colors from data attributes
        const brandColor = carouselContainer.dataset.brandColor || '#0ea5e9';
        const brandColorLight = carouselContainer.dataset.brandColorLight || '#bae6fd';

        // Get items per view based on screen width
        function getItemsPerView() {
            const width = window.innerWidth;
            if (width >= 1024) return 3; // lg: 3 items
            if (width >= 768) return 2;  // md: 2 items
            return 1; // mobile: 1 item
        }

        // Get max index for navigation
        function getMaxIndex() {
            return Math.max(0, totalItems - getItemsPerView());
        }

        // Update carousel position
        function updateCarousel() {
            if (isAnimating) return;
            isAnimating = true;

            const itemsPerView = getItemsPerView();
            const maxIndex = getMaxIndex();

            // Clamp current index
            currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));

            // Calculate percentage to translate
            // Each item is (100 / itemsPerView)% of the container width
            const itemWidthPercent = 100 / itemsPerView;
            const translatePercent = currentIndex * itemWidthPercent;

            // Apply RTL-aware translation
            const isRTL = document.documentElement.dir === 'rtl';
            if (isRTL) {
                track.style.transform = `translateX(${translatePercent}%)`;
            } else {
                track.style.transform = `translateX(-${translatePercent}%)`;
            }

            updateDots();
            updateButtons();

            setTimeout(() => {
                isAnimating = false;
            }, 350);
        }

        // Update navigation button states
        function updateButtons() {
            const maxIndex = getMaxIndex();
            if (prevBtn) {
                prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
                prevBtn.style.cursor = currentIndex === 0 ? 'default' : 'pointer';
            }
            if (nextBtn) {
                nextBtn.style.opacity = currentIndex >= maxIndex ? '0.5' : '1';
                nextBtn.style.cursor = currentIndex >= maxIndex ? 'default' : 'pointer';
            }
        }

        // Update dots
        function updateDots() {
            if (!dotContainer) return;
            const dots = dotContainer.querySelectorAll('.carousel-dot');
            const maxIndex = getMaxIndex();

            dots.forEach((dot, index) => {
                const isActive = index === currentIndex;
                dot.style.backgroundColor = isActive ? brandColor : brandColorLight;
                dot.style.transform = isActive ? 'scale(1.3)' : 'scale(1)';
            });
        }

        // Create dots based on max index
        function createDots() {
            if (!dotContainer) return;

            const maxIndex = getMaxIndex();
            const numDots = maxIndex + 1;

            dotContainer.innerHTML = '';

            for (let i = 0; i < numDots; i++) {
                const dot = document.createElement('button');
                dot.className = 'carousel-dot w-3 h-3 rounded-full transition-all duration-300 cursor-pointer';
                dot.style.backgroundColor = i === currentIndex ? brandColor : brandColorLight;
                if (i === currentIndex) dot.style.transform = 'scale(1.3)';
                dot.setAttribute('aria-label', `Go to position ${i + 1}`);
                dot.addEventListener('click', () => {
                    if (isAnimating || i === currentIndex) return;
                    currentIndex = i;
                    updateCarousel();
                });
                dotContainer.appendChild(dot);
            }
        }

        // Navigation handlers
        function goNext() {
            if (isAnimating) return;
            const maxIndex = getMaxIndex();
            if (currentIndex < maxIndex) {
                currentIndex++;
                updateCarousel();
            }
        }

        function goPrev() {
            if (isAnimating) return;
            if (currentIndex > 0) {
                currentIndex--;
                updateCarousel();
            }
        }

        // Event listeners
        if (nextBtn) nextBtn.addEventListener('click', goNext);
        if (prevBtn) prevBtn.addEventListener('click', goPrev);

        // Autoplay
        let autoplayInterval = null;
        function startAutoplay() {
            stopAutoplay();
            autoplayInterval = setInterval(() => {
                const maxIndex = getMaxIndex();
                if (currentIndex < maxIndex) {
                    currentIndex++;
                } else {
                    currentIndex = 0;
                }
                updateCarousel();
            }, 5000);
        }

        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                autoplayInterval = null;
            }
        }

        carouselContainer.addEventListener('mouseenter', stopAutoplay);
        carouselContainer.addEventListener('mouseleave', startAutoplay);

        // Handle resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                const maxIndex = getMaxIndex();
                if (currentIndex > maxIndex) {
                    currentIndex = maxIndex;
                }
                createDots();
                isAnimating = false;
                updateCarousel();
            }, 150);
        });

        // Touch/swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        track.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        }, { passive: true });

        track.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            const diff = touchStartX - touchEndX;
            const isRTL = document.documentElement.dir === 'rtl';

            if (Math.abs(diff) > 50) {
                if ((diff > 0 && !isRTL) || (diff < 0 && isRTL)) {
                    goNext();
                } else {
                    goPrev();
                }
            }
            startAutoplay();
        }, { passive: true });

        // Initialize
        createDots();
        updateCarousel();
        startAutoplay();
    });
</script> 