@php
    $user = auth()->user();
    $subdomain = $user->academy->subdomain ?? 'itqan-academy';
    $isAdmin = $user->isSuperAdmin() || $user->isAdmin() || $user->isAcademyAdmin();
    $displayName = $user->supervisorProfile?->full_name ?? $user->name;
    $roleLabel = $isAdmin ? $user->getUserTypeLabel() : ($user->supervisorProfile?->supervisor_code ?? __('supervisor.sidebar.supervisor'));
    $supervisorGender = $user->supervisorProfile?->gender ?? $user->gender ?? 'male';

    // Admins see all sections; supervisors see only their assigned teacher types
    $hasQuranTeachers = $isAdmin || !empty($user->supervisorProfile?->getAssignedQuranTeacherIds());
    $hasAcademicTeachers = $isAdmin || !empty($user->supervisorProfile?->getAssignedAcademicTeacherIds());
@endphp

<x-sidebar.container sidebar-id="supervisor-sidebar" storage-key="supervisorSidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="$user"
    user-type="supervisor"
    :display-name="$displayName"
    :role-label="$roleLabel"
    :gender="$supervisorGender"
    :profile-route="route('manage.profile', ['subdomain' => $subdomain])" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="{{ __('supervisor.sidebar.navigation_label') }}">
    <div class="space-y-2">

      <!-- Overview -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.overview')">
        <x-sidebar.nav-item
          :href="route('manage.dashboard', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.dashboard')"
          icon="ri-dashboard-line"
          :active="request()->routeIs('manage.dashboard')" />

        <x-sidebar.nav-item
          :href="route('manage.teachers.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.my_teachers')"
          icon="ri-team-line"
          :active="request()->routeIs('manage.teachers.*')" />

        <x-sidebar.nav-item
          :href="route('manage.students.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.my_students')"
          icon="ri-group-2-line"
          :active="request()->routeIs('manage.students.*')" />
      </x-sidebar.nav-section>

      <!-- Quran Programs -->
      @if($hasQuranTeachers)
      <x-sidebar.nav-section :title="__('supervisor.sidebar.quran_programs')">
        <x-sidebar.nav-item
          :href="route('manage.group-circles.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.group_circles')"
          icon="ri-group-line"
          :active="request()->routeIs('manage.group-circles.*')" />

        <x-sidebar.nav-item
          :href="route('manage.individual-circles.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.individual_circles')"
          icon="ri-user-star-line"
          :active="request()->routeIs('manage.individual-circles.*')" />

        <x-sidebar.nav-item
          :href="route('manage.trial-sessions.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.trial_sessions')"
          icon="ri-user-add-line"
          :active="request()->routeIs('manage.trial-sessions.*')" />
      </x-sidebar.nav-section>
      @endif

      <!-- Academic Programs -->
      @if($hasAcademicTeachers)
      <x-sidebar.nav-section :title="__('supervisor.sidebar.academic_programs')">
        <x-sidebar.nav-item
          :href="route('manage.academic-lessons.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.academic_lessons')"
          icon="ri-user-3-line"
          :active="request()->routeIs('manage.academic-lessons.*')" />

        <x-sidebar.nav-item
          :href="route('manage.interactive-courses.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.interactive_courses')"
          icon="ri-book-open-line"
          :active="request()->routeIs('manage.interactive-courses.*')" />
      </x-sidebar.nav-section>
      @endif

      <!-- Calendar & Monitoring -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.calendar_monitoring')">
        <x-sidebar.nav-item
          :href="route('manage.calendar.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.calendar')"
          icon="ri-calendar-schedule-line"
          :active="request()->routeIs('manage.calendar.*')" />

        <x-sidebar.nav-item
          :href="route('manage.sessions.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.sessions_management')"
          icon="ri-calendar-check-line"
          :active="request()->routeIs('manage.sessions.*')" />
      </x-sidebar.nav-section>

      <!-- Management -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.management')">
        <x-sidebar.nav-item
          :href="route('manage.teacher-earnings.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.teacher_earnings')"
          icon="ri-money-dollar-circle-line"
          :active="request()->routeIs('manage.teacher-earnings.*')" />

        <x-sidebar.nav-item
          :href="route('manage.quizzes.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.quizzes')"
          icon="ri-questionnaire-line"
          :active="request()->routeIs('manage.quizzes.*')" />

        <x-sidebar.nav-item
          :href="route('manage.session-reports.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.session_reports')"
          icon="ri-file-chart-line"
          :active="request()->routeIs('manage.session-reports.*')" />

        <x-sidebar.nav-item
          :href="route('manage.certificates.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.certificates')"
          icon="ri-award-line"
          :active="request()->routeIs('manage.certificates.*')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
