@props(['role' => 'student'])

@php
  $user = auth()->user();
  $academy = $user ? $user->academy : null;
  $academyName = $academy ? $academy->name : 'أكاديمية إتقان';
  $subdomain = $academy ? $academy->subdomain : 'itqan-academy';

  // Get academy branding
  $brandColor = $academy && $academy->brand_color ? $academy->brand_color->value : 'sky';
  $brandColorClass = "text-{$brandColor}-600";

  // Determine current route for active states
  $currentRoute = request()->route()->getName();

  // Student navigation items
  $studentNavItems = [];

  // Add items only if routes exist
  if (Route::has('student.quran-circles')) {
    $studentNavItems[] = ['route' => 'student.quran-circles', 'label' => 'حلقات القرآن الجماعية', 'activeRoutes' => ['student.quran-circles', 'student.circles.show']];
  }
  if (Route::has('student.quran-teachers')) {
    $studentNavItems[] = ['route' => 'student.quran-teachers', 'label' => 'معلمو القرآن', 'activeRoutes' => ['student.quran-teachers', 'public.quran-teachers.index', 'public.quran-teachers.show', 'public.quran-teachers.trial', 'public.quran-teachers.subscribe']];
  }
  if (Route::has('student.interactive-courses')) {
    $studentNavItems[] = ['route' => 'student.interactive-courses', 'label' => 'الكورسات التفاعلية', 'activeRoutes' => ['student.interactive-courses']];
  }
  if (Route::has('student.academic-teachers')) {
    $studentNavItems[] = ['route' => 'student.academic-teachers', 'label' => 'المعلمون الأكاديميون', 'activeRoutes' => ['student.academic-teachers']];
  }
  if (Route::has('courses.index')) {
    $studentNavItems[] = ['route' => 'courses.index', 'label' => 'الكورسات المسجلة', 'activeRoutes' => ['courses.index', 'courses.show', 'courses.learn']];
  }
  if (Route::has('student.homework.index')) {
    $studentNavItems[] = ['route' => 'student.homework.index', 'label' => 'الواجبات', 'activeRoutes' => ['student.homework.index', 'student.homework.submit', 'student.homework.view']];
  }

  // If no routes exist, add fallback items without routes
  if (empty($studentNavItems)) {
    $studentNavItems = [
      ['route' => 'student.profile', 'label' => 'حلقات القرآن الجماعية', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'معلمو القرآن', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'الكورسات التفاعلية', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'المعلمون الأكاديميون', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'الكورسات المسجلة', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'الواجبات', 'activeRoutes' => []],
    ];
  }

  // Teacher navigation items
  $teacherNavItems = [];

  if (Route::has('teacher.profile')) {
    $teacherNavItems[] = ['route' => 'teacher.profile', 'label' => 'الرئيسية', 'icon' => 'ri-dashboard-line', 'activeRoutes' => ['teacher.profile']];
  }
  if (Route::has('teacher.students')) {
    $teacherNavItems[] = ['route' => 'teacher.students', 'label' => 'الطلاب', 'icon' => 'ri-group-line', 'activeRoutes' => ['teacher.students']];
  }
  if (Route::has('teacher.earnings')) {
    $teacherNavItems[] = ['route' => 'teacher.earnings', 'label' => 'الأرباح', 'icon' => 'ri-money-dollar-circle-line', 'activeRoutes' => ['teacher.earnings']];
  }
  if (Route::has('teacher.schedule.dashboard')) {
    $teacherNavItems[] = ['route' => 'teacher.schedule.dashboard', 'label' => 'الجدول والمواعيد', 'icon' => 'ri-calendar-schedule-line', 'activeRoutes' => ['teacher.schedule.*']];
  }

  // If no routes exist, add fallback items
  if (empty($teacherNavItems)) {
    $teacherNavItems = [
      ['route' => 'teacher.profile', 'label' => 'الرئيسية', 'icon' => 'ri-dashboard-line', 'activeRoutes' => []],
    ];
  }

  $navItems = $role === 'teacher' ? $teacherNavItems : $studentNavItems;

  // Get user profile info
  if ($role === 'student') {
    $profile = $user ? $user->studentProfile : null;
    $displayName = $profile ? ($profile->first_name ?? ($user ? $user->name : 'ضيف')) : ($user ? $user->name : 'ضيف');
    $roleLabel = 'طالب';
  } else {
    $profile = $user && $user->isQuranTeacher()
              ? $user->quranTeacherProfile
              : ($user ? $user->academicTeacherProfile : null);
    $displayName = $profile ? ($profile->first_name ?? ($user ? $user->name : 'معلم')) : ($user ? $user->name : 'معلم');
    $roleLabel = $user && $user->isQuranTeacher() ? 'معلم قرآن' : 'معلم أكاديمي';
  }
