@props(['role' => 'student'])

@php
  $user = auth()->user();
  $academy = $user ? $user->academy : null;
  $academyName = $academy ? $academy->name : __('components.navigation.app.academy_default');
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
    $studentNavItems[] = ['route' => 'quran-circles.index', 'label' => __('components.navigation.app.student_nav.quran_circles'), 'activeRoutes' => ['quran-circles.index', 'quran-circles.show']];
  }
  if (Route::has('quran-teachers.index')) {
    $studentNavItems[] = ['route' => 'quran-teachers.index', 'label' => __('components.navigation.app.student_nav.quran_teachers'), 'activeRoutes' => ['quran-teachers.index', 'quran-teachers.show']];
  }
  if (Route::has('interactive-courses.index')) {
    $studentNavItems[] = ['route' => 'interactive-courses.index', 'label' => __('components.navigation.app.student_nav.interactive_courses'), 'activeRoutes' => ['interactive-courses.index', 'interactive-courses.show']];
  }
  if (Route::has('academic-teachers.index')) {
    $studentNavItems[] = ['route' => 'academic-teachers.index', 'label' => __('components.navigation.app.student_nav.academic_teachers'), 'activeRoutes' => ['academic-teachers.index', 'academic-teachers.show']];
  }
  if (Route::has('courses.index')) {
    $studentNavItems[] = ['route' => 'courses.index', 'label' => __('components.navigation.app.student_nav.recorded_courses'), 'activeRoutes' => ['courses.index', 'courses.show', 'courses.learn', 'lessons.show']];
  }

  // Teacher navigation items - links to Filament dashboard resources
  $teacherNavItems = [];

  if ($user && $user->isQuranTeacher()) {
    $teacherNavItems = [
      ['route' => null, 'href' => '/teacher-panel/quran-sessions', 'label' => __('components.navigation.app.teacher_nav.sessions_schedule'), 'icon' => 'ri-calendar-schedule-line', 'activeRoutes' => []],
      ['route' => null, 'href' => '/teacher-panel/quran-trial-requests', 'label' => __('components.navigation.app.teacher_nav.trial_sessions'), 'icon' => 'ri-user-add-line', 'activeRoutes' => []],
      ['route' => null, 'href' => '/teacher-panel/quran-session-reports', 'label' => __('components.navigation.app.teacher_nav.session_reports'), 'icon' => 'ri-file-chart-line', 'activeRoutes' => []],
    ];
  } elseif ($user && $user->isAcademicTeacher()) {
    $teacherNavItems = [
      ['route' => null, 'href' => '/academic-teacher-panel/academic-sessions', 'label' => __('components.navigation.app.teacher_nav.sessions_schedule'), 'icon' => 'ri-calendar-schedule-line', 'activeRoutes' => []],
      ['route' => null, 'href' => '/academic-teacher-panel/homework-submissions', 'label' => __('components.navigation.app.teacher_nav.homework'), 'icon' => 'ri-file-list-3-line', 'activeRoutes' => []],
      ['route' => null, 'href' => '/academic-teacher-panel/academic-session-reports', 'label' => __('components.navigation.app.teacher_nav.session_reports'), 'icon' => 'ri-file-chart-line', 'activeRoutes' => []],
    ];
  }

  // Parent navigation items
  $parentNavItems = [];

  if (Route::has('parent.dashboard')) {
    $parentNavItems[] = ['route' => 'parent.dashboard', 'label' => __('components.navigation.app.parent_nav.home'), 'icon' => 'ri-dashboard-line', 'activeRoutes' => ['parent.dashboard']];
  }
  if (Route::has('parent.sessions.upcoming')) {
    $parentNavItems[] = ['route' => 'parent.sessions.upcoming', 'label' => __('components.navigation.app.parent_nav.upcoming_sessions'), 'icon' => 'ri-calendar-event-line', 'activeRoutes' => ['parent.sessions.*']];
  }
  if (Route::has('parent.subscriptions.index')) {
    $parentNavItems[] = ['route' => 'parent.subscriptions.index', 'label' => __('components.navigation.app.parent_nav.subscriptions'), 'icon' => 'ri-file-list-line', 'activeRoutes' => ['parent.subscriptions.*']];
  }
  if (Route::has('parent.reports.progress')) {
    $parentNavItems[] = ['route' => 'parent.reports.progress', 'label' => __('components.navigation.app.parent_nav.reports'), 'icon' => 'ri-bar-chart-line', 'activeRoutes' => ['parent.reports.*']];
  }

  $navItems = match($role) {
    'teacher' => $teacherNavItems,
    'parent' => $parentNavItems,
    default => $studentNavItems,
  };

  // Get user profile info
  if ($role === 'student') {
    $profile = $user ? $user->studentProfile : null;
    $displayName = $profile ? ($profile->first_name ?? ($user ? $user->name : __('components.navigation.app.guest'))) : ($user ? $user->name : __('components.navigation.app.guest'));
    $roleLabel = __('components.navigation.app.roles.student');
    $userAvatarType = 'student';
    $userGender = $profile?->gender ?? $user?->gender ?? 'male';
  } elseif ($role === 'parent') {
    $profile = $user ? $user->parentProfile : null;
    $displayName = $profile ? $profile->getFullNameAttribute() : ($user ? $user->name : __('components.navigation.app.parent_user'));
    $roleLabel = __('components.navigation.app.roles.parent');
    $userAvatarType = 'parent';
    $userGender = $profile?->gender ?? $user?->gender ?? 'male';
  } else {
    $profile = $user && $user->isQuranTeacher()
              ? $user->quranTeacherProfile
              : ($user ? $user->academicTeacherProfile : null);
    $displayName = $profile ? ($profile->first_name ?? ($user ? $user->name : __('components.navigation.app.roles.teacher'))) : ($user ? $user->name : __('components.navigation.app.roles.teacher'));
    $roleLabel = $user && $user->isQuranTeacher() ? __('components.navigation.app.roles.quran_teacher') : __('components.navigation.app.roles.academic_teacher');
    $userAvatarType = $user && $user->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';
    $userGender = $profile?->gender ?? $user?->gender ?? 'male';
  }
