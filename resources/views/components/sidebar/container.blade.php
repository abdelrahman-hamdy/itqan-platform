@props([
    'sidebarId',
    'storageKey' => 'sidebarCollapsed',
])

<!-- Sidebar Container -->
<aside id="{{ $sidebarId }}" class="fixed right-0 top-20 h-screen bg-white shadow-lg border-l border-t border-gray-200 z-40 transform translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out"
       role="complementary"
       aria-label="قائمة جانبية">

  <!-- Collapse Toggle Button (Inside Sidebar) -->
  <button id="{{ $sidebarId }}-toggle" class="absolute top-4 z-50 p-2 bg-white opacity-70 hover:opacity-100 rounded-l transition-all duration-300 border-r border-gray-200"
          aria-label="طي/فتح القائمة الجانبية">
    <i id="{{ $sidebarId }}-toggle-icon" class="ri-menu-unfold-line text-lg text-gray-600"></i>
  </button>

  <!-- Scrollable Content Container -->
  <div class="h-full overflow-y-auto sidebar-scrollable">
    {{ $slot }}
  </div>

</aside>

<!-- Mobile Sidebar Overlay -->
<div class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden" id="{{ $sidebarId }}-overlay"></div>

<!-- Tooltip Container -->
<div id="{{ $sidebarId }}-tooltip" class="fixed z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg opacity-0 pointer-events-none transition-opacity duration-200">
  <span id="{{ $sidebarId }}-tooltip-text"></span>
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
    transition: background 0.2s ease;
  }

  .sidebar-scrollable::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  /* Sidebar collapsed state */
  .sidebar-collapsed {
    width: 80px !important;
  }

  .sidebar-collapsed #profile-section {
    padding: 1rem 0.5rem;
    height: auto;
  }

  .sidebar-collapsed #profile-content {
    margin-bottom: 0 !important;
  }

  .sidebar-collapsed #profile-content > div {
    margin-bottom: 0 !important;
  }

  .sidebar-collapsed #profile-info,
  .sidebar-collapsed #student-info,
  .sidebar-collapsed .teacher-info {
    display: none !important;
    height: 0;
    margin: 0;
    padding: 0;
    overflow: hidden;
  }

  .sidebar-collapsed .nav-text,
  .sidebar-collapsed h4 {
    display: none !important;
    height: 0;
    margin: 0;
    padding: 0;
    overflow: hidden;
  }

  .sidebar-collapsed .nav-item {
    justify-content: center;
    padding: 0;
    height: 50px;
    width: 100%;
    display: flex;
    align-items: center;
  }

  .sidebar-collapsed .nav-item i {
    margin: 0;
    font-size: 1.25rem;
    width: 100%;
    text-align: center;
  }

  .sidebar-collapsed .mb-6 {
    margin-bottom: 0.5rem;
  }

  /* Tooltip styles */
  #{{ $sidebarId }}-tooltip {
    font-size: 0.875rem;
    max-width: 200px;
    word-wrap: break-word;
    z-index: 60;
    transition: opacity 0.2s ease, transform 0.2s ease;
    transform: translateX(0);
  }

  /* Smooth transitions */
  .transition-all {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Toggle button positioning */
  #{{ $sidebarId }}-toggle {
    border-radius: 0;
    right: 100%;
    transition: right 0.3s ease;
  }

  .sidebar-collapsed #{{ $sidebarId }}-toggle {
    right: 100%;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('{{ $sidebarId }}');
  const toggleButton = document.getElementById('{{ $sidebarId }}-toggle');
  const toggleIcon = document.getElementById('{{ $sidebarId }}-toggle-icon');
  const overlay = document.getElementById('{{ $sidebarId }}-overlay');
  const tooltip = document.getElementById('{{ $sidebarId }}-tooltip');
  const tooltipText = document.getElementById('{{ $sidebarId }}-tooltip-text');
  const mainContent = document.getElementById('main-content');

  let isCollapsed = false;

  // Mobile sidebar toggle
  const mobileToggle = document.getElementById('sidebar-toggle-mobile');

  // Toggle sidebar collapse
  function toggleSidebar() {
    isCollapsed = !isCollapsed;

    if (isCollapsed) {
      sidebar.classList.add('sidebar-collapsed');
      sidebar.style.width = '80px';
      toggleIcon.className = 'ri-menu-fold-line text-lg text-gray-600';
      if (mainContent) {
        mainContent.style.marginRight = '80px';
      }
    } else {
      sidebar.classList.remove('sidebar-collapsed');
      sidebar.style.width = '320px';
      toggleIcon.className = 'ri-menu-unfold-line text-lg text-gray-600';
      if (mainContent) {
        mainContent.style.marginRight = '320px';
      }
    }

    // Store state in localStorage
    localStorage.setItem('{{ $storageKey }}', isCollapsed);
  }

  // Initialize sidebar state from localStorage
  const savedState = localStorage.getItem('{{ $storageKey }}');
  if (savedState === 'true') {
    isCollapsed = true;
    sidebar.classList.add('sidebar-collapsed');
    sidebar.style.width = '80px';
    toggleIcon.className = 'ri-menu-fold-line text-lg text-gray-600';
    if (mainContent) {
      mainContent.style.marginRight = '80px';
    }
  } else {
    // Ensure sidebar takes full width on page load
    sidebar.style.width = '320px';
    if (mainContent) {
      mainContent.style.marginRight = '320px';
    }
  }

  // Event listeners
  toggleButton?.addEventListener('click', toggleSidebar);

  // Mobile sidebar toggle
  mobileToggle?.addEventListener('click', function() {
    sidebar.classList.toggle('translate-x-full');
    overlay.classList.toggle('hidden');
  });

  overlay?.addEventListener('click', function() {
    sidebar.classList.add('translate-x-full');
    overlay.classList.add('hidden');
  });

  // Tooltip functionality
  const navItems = document.querySelectorAll('#{{ $sidebarId }} .nav-item');
  let tooltipTimeout;

  navItems.forEach(item => {
    item.addEventListener('mouseenter', function(e) {
      if (isCollapsed) {
        // Clear any existing timeout
        clearTimeout(tooltipTimeout);

        const tooltipContent = this.getAttribute('data-tooltip');
        if (tooltipContent) {
          tooltipText.textContent = tooltipContent;

          // Position tooltip to the left of the collapsed sidebar (RTL layout)
          const rect = this.getBoundingClientRect();
          tooltip.style.right = '90px'; // Fixed position beside the 80px collapsed sidebar
          tooltip.style.left = 'auto'; // Clear any left positioning
          tooltip.style.top = (rect.top + rect.height / 2 - 15) + 'px';
          tooltip.style.transform = 'translateX(-10px)'; // Start from left (in RTL, move from sidebar)

          // Show tooltip with animation
          tooltip.classList.remove('opacity-0');
          tooltip.classList.add('opacity-100');

          // Animate to final position
          setTimeout(() => {
            tooltip.style.transform = 'translateX(0)';
          }, 10);
        }
      }
    });

    item.addEventListener('mouseleave', function() {
      // Add delay before hiding tooltip
      tooltipTimeout = setTimeout(() => {
        // Animate out to the left (towards sidebar in RTL)
        tooltip.style.transform = 'translateX(-10px)';
        setTimeout(() => {
          tooltip.classList.remove('opacity-100');
          tooltip.classList.add('opacity-0');
          tooltip.style.transform = 'translateX(0)';
        }, 100);
      }, 200); // 200ms delay before hiding
    });
  });

  // Handle window resize
  window.addEventListener('resize', function() {
    if (window.innerWidth < 768) {
      // Mobile view - reset sidebar state
      sidebar.classList.remove('sidebar-collapsed');
      sidebar.style.width = '';
      if (mainContent) {
        mainContent.style.marginRight = '';
      }
    } else {
      // Desktop view - restore saved state
      if (savedState === 'true') {
        sidebar.classList.add('sidebar-collapsed');
        sidebar.style.width = '80px';
        if (mainContent) {
          mainContent.style.marginRight = '80px';
        }
      } else {
        sidebar.style.width = '320px';
        if (mainContent) {
          mainContent.style.marginRight = '320px';
        }
      }
    }
  });
});
</script>
