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
  $currentRoute = request()->route()?->getName() ?? '';

  // Student navigation items
  $studentNavItems = [];

  // Add items only if routes exist (using unified route names)
  if (Route::has('quran-circles.index')) {
    $studentNavItems[] = ['route' => 'quran-circles.index', 'label' => 'حلقات القرآن الجماعية', 'activeRoutes' => ['quran-circles.index', 'quran-circles.show']];
  }
  if (Route::has('quran-teachers.index')) {
    $studentNavItems[] = ['route' => 'quran-teachers.index', 'label' => 'معلمو القرآن', 'activeRoutes' => ['quran-teachers.index', 'quran-teachers.show']];
  }
  if (Route::has('interactive-courses.index')) {
    $studentNavItems[] = ['route' => 'interactive-courses.index', 'label' => 'الكورسات التفاعلية', 'activeRoutes' => ['interactive-courses.index', 'interactive-courses.show']];
  }
  if (Route::has('academic-teachers.index')) {
    $studentNavItems[] = ['route' => 'academic-teachers.index', 'label' => 'المعلمون الأكاديميون', 'activeRoutes' => ['academic-teachers.index', 'academic-teachers.show']];
  }
  if (Route::has('courses.index')) {
    $studentNavItems[] = ['route' => 'courses.index', 'label' => 'الكورسات المسجلة', 'activeRoutes' => ['courses.index', 'courses.show', 'courses.learn', 'lessons.show']];
  }

  // If no routes exist, add fallback items without routes
  if (empty($studentNavItems)) {
    $studentNavItems = [
      ['route' => 'student.profile', 'label' => 'حلقات القرآن الجماعية', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'معلمو القرآن', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'الكورسات التفاعلية', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'المعلمون الأكاديميون', 'activeRoutes' => []],
      ['route' => 'student.profile', 'label' => 'الكورسات المسجلة', 'activeRoutes' => []],
    ];
  }

  // Teacher navigation items - empty (removed nav links from topbar)
  $teacherNavItems = [];

  // Parent navigation items
  $parentNavItems = [];

  if (Route::has('parent.dashboard')) {
    $parentNavItems[] = ['route' => 'parent.dashboard', 'label' => 'الرئيسية', 'icon' => 'ri-dashboard-line', 'activeRoutes' => ['parent.dashboard']];
  }
  if (Route::has('parent.sessions.upcoming')) {
    $parentNavItems[] = ['route' => 'parent.sessions.upcoming', 'label' => 'الجلسات القادمة', 'icon' => 'ri-calendar-event-line', 'activeRoutes' => ['parent.sessions.*']];
  }
  if (Route::has('parent.subscriptions.index')) {
    $parentNavItems[] = ['route' => 'parent.subscriptions.index', 'label' => 'الاشتراكات', 'icon' => 'ri-file-list-line', 'activeRoutes' => ['parent.subscriptions.*']];
  }
  if (Route::has('parent.reports.progress')) {
    $parentNavItems[] = ['route' => 'parent.reports.progress', 'label' => 'التقارير', 'icon' => 'ri-bar-chart-line', 'activeRoutes' => ['parent.reports.*']];
  }

  // If no routes exist, add fallback items
  if (empty($parentNavItems)) {
    $parentNavItems = [
      ['route' => 'parent.dashboard', 'label' => 'الرئيسية', 'icon' => 'ri-dashboard-line', 'activeRoutes' => []],
    ];
  }

  $navItems = match($role) {
    'teacher' => $teacherNavItems,
    'parent' => $parentNavItems,
    default => $studentNavItems,
  };

  // Get user profile info
  if ($role === 'student') {
    $profile = $user ? $user->studentProfile : null;
    $displayName = $profile ? ($profile->first_name ?? ($user ? $user->name : 'ضيف')) : ($user ? $user->name : 'ضيف');
    $roleLabel = 'طالب';
    $userAvatarType = 'student';
    $userGender = $profile?->gender ?? $user?->gender ?? 'male';
  } elseif ($role === 'parent') {
    $profile = $user ? $user->parentProfile : null;
    $displayName = $profile ? $profile->getFullNameAttribute() : ($user ? $user->name : 'ولي أمر');
    $roleLabel = 'ولي أمر';
    $userAvatarType = 'parent';
    $userGender = $profile?->gender ?? $user?->gender ?? 'male';
  } else {
    $profile = $user && $user->isQuranTeacher()
              ? $user->quranTeacherProfile
              : ($user ? $user->academicTeacherProfile : null);
    $displayName = $profile ? ($profile->first_name ?? ($user ? $user->name : 'معلم')) : ($user ? $user->name : 'معلم');
    $roleLabel = $user && $user->isQuranTeacher() ? 'معلم قرآن' : 'معلم أكاديمي';
    $userAvatarType = $user && $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';
    $userGender = $profile?->gender ?? $user?->gender ?? 'male';
  }