@endphp

<nav id="navigation"
     x-data="{ mobileMenuOpen: false }"
     class="bg-white shadow-lg fixed top-0 left-0 right-0 z-40"
     role="navigation"
     aria-label="{{ __('components.navigation.app.main_navigation_label') }}">
  <div class="w-full px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16 md:h-20">

      <!-- Logo and Navigation -->
      <div class="flex items-center gap-8">
        <!-- Logo -->
        <div class="flex items-center">
          <x-academy-logo
            :academy="$academy"
            size="md"
            :showName="true"
            :href="route('academy.home', ['subdomain' => $subdomain])" />
        </div>

        <!-- Desktop Navigation -->
        <div class="hidden md:flex items-center {{ $role === 'teacher' ? 'gap-6' : 'gap-6' }} space-x-reverse">
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
              // Handle both route-based and href-based items
              $itemRoute = isset($item['href']) ? $item['href'] : (Route::has($item['route'] ?? '') ? route($item['route'], ['subdomain' => $subdomain]) : '#');
              $isCourseRoute = ($item['route'] ?? null) === 'courses.index';
              $isQuranCircleRoute = ($item['route'] ?? null) === 'quran-circles.index';
              $isQuranTeacherRoute = ($item['route'] ?? null) === 'quran-teachers.index';
              $isInteractiveCourseRoute = ($item['route'] ?? null) === 'interactive-courses.index';
              $isAcademicTeacherRoute = ($item['route'] ?? null) === 'academic-teachers.index';

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
               class="flex items-center gap-2 font-medium px-3 py-2 {{ $isActive ? $activeColorClass : 'text-gray-700' }} {{ $hoverColorClass }} rounded-lg transition-all duration-200 focus:outline-none">
              @if(isset($item['icon']) && in_array($role, ['teacher', 'parent']))
                <i class="{{ $item['icon'] }}"></i>
              @endif
              {{ $item['label'] }}
            </a>
          @endforeach
        </div>
      </div>

      <!-- Right Side Actions -->
      <div class="flex items-center gap-4">

        <!-- Search Button (Student only) -->
        @if($role === 'student')
          @php
            $searchRoute = Route::has('student.search') ? route('student.search', ['subdomain' => $subdomain]) : '/search';
          @endphp
          <div class="relative hidden md:block" x-data="{ searchOpen: false }">
            <!-- Search Icon Button -->
            <button
              @click="searchOpen = !searchOpen; $nextTick(() => { if(searchOpen) $refs.searchInput.focus() })"
              class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200"
              :class="{ 'bg-gray-100 text-gray-800': searchOpen }"
              aria-label="{{ __('components.navigation.app.search_label') }}"
              :aria-expanded="searchOpen">
              <i class="ri-search-line text-xl"></i>
            </button>

            <!-- Search Dropdown -->
            <div
              x-show="searchOpen"
              x-cloak
              @click.away="searchOpen = false"
              @keydown.escape.window="searchOpen = false"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 translate-y-1"
              x-transition:enter-end="opacity-100 translate-y-0"
              x-transition:leave="transition ease-in duration-150"
              x-transition:leave-start="opacity-100 translate-y-0"
              x-transition:leave-end="opacity-0 translate-y-1"
              class="absolute rtl:left-0 ltr:right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 p-4 z-50">
              <form
                id="nav-search-form"
                action="{{ $searchRoute }}"
                method="GET"
                onsubmit="return handleNavSearch(event)">
                <div class="relative flex items-center">
                  <input
                    type="text"
                    name="q"
                    id="nav-search-input"
                    x-ref="searchInput"
                    value="{{ request('q') }}"
                    placeholder="{{ __('components.navigation.app.search_placeholder') }}"
                    class="w-full py-2.5 px-4 pe-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-{{ $brandColor }}-500 focus:border-transparent text-sm"
                    aria-label="{{ __('components.navigation.app.search_label') }}"
                    required
                    minlength="1">
                  <button
                    type="submit"
                    class="absolute end-1 top-1/2 -translate-y-1/2 w-9 h-9 flex items-center justify-center text-gray-500 hover:text-{{ $brandColor }}-600 hover:bg-gray-100 rounded-md transition-colors"
                    aria-label="{{ __('components.navigation.app.search_button') }}">
                    <i class="ri-search-line text-lg"></i>
                  </button>
                </div>
                <p class="mt-2 text-xs text-gray-500 text-center">{{ __('components.navigation.app.search_hint') }}</p>
              </form>
            </div>
          </div>
        @endif

        <!-- Dashboard Link (Teacher only) -->
        @if($role === 'teacher')
          @if($user && $user->isQuranTeacher())
            <a href="/teacher-panel" target="_blank"
               class="hidden md:flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
              <i class="ri-apps-2-line"></i>
              {{ __('components.navigation.app.teacher_nav.dashboard') }}
            </a>
          @elseif($user && $user->isAcademicTeacher())
            <a href="/academic-teacher-panel" target="_blank"
               class="hidden md:flex items-center gap-2 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
              <i class="ri-apps-2-line"></i>
              {{ __('components.navigation.app.teacher_nav.dashboard') }}
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
                <span class="text-sm font-medium text-gray-700">{{ __('components.navigation.app.child_selector.all_children') }}</span>
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
                 class="absolute rtl:left-0 ltr:right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">

              <div class="p-2 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-medium text-gray-500 px-2">{{ __('components.navigation.app.child_selector.select_child') }}</p>
              </div>

              <div class="p-2 max-h-80 overflow-y-auto">
                <!-- All Children Option -->
                <button @click="selectChild('all')"
                        class="w-full flex items-center gap-3 p-3 rounded-lg transition-colors {{ (!isset($selectedChild) || !$selectedChild) ? 'bg-' . $brandColor . '-50 border border-' . $brandColor . '-200' : 'hover:bg-gray-50' }}">
                  <div class="w-10 h-10 rounded-full bg-{{ $brandColor }}-100 flex items-center justify-center flex-shrink-0">
                    <i class="ri-team-line text-{{ $brandColor }}-600 text-lg"></i>
                  </div>
                  <div class="flex-1 text-end">
                    <p class="text-sm font-medium text-gray-900">{{ __('components.navigation.app.child_selector.all_children') }}</p>
                    <p class="text-xs text-gray-500">{{ __('components.navigation.app.child_selector.view_data_for') }} {{ $parentChildren->count() }} {{ $parentChildren->count() > 2 ? __('components.navigation.app.child_selector.children') : ($parentChildren->count() == 2 ? __('components.navigation.app.child_selector.two_children') : __('components.navigation.app.child_selector.one_child')) }}</p>
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
                    <div class="flex-1 text-end">
                      <p class="text-sm font-medium text-gray-900">{{ $child->user->name ?? $child->first_name }}</p>
                      <p class="text-xs text-gray-500">{{ $child->student_code ?? __('components.navigation.app.student_label') }}</p>
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
                selectChildUrl: '{{ route("parent.select-child", ["subdomain" => $subdomain]) }}',
                csrfToken: '{{ csrf_token() }}',
                init() {
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
                  fetch(this.selectChildUrl, {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json',
                      'X-CSRF-TOKEN': this.csrfToken,
                      'Accept': 'application/json',
                    },
                    body: JSON.stringify({ child_id: childId })
                  })
                  .then(response => response.json())
                  .then(data => {
                    if (data.success) {
                      window.location.reload();
                    }
                  })
                  .catch(() => {});
                  this.open = false;
                }
              };
            }
          </script>
        @endif

        <!-- Language Switcher -->
        <x-ui.language-switcher :dropdown="false" :showLabel="false" size="sm" class="hidden md:flex" />

        <!-- Notifications -->
        @livewire('notification-center')

        <!-- Messages -->
        <a href="/chats"
           class="relative w-10 h-10 flex items-center justify-center text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-full transition-all duration-200"
           aria-label="{{ __('components.navigation.app.messages.open_messages') }}">
          <i class="ri-message-2-line text-xl"></i>
          <span id="unread-count-badge" class="absolute top-0 start-0 inline-flex items-center justify-center min-w-[18px] h-[18px] px-0.5 text-xs font-bold text-white transform translate-x-1/2 -translate-y-1/2 bg-green-600 rounded-full" style="display:none">
          </span>
        </a>
        <script>
        (function() {
            function updateUnreadCount() {
                fetch('/api/chat/unreadCount', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin'
                })
                .then(r => r.json())
                .then(data => {
                    const badge = document.getElementById('unread-count-badge');
                    if (badge && data.unread_count !== undefined) {
                        if (data.unread_count > 0) {
                            badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                            badge.style.display = 'inline-flex';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(() => {});
            }
            updateUnreadCount();
            setInterval(updateUnreadCount, 10000);
        })();
        </script>

        <!-- User Dropdown (hidden on mobile - sidebar has user widget) -->
        <div class="relative hidden md:block" x-data="{ open: false }">
          <button @click="open = !open"
                  class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-gray-100 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-{{ $brandColor }}-500"
                  aria-expanded="false"
                  aria-haspopup="true">
            <x-avatar
              :user="$user"
              size="sm"
              :userType="$userAvatarType"
              :gender="$userGender" />
            <div class="text-end hidden lg:block">
              <p class="text-sm font-medium text-gray-900 leading-tight">{{ $displayName }}</p>
              <p class="text-xs text-gray-500 leading-tight">{{ $roleLabel }}</p>
            </div>
            <i class="ri-arrow-down-s-line text-gray-400 transition-transform" :class="{ 'rotate-180': open }"></i>
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
               class="rtl:origin-top-left ltr:origin-top-right absolute rtl:left-0 ltr:right-0 mt-2 w-56 rounded-xl shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden"
               role="menu"
               aria-orientation="vertical">
            <div class="py-1" role="none">
              @php
                $profileRoute = Route::has($role . '.profile') ? route($role . '.profile', ['subdomain' => $subdomain]) : '#';
                $logoutRoute = Route::has('logout') ? route('logout', ['subdomain' => $subdomain]) : '/logout';
                $isAdminOrSuperAdminOrSupervisor = $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->isSupervisor());
              @endphp
              @if(!$isAdminOrSuperAdminOrSupervisor)
              <a href="{{ $profileRoute }}"
                 class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50"
                 role="menuitem">
                <i class="ri-user-line text-gray-400"></i>
                {{ __('components.navigation.app.user_menu.profile') }}
              </a>
              <div class="border-t border-gray-100"></div>
              @endif
              <form method="POST" action="{{ $logoutRoute }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-red-700 hover:bg-red-50"
                        role="menuitem">
                  <i class="ri-logout-box-line"></i>
                  {{ __('components.navigation.app.user_menu.logout') }}
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="md:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-gray-100 transition-colors"
                aria-label="{{ __('components.navigation.app.mobile_menu.open_navigation') }}"
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
            // Handle both route-based and href-based items
            $itemRoute = isset($item['href']) ? $item['href'] : (Route::has($item['route'] ?? '') ? route($item['route'], ['subdomain' => $subdomain]) : '#');
            $isCourseRoute = ($item['route'] ?? null) === 'courses.index';
            $isQuranCircleRoute = ($item['route'] ?? null) === 'quran-circles.index';
            $isQuranTeacherRoute = ($item['route'] ?? null) === 'quran-teachers.index';
            $isInteractiveCourseRoute = ($item['route'] ?? null) === 'interactive-courses.index';
            $isAcademicTeacherRoute = ($item['route'] ?? null) === 'academic-teachers.index';

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
          $mobileIsAdminOrSuperAdminOrSupervisor = $user && ($user->isAdmin() || $user->isSuperAdmin() || $user->isSupervisor());
        @endphp

        @if(!$mobileIsAdminOrSuperAdminOrSupervisor)
        <a href="{{ $profileRoute }}"
           class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
          <i class="ri-user-line text-xl"></i>
          <span>{{ __('components.navigation.app.user_menu.profile') }}</span>
        </a>
        @endif

        @if($role === 'teacher')
          @if($user && $user->isQuranTeacher())
            <a href="/teacher-panel" target="_blank"
               class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
              <i class="ri-apps-2-line text-xl"></i>
              <span>{{ __('components.navigation.app.teacher_nav.dashboard') }}</span>
            </a>
          @elseif($user && $user->isAcademicTeacher())
            <a href="/academic-teacher-panel" target="_blank"
               class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
              <i class="ri-apps-2-line text-xl"></i>
              <span>{{ __('components.navigation.app.teacher_nav.dashboard') }}</span>
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
          <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase">{{ __('components.navigation.app.mobile_menu.profile_management') }}</p>

          <a href="{{ route('student.profile.edit', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.profile.edit') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-edit-line text-xl"></i>
            <span>{{ __('components.navigation.app.mobile_menu.edit_profile') }}</span>
          </a>

          <!-- Section: Learning Progress -->
          <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase mt-4">{{ __('components.navigation.app.mobile_menu.learning_progress') }}</p>

          <a href="{{ route('student.calendar', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.calendar') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-calendar-line text-xl"></i>
            <span>{{ __('components.navigation.app.mobile_menu.calendar_sessions') }}</span>
          </a>

          <a href="{{ route('student.homework.index', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.homework.*') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-file-list-3-line text-xl"></i>
            <span>{{ __('components.navigation.app.mobile_menu.homework') }}</span>
          </a>

          <a href="{{ route('student.quizzes', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.quizzes') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-questionnaire-line text-xl"></i>
            <span>{{ __('components.navigation.app.mobile_menu.quizzes') }}</span>
          </a>

          <a href="{{ route('student.certificates', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.certificates') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-medal-line text-xl"></i>
            <span>{{ __('components.navigation.app.mobile_menu.certificates') }}</span>
          </a>

          <!-- Section: Subscriptions & Payments -->
          <p class="px-4 py-2 text-xs font-semibold text-gray-400 uppercase mt-4">{{ __('components.navigation.app.mobile_menu.subscriptions_payments') }}</p>

          <a href="{{ route('student.subscriptions', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.subscriptions') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-wallet-3-line text-xl"></i>
            <span>{{ __('components.navigation.app.mobile_menu.subscriptions') }}</span>
          </a>

          <a href="{{ route('student.payments', ['subdomain' => $subdomain]) }}"
             class="flex items-center gap-3 px-4 py-3 min-h-[48px] text-gray-700 hover:bg-gray-100 rounded-lg transition-colors {{ request()->routeIs('student.payments') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-bill-line text-xl"></i>
            <span>{{ __('components.navigation.app.mobile_menu.payment_history') }}</span>
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
          <span>{{ __('components.navigation.app.user_menu.logout') }}</span>
        </button>
      </form>
    </div>
  </div>
</nav>

<!-- Alpine.js x-cloak styles -->
<style>
  [x-cloak] { display: none !important; }
</style>
