<!-- Student Navigation Component -->
@php
  $user = auth()->user();
  $academy = $user ? $user->academy : null;
  $academyName = $academy ? $academy->name : 'أكاديمية إتقان';
  $subdomain = $academy ? $academy->subdomain : 'itqan-academy';
@endphp
<nav id="navigation" class="bg-white shadow-lg fixed top-0 left-0 right-0 z-50" role="navigation" aria-label="التنقل الرئيسي للطالب">
  <div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <!-- Logo and Main Navigation -->
      <div class="flex items-center space-x-8 space-x-reverse">
        <div class="flex items-center">
          <div class="w-8 h-8 flex items-center justify-center">
            <i class="ri-book-open-line text-2xl text-primary"></i>
          </div>
          <span class="mr-2 text-xl font-bold text-primary">{{ $academyName }}</span>
        </div>
        <div class="hidden md:flex items-center space-x-6 space-x-reverse">
          @php
            $currentRoute = request()->route()->getName();
            $isQuranCirclesActive = in_array($currentRoute, ['student.quran-circles', 'student.circles.show', 'student.quran']);
            $isQuranTeachersActive = in_array($currentRoute, ['student.quran-teachers', 'public.quran-teachers.index', 'public.quran-teachers.show', 'public.quran-teachers.trial', 'public.quran-teachers.subscribe']);
            $isInteractiveCoursesActive = in_array($currentRoute, ['student.interactive-courses']);
            $isAcademicTeachersActive = in_array($currentRoute, ['student.academic-teachers']);
            $isRecordedCoursesActive = in_array($currentRoute, ['courses.index', 'courses.show', 'courses.learn']);
          @endphp
          <a href="{{ route('student.quran-circles', ['subdomain' => $subdomain]) }}" 
             class="{{ $isQuranCirclesActive ? 'text-primary font-medium' : 'text-gray-700' }} hover:text-primary transition-colors duration-200 focus:ring-custom" 
             aria-label="استعرض حلقات القرآن المتاحة">حلقات القرآن الجماعية</a>
          <a href="{{ route('student.quran-teachers', ['subdomain' => $subdomain]) }}" 
             class="{{ $isQuranTeachersActive ? 'text-primary font-medium' : 'text-gray-700' }} hover:text-primary transition-colors duration-200 focus:ring-custom" 
             aria-label="استعرض معلمي القرآن">معلمو القرآن</a>
          <a href="{{ route('student.interactive-courses', ['subdomain' => $subdomain]) }}" 
             class="{{ $isInteractiveCoursesActive ? 'text-primary font-medium' : 'text-gray-700' }} hover:text-primary transition-colors duration-200 focus:ring-custom" 
             aria-label="استعرض الكورسات التفاعلية">الكورسات التفاعلية</a>
          <a href="{{ route('student.academic-teachers', ['subdomain' => $subdomain]) }}" 
             class="{{ $isAcademicTeachersActive ? 'text-primary font-medium' : 'text-gray-700' }} hover:text-primary transition-colors duration-200 focus:ring-custom" 
             aria-label="استعرض المعلمين الأكاديميين">المعلمون الأكاديميون</a>
          <a href="{{ route('courses.index', ['subdomain' => $subdomain]) }}" 
             class="{{ $isRecordedCoursesActive ? 'text-primary font-medium' : 'text-gray-700' }} hover:text-primary transition-colors duration-200 focus:ring-custom" 
             aria-label="استعرض الكورسات المسجلة">الكورسات المسجلة</a>
        </div>
      </div>

      <!-- Search, Notifications, and User Profile -->
      <div class="flex items-center space-x-4 space-x-reverse">
        <!-- Search Bar -->
        <div class="relative hidden md:block">
          <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <i class="ri-search-line text-gray-400"></i>
          </div>
          <input type="text" 
                 placeholder="البحث في الكورسات والدروس..." 
                 class="w-64 p4-10 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                 aria-label="البحث في المحتوى">
        </div>

        <!-- Notifications -->
        <button class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200">
          <i class="ri-notification-2-line text-xl"></i>
          <span class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
            3
          </span>
        </button>

        <!-- Messages -->
        <a href="{{ route('chat', ['subdomain' => $subdomain]) }}" class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200" aria-label="فتح الرسائل">
          <i class="ri-message-2-line text-xl"></i>
          <span id="unread-count-badge" class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-green-600 rounded-full hidden">
            0
          </span>
        </a>

        <!-- User Dropdown -->
        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open" 
                  class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                  aria-expanded="false" 
                  aria-haspopup="true">
            <div class="flex items-center space-x-3 space-x-reverse">
              @php
                $student = $user ? $user->studentProfile : null;
                $studentName = $student ? 
                             ($student->first_name && $student->last_name ? $student->first_name . ' ' . $student->last_name : $student->first_name) :
                             ($user ? $user->name : 'ضيف');
              @endphp
              <x-student-avatar :student="$student" size="sm" />
              <div class="hidden md:block text-right">
                <p class="text-sm font-medium text-gray-900">
                  {{ $student->first_name ?? ($user ? $user->name : 'ضيف') }}
                </p>
                <p class="text-xs text-gray-500">
                  طالب
                </p>
              </div>
              <i class="ri-arrow-down-s-line text-gray-400"></i>
            </div>
          </button>

          <!-- Dropdown menu -->
          <div x-show="open" 
               @click.away="open = false"
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="transform opacity-0 scale-95"
               x-transition:enter-end="transform opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="transform opacity-100 scale-100"
               x-transition:leave-end="transform opacity-0 scale-95"
               class="origin-top-left absolute left-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none"
               role="menu" 
               aria-orientation="vertical">
            <div class="py-1" role="none">
              <a href="{{ route('student.profile', ['subdomain' => $subdomain]) }}" 
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                <i class="ri-user-line ml-2"></i>
                الملف الشخصي
              </a>
              <div class="border-t border-gray-100"></div>
              <form method="POST" action="{{ route('logout', ['subdomain' => $subdomain]) }}">
                @csrf
                <button type="submit" 
                        class="flex items-center w-full px-4 py-2 text-sm text-red-700 hover:bg-red-50" 
                        role="menuitem">
                  <i class="ri-logout-box-line ml-2"></i>
                  تسجيل الخروج
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <button class="md:hidden focus:ring-custom" 
                aria-label="فتح قائمة التنقل" 
                aria-expanded="false" 
                id="mobile-menu-button">
          <div class="w-6 h-6 flex items-center justify-center">
            <i class="ri-menu-line text-xl"></i>
          </div>
        </button>
      </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="md:hidden hidden" id="mobile-menu">
      <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t border-gray-200">
        <a href="{{ route('student.quran-circles', ['subdomain' => $subdomain]) }}" 
           class="block px-3 py-2 {{ $isQuranCirclesActive ? 'text-primary font-medium bg-gray-50' : 'text-gray-700' }} hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">حلقات القرآن</a>
        <a href="{{ route('student.quran-teachers', ['subdomain' => $subdomain]) }}" 
           class="block px-3 py-2 {{ $isQuranTeachersActive ? 'text-primary font-medium bg-gray-50' : 'text-gray-700' }} hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">معلمو القرآن</a>
        <a href="{{ route('student.interactive-courses', ['subdomain' => $subdomain]) }}" 
           class="block px-3 py-2 {{ $isInteractiveCoursesActive ? 'text-primary font-medium bg-gray-50' : 'text-gray-700' }} hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">الكورسات التفاعلية</a>
        <a href="{{ route('student.academic-teachers', ['subdomain' => $subdomain]) }}" 
           class="block px-3 py-2 {{ $isAcademicTeachersActive ? 'text-primary font-medium bg-gray-50' : 'text-gray-700' }} hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">المعلمون الأكاديميون</a>
        <a href="{{ route('courses.index', ['subdomain' => $subdomain]) }}" 
           class="block px-3 py-2 {{ $isRecordedCoursesActive ? 'text-primary font-medium bg-gray-50' : 'text-gray-700' }} hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">الكورسات المسجلة</a>
      </div>
    </div>
  </div>
</nav>

<!-- Alpine.js for dropdown functionality -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mobile menu toggle
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  
  mobileMenuButton?.addEventListener('click', function() {
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
    mobileMenu.classList.toggle('hidden');
  });

  // Function to fetch and update unread count
  function updateUnreadCount() {
    fetch('/chat/api/unreadCount', {
      method: 'GET',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
      const badge = document.getElementById('unread-count-badge');
      if (badge && data.unread_count !== undefined) {
        if (data.unread_count > 0) {
          badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
          badge.classList.remove('hidden');
        } else {
          badge.classList.add('hidden');
        }
      }
    })
    .catch(error => {
      console.error('Error fetching unread count:', error);
    });
  }

  // Initial load
  updateUnreadCount();

  // Update every 5 seconds for faster real-time feel
  setInterval(updateUnreadCount, 5000);

  // Listen for messages marked as read
  window.addEventListener('messages-marked-read', (e) => {
    updateUnreadCount();
  });
});
</script> 