@endphp

<nav id="navigation"
     x-data="{ mobileMenuOpen: false }"
     class="bg-white shadow-lg fixed top-0 left-0 right-0 z-40"
     role="navigation"
     aria-label="التنقل الرئيسي">
  <div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16 md:h-20">

      <!-- Logo and Navigation -->
      <div class="flex items-center space-x-8 space-x-reverse">
        <!-- Logo -->
        <div class="flex items-center">
          <x-academy-logo
            :academy="$academy"
            size="md"
            :showName="true"
            :href="route('academy.home', ['subdomain' => $subdomain])" />
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
              $isCourseRoute = $item['route'] === 'courses.index';
              $isQuranCircleRoute = $item['route'] === 'quran-circles.index';
              $isQuranTeacherRoute = $item['route'] === 'quran-teachers.index';
              $isInteractiveCourseRoute = $item['route'] === 'interactive-courses.index';
              $isAcademicTeacherRoute = $item['route'] === 'academic-teachers.index';

              // Determine primary color for each resource
              $activeColorClass = $isCourseRoute ? 'text-cyan-600' :
                                  ($isQuranCircleRoute ? 'text-green-600' :
                                  ($isQuranTeacherRoute ? 'text-yellow-600' :
                                  ($isInteractiveCourseRoute ? 'text-blue-600' :
                                  ($isAcademicTeacherRoute ? 'text-violet-600' : $brandColorClass))));

              $hoverColorClass = $isCourseRoute ? 'hover:text-cyan-600 hover:bg-gray-100' :
                                 ($isQuranCircleRoute ? 'hover:text-green-600 hover:bg-gray-100' :
                                 ($isQuranTeacherRoute ? 'hover:text-yellow-600 hover:bg-gray-100' :
                                 ($isInteractiveCourseRoute ? 'hover:text-blue-600 hover:bg-gray-100' :
                                 ($isAcademicTeacherRoute ? 'hover:text-violet-600 hover:bg-gray-100' : 'hover:' . $brandColorClass . ' hover:bg-gray-100'))));
            @endphp
            <a href="{{ $itemRoute }}"
               class="flex items-center font-medium px-3 py-2 {{ $isActive ? $activeColorClass : 'text-gray-700' }} {{ $hoverColorClass }} rounded-lg transition-all duration-200 focus:outline-none">
              @if(isset($item['icon']) && in_array($role, ['teacher', 'parent']))
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
              placeholder="بحث..."
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

        <!-- Child Selector (Parent only) -->
        @if($role === 'parent' && isset($parentChildren) && $parentChildren->count() > 0)
          <div class="relative hidden md:block" x-data="childSelector()" x-init="init()">
            <button @click="open = !open"
                    class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-{{ $brandColor }}-50 border border-gray-200 transition-colors"
                    :class="{ 'bg-{{ $brandColor }}-50 border-{{ $brandColor }}-200': open }">
              @if(isset($selectedChild) && $selectedChild)
                <x-avatar :user="$selectedChild->user" size="xs" userType="student" />
                <span class="text-sm font-medium text-gray-700 max-w-[120px] truncate">{{ $selectedChild->user->name ?? $selectedChild->first_name }}</span>
              @else
                <div class="w-6 h-6 rounded-full bg-{{ $brandColor }}-100 flex items-center justify-center">
                  <i class="ri-team-line text-{{ $brandColor }}-600 text-sm"></i>
                </div>
                <span class="text-sm font-medium text-gray-700">جميع الأبناء</span>
              @endif
              <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
            </button>

            <!-- Dropdown -->
            <div x-show="open"
                 x-cloak
                 @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="absolute left-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">

              <div class="p-2 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-medium text-gray-500 px-2">اختر الابن لعرض بياناته</p>
              </div>

              <div class="p-2 max-h-80 overflow-y-auto">
                <!-- All Children Option -->
                <button @click="selectChild('all')"
                        class="w-full flex items-center gap-3 p-3 rounded-lg transition-colors {{ (!isset($selectedChild) || !$selectedChild) ? 'bg-' . $brandColor . '-50 border border-' . $brandColor . '-200' : 'hover:bg-gray-50' }}">
                  <div class="w-10 h-10 rounded-full bg-{{ $brandColor }}-100 flex items-center justify-center flex-shrink-0">
                    <i class="ri-team-line text-{{ $brandColor }}-600 text-lg"></i>
                  </div>
                  <div class="flex-1 text-right">
                    <p class="text-sm font-medium text-gray-900">جميع الأبناء</p>
                    <p class="text-xs text-gray-500">عرض بيانات {{ $parentChildren->count() }} {{ $parentChildren->count() > 2 ? 'أبناء' : ($parentChildren->count() == 2 ? 'ابنين' : 'ابن') }}</p>
                  </div>
                  @if(!isset($selectedChild) || !$selectedChild)
                    <i class="ri-checkbox-circle-fill text-{{ $brandColor }}-600 text-lg"></i>
                  @endif
                </button>

                <div class="my-2 border-t border-gray-100"></div>

                <!-- Individual Children -->
                @foreach($parentChildren as $child)
                  <button @click="selectChild('{{ $child->id }}')"
                          class="w-full flex items-center gap-3 p-3 rounded-lg transition-colors {{ (isset($selectedChild) && $selectedChild && $selectedChild->id == $child->id) ? 'bg-' . $brandColor . '-50 border border-' . $brandColor . '-200' : 'hover:bg-gray-50' }}">
                    <x-avatar :user="$child->user" size="sm" userType="student" :gender="$child->gender ?? 'male'" />
                    <div class="flex-1 text-right">
                      <p class="text-sm font-medium text-gray-900">{{ $child->user->name ?? $child->first_name }}</p>
                      <p class="text-xs text-gray-500">{{ $child->student_code ?? 'طالب' }}</p>
                    </div>
                    @if(isset($selectedChild) && $selectedChild && $selectedChild->id == $child->id)
                      <i class="ri-checkbox-circle-fill text-{{ $brandColor }}-600 text-lg"></i>
                    @endif
                  </button>
                @endforeach
              </div>
            </div>
          </div>

          <script>
            function childSelector() {
              return {
                open: false,
                init() {
                  // Close on escape
                  this.$watch('open', value => {
                    if (value) {
                      document.addEventListener('keydown', this.handleEscape.bind(this));
                    } else {
                      document.removeEventListener('keydown', this.handleEscape.bind(this));
                    }
                  });
                },
                handleEscape(e) {
                  if (e.key === 'Escape') this.open = false;
                },
                selectChild(childId) {
                  // Update session via AJAX
                  fetch('{{ route("parent.select-child", ["subdomain" => $subdomain]) }}', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': '{{ csrf_token() }}',
                      'Accept': 'application/json',
                    },
                    body: JSON.stringify({ child_id: childId })
                  })
                  .then(response => response.json())
                  .then(data => {
                    if (data.success) {
                      // Reload current page to reflect changes
                      window.location.reload();
                    }
                  })
                  .catch(error => {
                    console.error('Error selecting child:', error);
                  });

                  this.open = false;
                }
              };
            }
          </script>
        @endif

        <!-- Notifications -->
        @livewire('notification-center')

        <!-- Messages -->
        <a href="/chats"
           class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200"
           aria-label="فتح الرسائل">
          <i class="ri-message-2-line text-xl"></i>
          <span id="unread-count-badge" class="absolute top-0 left-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-green-600 rounded-full hidden">
            0
          </span>
        </a>

        <!-- User Dropdown (hidden on mobile - sidebar has user widget) -->
        <div class="relative hidden md:block" x-data="{ open: false }">
          <button @click="open = !open"
                  class="flex items-center px-3 py-2 text-sm rounded-lg hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $brandColor }}-500"
                  aria-expanded="false"
                  aria-haspopup="true">
            <div class="flex items-center space-x-3 space-x-reverse">
              <x-avatar
                :user="$user"
                size="sm"
                :userType="$userAvatarType"
                :gender="$userGender" />
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
                $isAdminOrSuperAdmin = $user && ($user->isAdmin() || $user->isSuperAdmin());
              @endphp
              @if(!$isAdminOrSuperAdmin)
              <a href="{{ $profileRoute }}"
                 class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                 role="menuitem">
                <i class="ri-user-line ml-2"></i>
                الملف الشخصي
              </a>
              <div class="border-t border-gray-100"></div>
              @endif
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
        <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="md:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors"
                aria-label="فتح قائمة التنقل"
                :aria-expanded="mobileMenuOpen">
          <i x-show="!mobileMenuOpen" class="ri-menu-line text-xl text-gray-700"></i>
          <i x-show="mobileMenuOpen" x-cloak class="ri-close-line text-xl text-gray-700"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile Navigation Drawer -->
  <div x-show="mobileMenuOpen"
       x-cloak
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       @click="mobileMenuOpen = false"
       class="md:hidden fixed inset-0 z-30 bg-black/50"></div>

  <div x-show="mobileMenuOpen"
       x-cloak
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="translate-x-full"
       @click.stop
       class="md:hidden fixed top-0 right-0 bottom-0 w-[300px] max-w-[85vw] bg-white shadow-xl z-50 flex flex-col">

    <!-- Mobile Menu Header -->
    <div class="flex items-center justify-between p-4 border-b bg-gray-50 flex-shrink-0">
      <div class="flex items-center gap-3">
        <x-avatar :user="$user" size="sm" :userType="$userAvatarType" :gender="$userGender" />
        <div>
          <p class="text-sm font-bold text-gray-900">{{ $displayName }}</p>
          <p class="text-xs text-gray-500">{{ $roleLabel }}</p>
        </div>
      </div>
      <button @click="mobileMenuOpen = false"
              class="p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-gray-200 text-gray-600">
        <i class="ri-close-line text-xl"></i>
      </button>
    </div>

    <!-- Mobile Navigation Links -->
    <nav class="p-4 flex-1 overflow-y-auto">
      <div class="space-y-1">
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
            $isCourseRoute = $item['route'] === 'courses.index';
            $isQuranCircleRoute = $item['route'] === 'quran-circles.index';
            $isQuranTeacherRoute = $item['route'] === 'quran-teachers.index';
            $isInteractiveCourseRoute = $item['route'] === 'interactive-courses.index';
            $isAcademicTeacherRoute = $item['route'] === 'academic-teachers.index';

            // Determine icon for nav items
            $navIcon = isset($item['icon']) ? $item['icon'] :
                       ($isCourseRoute ? 'ri-play-circle-line' :
                       ($isQuranCircleRoute ? 'ri-quill-pen-line' :
                       ($isQuranTeacherRoute ? 'ri-user-star-line' :
                       ($isInteractiveCourseRoute ? 'ri-live-line' :
                       ($isAcademicTeacherRoute ? 'ri-user-settings-line' : 'ri-arrow-left-line')))));

            // Determine primary color for mobile
            $mobileActiveColorClass = $isCourseRoute ? 'text-cyan-600 bg-cyan-50' :
                                      ($isQuranCircleRoute ? 'text-green-600 bg-green-50' :
                                      ($isQuranTeacherRoute ? 'text-yellow-600 bg-yellow-50' :
                                      ($isInteractiveCourseRoute ? 'text-blue-600 bg-blue-50' :
                                      ($isAcademicTeacherRoute ? 'text-violet-600 bg-violet-50' : $brandColorClass . ' bg-gray-50'))));

            $mobileHoverColorClass = $isCourseRoute ? 'hover:text-cyan-600 hover:bg-cyan-50' :
                                     ($isQuranCircleRoute ? 'hover:text-green-600 hover:bg-green-50' :
                                     ($isQuranTeacherRoute ? 'hover:text-yellow-600 hover:bg-yellow-50' :
                                     ($isInteractiveCourseRoute ? 'hover:text-blue-600 hover:bg-blue-50' :
                                     ($isAcademicTeacherRoute ? 'hover:text-violet-600 hover:bg-violet-50' : 'hover:' . $brandColorClass . ' hover:bg-gray-50'))));
          @endphp
          <a href="{{ $itemRoute }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] font-medium {{ $isActive ? $mobileActiveColorClass : 'text-gray-700' }} {{ $mobileHoverColorClass }} rounded-lg transition-all duration-200">
            <i class="{{ $navIcon }} text-xl"></i>
            <span>{{ $item['label'] }}</span>
          </a>
        @endforeach
      </div>

      <!-- Divider -->
      <div class="my-4 border-t border-gray-200"></div>

      <!-- Quick Actions -->
      <div class="space-y-1">
        @php
          $profileRoute = Route::has($role . '.profile') ? route($role . '.profile', ['subdomain' => $subdomain]) : '#';
        @endphp

        <a href="{{ $profileRoute }}"
           class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
          <i class="ri-user-line text-xl"></i>
          <span>الملف الشخصي</span>
        </a>

        @if($role === 'teacher')
          @if($user && $user->isQuranTeacher())
            <a href="/teacher-panel" target="_blank"
               class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
              <i class="ri-apps-2-line text-xl"></i>
              <span>لوحة التحكم</span>
            </a>
          @elseif($user && $user->isAcademicTeacher())
            <a href="/academic-teacher-panel" target="_blank"
               class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
              <i class="ri-apps-2-line text-xl"></i>
              <span>لوحة التحكم</span>
            </a>
          @endif
        @endif
      </div>

      @if($role === 'student')
        <!-- Divider -->
        <div class="my-4 border-t border-gray-200"></div>

        <!-- Student Sidebar Items -->
        <div class="space-y-1">
          <!-- Section: Profile Management -->
          <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase">إدارة الملف الشخصي</p>

          <a href="{{ route('student.profile.edit', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.profile.edit') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-edit-line text-xl"></i>
            <span>تعديل الملف الشخصي</span>
          </a>

          <!-- Section: Learning Progress -->
          <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase mt-4">التقدم الدراسي</p>

          <a href="{{ route('student.calendar', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.calendar') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-calendar-line text-xl"></i>
            <span>التقويم والجلسات</span>
          </a>

          <a href="{{ route('student.homework.index', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.homework.*') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-file-list-3-line text-xl"></i>
            <span>الواجبات</span>
          </a>

          <a href="{{ route('student.quizzes', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.quizzes') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-questionnaire-line text-xl"></i>
            <span>الاختبارات</span>
          </a>

          <a href="{{ route('student.certificates', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.certificates') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-medal-line text-xl"></i>
            <span>الشهادات</span>
          </a>

          <!-- Section: Subscriptions & Payments -->
          <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase mt-4">الاشتراكات والمدفوعات</p>

          <a href="{{ route('student.subscriptions', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.subscriptions') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-wallet-3-line text-xl"></i>
            <span>الاشتراكات</span>
          </a>

          <a href="{{ route('student.payments', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.payments') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-bill-line text-xl"></i>
            <span>سجل المدفوعات</span>
          </a>
        </div>
      @endif
    </nav>

    <!-- Mobile Menu Footer -->
    <div class="p-4 border-t bg-white flex-shrink-0">
      @php
        $logoutRoute = Route::has('logout') ? route('logout', ['subdomain' => $subdomain]) : '/logout';
      @endphp
      <form method="POST" action="{{ $logoutRoute }}">
        @csrf
        <button type="submit"
                class="flex items-center justify-center gap-2 w-full px-4 py-3 min-h-[48px] bg-red-50 text-red-700 rounded-lg font-medium hover:bg-red-100 transition-colors">
          <i class="ri-logout-box-line text-xl"></i>
          <span>تسجيل الخروج</span>
        </button>
      </form>
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

  return true;
}

document.addEventListener('DOMContentLoaded', function() {
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
});
</script>
