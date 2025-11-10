<!-- Student Sidebar Component -->
<aside id="student-sidebar" class="fixed right-0 top-20 h-screen bg-white shadow-lg border-l border-t border-gray-200 z-40 transform translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out" 
       role="complementary" 
       aria-label="قائمة الطالب الجانبية">
  
  <!-- Collapse Toggle Button (Inside Sidebar) -->
  <button id="sidebar-toggle" class="absolute top-4 z-50 p-2 bg-gray-100 hover:bg-gray-200 transition-all duration-300 border-r border-gray-200" 
          aria-label="طي/فتح القائمة الجانبية">
    <i id="toggle-icon" class="ri-menu-fold-line text-lg text-gray-600"></i>
  </button>

  <!-- Scrollable Content Container -->
  <div class="h-full overflow-y-auto">

  <!-- Profile Section -->
  <div id="profile-section" class="p-6 border-b border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100/50 transition-all duration-300">
    <div id="profile-content" class="flex flex-col items-center text-center mb-4 transition-all duration-300">
      @php
        $user = auth()->user();
        $student = $user ? $user->studentProfile : null;
        $fullName = $student ? 
                   ($student->first_name && $student->last_name ? $student->first_name . ' ' . $student->last_name : $student->first_name) :
                   ($user ? $user->name : 'زائر');
      @endphp
      
      <x-student-avatar :student="$student" size="md" class="mb-3" />
      
      <div id="profile-info" class="transition-all duration-300">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $fullName }}
        </h3>
        <p class="text-xs text-gray-400">
          {{ $student && $student->gradeLevel?->name ? 'المرحلة الدراسية: ' . $student->gradeLevel->name : ($student?->student_code ?? 'طالب') }}
        </p>
      </div>
    </div>
    
    <!-- Student Info -->
    <div id="student-info" class="space-y-2 text-sm transition-all duration-300">
      <div class="flex items-center justify-center text-gray-600">
        <i class="ri-mail-line ml-2 text-gray-400"></i>
        <span class="truncate">{{ $user ? $user->email : 'غير مسجل الدخول' }}</span>
      </div>
    </div>
  </div>

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="قائمة التنقل الشخصية">
    <div class="space-y-2">
      
      <!-- Profile Management -->
      <div class="mb-6">
        <h4 id="profile-mgmt-title" class="text-xs font-medium text-gray-400 mb-3 transition-all duration-300">إدارة الملف الشخصي</h4>
        <div class="space-y-1">
          <a href="{{ $user ? route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy']) : '#' }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.profile') ? 'bg-gray-100 text-primary' : '' }}"
             data-tooltip="الملف الشخصي">
            <i class="ri-user-line ml-3"></i>
            <span class="nav-text transition-all duration-300">الملف الشخصي</span>
          </a>
          <a href="{{ route('student.profile.edit', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors"
             data-tooltip="تعديل الملف الشخصي">
            <i class="ri-edit-line ml-3"></i>
            <span class="nav-text transition-all duration-300">تعديل الملف الشخصي</span>
          </a>
        </div>
      </div>

      <!-- Learning Progress -->
      <div class="mb-6">
        <h4 id="learning-progress-title" class="text-xs font-medium text-gray-400 mb-3 transition-all duration-300">التقدم الدراسي</h4>
        <div class="space-y-1">
          <a href="{{ route('student.progress', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors"
             data-tooltip="تقرير التقدم">
            <i class="ri-bar-chart-line ml-3"></i>
            <span class="nav-text transition-all duration-300">تقرير التقدم</span>
          </a>
          <a href="{{ route('student.certificates', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors"
             data-tooltip="الشهادات">
            <i class="ri-medal-line ml-3"></i>
            <span class="nav-text transition-all duration-300">الشهادات</span>
          </a>
          <a href="{{ route('student.calendar', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}"
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.calendar') ? 'bg-gray-100 text-primary' : '' }}"
             data-tooltip="التقويم والجلسات">
            <i class="ri-calendar-line ml-3"></i>
            <span class="nav-text transition-all duration-300">التقويم والجلسات</span>
          </a>
          <a href="{{ route('student.homework.index', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}"
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.homework.*') ? 'bg-gray-100 text-primary' : '' }}"
             data-tooltip="الواجبات">
            <i class="ri-file-list-3-line ml-3"></i>
            <span class="nav-text transition-all duration-300">الواجبات</span>
          </a>
        </div>
      </div>

      <!-- Teachers -->
      <div class="mb-6">
        <h4 id="teachers-title" class="text-xs font-medium text-gray-400 mb-3 transition-all duration-300">المعلمون</h4>
        <div class="space-y-1">
          <a href="{{ route('student.quran-teachers', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.quran-teachers') ? 'bg-gray-100 text-primary' : '' }}"
             data-tooltip="معلمو القرآن الكريم">
            <i class="ri-user-star-line ml-3"></i>
            <span class="nav-text transition-all duration-300">معلمو القرآن الكريم</span>
          </a>
          <a href="{{ route('student.academic-teachers', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.academic-teachers') ? 'bg-gray-100 text-primary' : '' }}"
             data-tooltip="المعلمون الأكاديميون">
            <i class="ri-user-3-line ml-3"></i>
            <span class="nav-text transition-all duration-300">المعلمون الأكاديميون</span>
          </a>
        </div>
      </div>

      <!-- Subscriptions & Payments -->
      <div class="mb-6">
        <h4 id="subscriptions-title" class="text-xs font-medium text-gray-400 mb-3 transition-all duration-300">الاشتراكات والمدفوعات</h4>
        <div class="space-y-1">
          <a href="{{ route('student.subscriptions', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors"
             data-tooltip="الاشتراكات النشطة">
            <i class="ri-wallet-3-line ml-3"></i>
            <span class="nav-text transition-all duration-300">الاشتراكات النشطة</span>
          </a>
          <a href="{{ route('student.payments', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors"
             data-tooltip="سجل المدفوعات">
            <i class="ri-bill-line ml-3"></i>
            <span class="nav-text transition-all duration-300">سجل المدفوعات</span>
          </a>
        </div>
      </div>

      <!-- Communication -->
      <div class="mb-6">
        <h4 id="communication-title" class="text-xs font-medium text-gray-400 mb-3 transition-all duration-300">التواصل</h4>
        <div class="space-y-1">
          <a href="{{ url('/chat') }}" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->is('chat*') ? 'bg-gray-100 text-primary' : '' }}"
             data-tooltip="الرسائل والمحادثات">
            <i class="ri-message-3-line ml-3"></i>
            <span class="nav-text transition-all duration-300">الرسائل والمحادثات</span>
          </a>
          <a href="#" 
             class="nav-item flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors"
             data-tooltip="الدعم الفني">
            <i class="ri-customer-service-2-line ml-3"></i>
            <span class="nav-text transition-all duration-300">الدعم الفني</span>
          </a>
        </div>
      </div>

    </div>
  </nav>

  </div> <!-- End Scrollable Content Container -->

