<!-- Teacher Navigation Component -->
<nav class="fixed top-0 left-0 right-0 bg-white shadow-sm border-b border-gray-200 z-50" role="navigation" aria-label="شريط التنقل الرئيسي">
  <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      
      <!-- Logo and Academy Name -->
      <div class="flex items-center space-x-4 space-x-reverse">
        <div class="flex-shrink-0">
          @if(auth()->user()->academy->logo)
            <img src="{{ Storage::url(auth()->user()->academy->logo) }}" 
                 alt="{{ auth()->user()->academy->name }}" 
                 class="h-12 w-auto">
          @else
            <div class="h-12 w-12 bg-primary rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-lg">
                {{ substr(auth()->user()->academy->name, 0, 1) }}
              </span>
            </div>
          @endif
        </div>
        <div class="hidden md:block">
          <h1 class="text-xl font-bold text-gray-900">
            {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}
          </h1>
          <p class="text-sm text-gray-500">لوحة المعلم</p>
        </div>
      </div>

      <!-- Center - Quick Navigation -->
      <div class="hidden lg:flex items-center space-x-6 space-x-reverse">
        <a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary transition-colors {{ request()->routeIs('teacher.profile') ? 'text-primary border-b-2 border-primary' : '' }}">
          <i class="ri-dashboard-line ml-2"></i>
          الرئيسية
        </a>
        
        <a href="{{ route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary transition-colors {{ request()->routeIs('teacher.students') ? 'text-primary border-b-2 border-primary' : '' }}">
          <i class="ri-group-line ml-2"></i>
          الطلاب
        </a>
        
        <a href="{{ route('teacher.earnings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary transition-colors {{ request()->routeIs('teacher.earnings') ? 'text-primary border-b-2 border-primary' : '' }}">
          <i class="ri-money-dollar-circle-line ml-2"></i>
          الأرباح
        </a>
        
        <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary transition-colors {{ request()->routeIs('teacher.schedule.*') ? 'text-primary border-b-2 border-primary' : '' }}">
          <i class="ri-calendar-schedule-line ml-2"></i>
          الجدول والمواعيد
        </a>
      </div>

      <!-- Right Side - User Menu -->
      <div class="flex items-center space-x-4 space-x-reverse">
        
        <!-- Dashboard Link -->
        @if(auth()->user()->isQuranTeacher())
          <a href="/teacher-panel" target="_blank"
             class="hidden md:flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <i class="ri-apps-2-line ml-2"></i>
            لوحة التحكم
          </a>
        @elseif(auth()->user()->isAcademicTeacher())
          <a href="/academic-teacher-panel" target="_blank"
             class="hidden md:flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
            <i class="ri-apps-2-line ml-2"></i>
            لوحة التحكم
          </a>
        @endif
        
        <!-- Notifications -->
        <button class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200">
          <i class="ri-notification-2-line text-xl"></i>
          <span class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
            2
          </span>
        </button>

        <!-- Messages -->
        <a href="{{ route('chat', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200" aria-label="فتح الرسائل">
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
                $teacher = auth()->user()->isQuranTeacher() 
                          ? auth()->user()->quranTeacherProfile 
                          : auth()->user()->academicTeacherProfile;
              @endphp
              <x-teacher-avatar :teacher="$teacher" size="sm" />
              <div class="hidden md:block text-right">
                <p class="text-sm font-medium text-gray-900">
                  @if(auth()->user()->isQuranTeacher())
                    {{ auth()->user()->quranTeacherProfile->first_name ?? auth()->user()->name }}
                  @else
                    {{ auth()->user()->academicTeacherProfile->first_name ?? auth()->user()->name }}
                  @endif
                </p>
                <p class="text-xs text-gray-500">
                  @if(auth()->user()->isQuranTeacher())
                    معلم قرآن
                  @else
                    معلم أكاديمي
                  @endif
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
              <a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                <i class="ri-user-line ml-2"></i>
                الملف الشخصي
              </a>
              <div class="border-t border-gray-100"></div>
              <form method="POST" action="{{ route('logout', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}">
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

        <!-- Mobile menu button -->
        <button id="sidebar-toggle" 
                class="md:hidden p-2 text-gray-400 hover:text-gray-600 transition-colors">
          <i class="ri-menu-line text-xl"></i>
        </button>
      </div>
    </div>
  </div>
</nav>

<!-- Alpine.js for dropdown functionality -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Function to fetch and update unread count
  function updateUnreadCount() {
    fetch('/api/chat/unreadCount', {
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