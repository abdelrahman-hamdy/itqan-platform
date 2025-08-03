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
        
        <a href="{{ route('teacher.schedule', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
           class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 hover:text-primary transition-colors {{ request()->routeIs('teacher.schedule') ? 'text-primary border-b-2 border-primary' : '' }}">
          <i class="ri-calendar-line ml-2"></i>
          الجدول
        </a>
      </div>

      <!-- Right Side - User Menu -->
      <div class="flex items-center space-x-4 space-x-reverse">
        
        <!-- Dashboard Link -->
        <a href="/teacher-panel" target="_blank"
           class="hidden md:flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-secondary transition-colors">
          <i class="ri-dashboard-3-line ml-2"></i>
          لوحة التحكم
        </a>
        
        <!-- Notifications -->
        <button class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors">
          <i class="ri-notification-3-line text-xl"></i>
          <span class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
            2
          </span>
        </button>

        <!-- Messages -->
        <button class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors">
          <i class="ri-message-3-line text-xl"></i>
          <span class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-green-600 rounded-full">
            1
          </span>
        </button>

        <!-- User Dropdown -->
        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open" 
                  class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                  aria-expanded="false" 
                  aria-haspopup="true">
            <div class="flex items-center space-x-3 space-x-reverse">
              <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                <span class="text-white font-medium">
                  @if(auth()->user()->isQuranTeacher())
                    {{ substr(auth()->user()->quranTeacherProfile->first_name ?? auth()->user()->name, 0, 1) }}
                  @else
                    {{ substr(auth()->user()->academicTeacherProfile->first_name ?? auth()->user()->name, 0, 1) }}
                  @endif
                </span>
              </div>
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
              <a href="{{ route('teacher.settings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50" role="menuitem">
                <i class="ri-settings-3-line ml-2"></i>
                الإعدادات
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