@endphp

<nav id="navigation" class="bg-white shadow-lg fixed top-0 left-0 right-0 z-40" role="navigation" aria-label="التنقل الرئيسي">
  <div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">

      <!-- Logo and Navigation -->
      <div class="flex items-center space-x-8 space-x-reverse">
        <!-- Logo -->
        <div class="flex items-center">
          @if($academy && $academy->logo)
            <img src="{{ Storage::url($academy->logo) }}"
                 alt="{{ $academyName }}"
                 class="h-12 w-auto">
          @else
            <div class="w-10 h-10 flex items-center justify-center rounded-lg bg-{{ $brandColor }}-100">
              <i class="ri-book-open-line text-2xl text-{{ $brandColor }}-600"></i>
            </div>
          @endif
          <div class="mr-2">
            <span class="text-xl font-bold {{ $brandColorClass }}">{{ $academyName }}</span>
            @if($role === 'teacher')
              <p class="text-xs text-gray-500">لوحة المعلم</p>
            @endif
          </div>
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center {{ $role === 'teacher' ? 'space-x-6' : 'space-x-6' }} space-x-reverse">
          @foreach($navItems as $item)
            @php
              $isActive = false;
              foreach($item['activeRoutes'] as $activeRoute) {
                if (str_contains($activeRoute, '*')) {
                  $pattern = str_replace('*', '', $activeRoute);
                  if (str_starts_with($currentRoute, $pattern)) {
                    $isActive = true;
                    break;
                  }
                } elseif ($currentRoute === $activeRoute) {
                  $isActive = true;
                  break;
                }
              }
              $itemRoute = Route::has($item['route']) ? route($item['route'], ['subdomain' => $subdomain]) : '#';
            @endphp
            <a href="{{ $itemRoute }}"
               class="flex items-center font-medium {{ $role === 'teacher' ? 'px-3 py-2 text-sm' : '' }} {{ $isActive ? $brandColorClass . ($role === 'teacher' ? ' border-b-2 border-' . $brandColor . '-600' : '') : 'text-gray-700' }} hover:{{ $brandColorClass }} transition-colors duration-200 focus:ring-2 focus:ring-{{ $brandColor }}-500">
              @if(isset($item['icon']) && $role === 'teacher')
                <i class="{{ $item['icon'] }} ml-2"></i>
              @endif
              {{ $item['label'] }}
            </a>
          @endforeach
        </div>
      </div>

      <!-- Right Side Actions -->
      <div class="flex items-center space-x-4 space-x-reverse">

        <!-- Search Bar (Student only) -->
        @if($role === 'student')
          @php
            $searchRoute = Route::has('student.search') ? route('student.search', ['subdomain' => $subdomain]) : '/search';
          @endphp
          <form
            id="nav-search-form"
            action="{{ $searchRoute }}"
            method="GET"
            class="relative hidden md:flex flex-1 max-w-xl mx-4"
            onsubmit="return handleNavSearch(event)"
          >
            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
              <i class="ri-search-line text-gray-400"></i>
            </div>
            <input
              type="text"
              name="q"
              id="nav-search-input"
              value="{{ request('q') }}"
              placeholder="البحث في الكورسات والدروس..."
              class="w-full pl-4 pr-10 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $brandColor }}-500 focus:border-transparent"
              aria-label="البحث في المحتوى"
              required
              minlength="1"
            >
            <button
              type="submit"
              class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 hover:{{ $brandColorClass }} transition-colors"
              aria-label="بحث"
            >
              <i class="ri-arrow-left-s-line text-lg"></i>
            </button>
          </form>
        @endif

        <!-- Dashboard Link (Teacher only) -->
        @if($role === 'teacher')
          @if($user && $user->isQuranTeacher())
            <a href="/teacher-panel" target="_blank"
               class="hidden md:flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
              <i class="ri-apps-2-line ml-2"></i>
              لوحة التحكم
            </a>
          @elseif($user && $user->isAcademicTeacher())
            <a href="/academic-teacher-panel" target="_blank"
               class="hidden md:flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
              <i class="ri-apps-2-line ml-2"></i>
              لوحة التحكم
            </a>
          @endif
        @endif

        <!-- Notifications -->
        @livewire('notification-center')

        <!-- Messages -->
        <a href="/chat"
           class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200"
           aria-label="فتح الرسائل">
          <i class="ri-message-2-line text-xl"></i>
          <span id="unread-count-badge" class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-green-600 rounded-full hidden">
            0
          </span>
        </a>

        <!-- User Dropdown -->
        <div class="relative" x-data="{ open: false }">
          <button @click="open = !open"
                  class="flex items-center px-3 py-2 text-sm rounded-lg hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $brandColor }}-500"
                  aria-expanded="false"
                  aria-haspopup="true">
            <div class="flex items-center space-x-3 space-x-reverse">
              @if($role === 'student')
                <x-student-avatar :student="$profile" size="sm" />
              @else
                <x-teacher-avatar :teacher="$profile" size="sm" />
              @endif
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900">{{ $displayName }}</p>
                <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
              </div>
              <i class="ri-arrow-down-s-line text-gray-400"></i>
            </div>
          </button>

          <!-- Dropdown menu -->
          <div x-show="open"
               x-cloak
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
              @php
                $profileRoute = Route::has($role . '.profile') ? route($role . '.profile', ['subdomain' => $subdomain]) : '#';
                $logoutRoute = Route::has('logout') ? route('logout', ['subdomain' => $subdomain]) : '/logout';
              @endphp
              <a href="{{ $profileRoute }}"
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                 role="menuitem">
                <i class="ri-user-line ml-2"></i>
                الملف الشخصي
              </a>
              <div class="border-t border-gray-100"></div>
              <form method="POST" action="{{ $logoutRoute }}">
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
        @foreach($navItems as $item)
          @php
            $isActive = false;
            foreach($item['activeRoutes'] as $activeRoute) {
              if (str_contains($activeRoute, '*')) {
                $pattern = str_replace('*', '', $activeRoute);
                if (str_starts_with($currentRoute, $pattern)) {
                  $isActive = true;
                  break;
                }
              } elseif ($currentRoute === $activeRoute) {
                $isActive = true;
                break;
              }
            }
            $itemRoute = Route::has($item['route']) ? route($item['route'], ['subdomain' => $subdomain]) : '#';
          @endphp
          <a href="{{ $itemRoute }}"
             class="flex items-center px-3 py-2 font-medium {{ $isActive ? $brandColorClass . ' bg-gray-50' : 'text-gray-700' }} hover:{{ $brandColorClass }} hover:bg-gray-50 rounded-md focus:ring-2 focus:ring-{{ $brandColor }}-500">
            @if(isset($item['icon']))
              <i class="{{ $item['icon'] }} ml-2"></i>
            @endif
            {{ $item['label'] }}
          </a>
        @endforeach
      </div>
    </div>
  </div>
</nav>

<!-- Alpine.js x-cloak styles -->
<style>
  [x-cloak] { display: none !important; }
</style>

<script>
// Handle navigation search form submission
function handleNavSearch(event) {
  const form = event.target;
  const input = form.querySelector('#nav-search-input');
  const query = input.value.trim();

  if (!query || query.length === 0) {
    event.preventDefault();
    alert('الرجاء إدخال كلمة بحث');
    return false;
  }

  // Let the form submit naturally
  return true;
}

document.addEventListener('DOMContentLoaded', function() {
  // Mobile menu toggle
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');

  mobileMenuButton?.addEventListener('click', function() {
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
    mobileMenu.classList.toggle('hidden');
  });

  // Navigation search - handle Enter key
  const navSearchInput = document.getElementById('nav-search-input');
  if (navSearchInput) {
    navSearchInput.addEventListener('keypress', function(event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        const form = document.getElementById('nav-search-form');
        if (form) {
          form.submit();
        }
      }
    });
  }

  // WireChat will handle unread counts internally
});
</script>