</aside>

<!-- Mobile Sidebar Overlay -->
<div class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden" id="sidebar-overlay"></div>

<!-- Tooltip Container -->
<div id="tooltip" class="fixed z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg opacity-0 pointer-events-none transition-opacity duration-200">
  <span id="tooltip-text"></span>
</div>

<style>
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
  .sidebar-collapsed #student-info {
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
  #tooltip {
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
  #sidebar-toggle {
    border-radius: 0;
    right: 100%;
    transition: right 0.3s ease;
  }
  
  .sidebar-collapsed #sidebar-toggle {
    right: 100%;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('student-sidebar');
  const toggleButton = document.getElementById('sidebar-toggle');
  const toggleIcon = document.getElementById('toggle-icon');
  const overlay = document.getElementById('sidebar-overlay');
  const tooltip = document.getElementById('tooltip');
  const tooltipText = document.getElementById('tooltip-text');
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
      toggleIcon.className = 'ri-menu-unfold-line text-lg text-gray-600';
      if (mainContent) {
        mainContent.style.marginRight = '80px';
      }
    } else {
      sidebar.classList.remove('sidebar-collapsed');
      sidebar.style.width = '320px';
      toggleIcon.className = 'ri-menu-fold-line text-lg text-gray-600';
      if (mainContent) {
        mainContent.style.marginRight = '320px';
      }
    }
    
    // Store state in localStorage
    localStorage.setItem('sidebarCollapsed', isCollapsed);
  }
  
  // Initialize sidebar state from localStorage
  const savedState = localStorage.getItem('sidebarCollapsed');
  if (savedState === 'true') {
    isCollapsed = true;
    sidebar.classList.add('sidebar-collapsed');
    sidebar.style.width = '80px';
    toggleIcon.className = 'ri-menu-unfold-line text-lg text-gray-600';
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
  const navItems = document.querySelectorAll('.nav-item');
  let tooltipTimeout;
  
  navItems.forEach(item => {
    item.addEventListener('mouseenter', function(e) {
      if (isCollapsed) {
        // Clear any existing timeout
        clearTimeout(tooltipTimeout);
        
        const tooltipContent = this.getAttribute('data-tooltip');
        if (tooltipContent) {
          tooltipText.textContent = tooltipContent;
          
          // Position tooltip closer to the sidebar
          const rect = this.getBoundingClientRect();
          tooltip.style.left = (rect.left - 180) + 'px'; // Closer to sidebar
          tooltip.style.top = (rect.top + rect.height / 2 - 15) + 'px';
          tooltip.style.transform = 'translateX(10px)'; // Start from right
          
          // Show tooltip with animation
          tooltip.classList.remove('opacity-0');
          tooltip.classList.add('opacity-100');
          
          // Animate from right to left
          setTimeout(() => {
            tooltip.style.transform = 'translateX(0)';
          }, 10);
        }
      }
    });
    
    item.addEventListener('mouseleave', function() {
      // Add delay before hiding tooltip
      tooltipTimeout = setTimeout(() => {
        // Animate out to the right
        tooltip.style.transform = 'translateX(10px)';
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