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
      threshold: 0.5,
      rootMargin: "0px 0px -100px 0px",
    };
    
    const observer = new IntersectionObserver(function (entries) {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const counter = entry.target;
          const finalValue = parseInt(counter.dataset.target) || 0;
          const increment = Math.ceil(finalValue / 50);
          let currentValue = 0;
          
          const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= finalValue) {
              currentValue = finalValue;
              clearInterval(timer);
            }
            
            if (counter.textContent.includes("+")) {
              counter.textContent = currentValue.toLocaleString() + "+";
            } else if (counter.textContent.includes("%")) {
              counter.textContent = currentValue + "%";
            } else {
              counter.textContent = currentValue.toLocaleString();
            }
          }, 30);
          
          observer.unobserve(counter);
        }
      });
    }, observerOptions);
    
    statsCounters.forEach((counter) => {
      observer.observe(counter);
    });
  });
</script> 