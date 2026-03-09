@php
    $user = auth()->user();
    $subdomain = $user->academy->subdomain ?? 'itqan-academy';
    $displayName = $user->supervisorProfile->full_name ?? $user->name;
    $roleLabel = $user->supervisorProfile->supervisor_code ?? __('supervisor.sidebar.supervisor');
    $supervisorGender = $user->supervisorProfile?->gender ?? $user->gender ?? 'male';

    $hasQuranTeachers = !empty($user->supervisorProfile?->getAssignedQuranTeacherIds());
    $hasAcademicTeachers = !empty($user->supervisorProfile?->getAssignedAcademicTeacherIds());
@endphp

<x-sidebar.container sidebar-id="supervisor-sidebar" storage-key="supervisorSidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="$user"
    user-type="supervisor"
    :display-name="$displayName"
    :role-label="$roleLabel"
    :gender="$supervisorGender"
    :profile-route="route('supervisor.profile', ['subdomain' => $subdomain])" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="{{ __('supervisor.sidebar.navigation_label') }}">
    <div class="space-y-2">

      <!-- Overview -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.overview')">
        <x-sidebar.nav-item
          :href="route('supervisor.dashboard', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.dashboard')"
          icon="ri-dashboard-line"
          :active="request()->routeIs('supervisor.dashboard')" />

        <x-sidebar.nav-item
          :href="route('supervisor.teachers.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.my_teachers')"
          icon="ri-team-line"
          :active="request()->routeIs('supervisor.teachers.*')" />
      </x-sidebar.nav-section>

      <!-- Quran Programs -->
      @if($hasQuranTeachers)
      <x-sidebar.nav-section :title="__('supervisor.sidebar.quran_programs')">
        <x-sidebar.nav-item
          :href="route('supervisor.group-circles.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.group_circles')"
          icon="ri-group-line"
          :active="request()->routeIs('supervisor.group-circles.*')" />

        <x-sidebar.nav-item
          :href="route('supervisor.individual-circles.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.individual_circles')"
          icon="ri-user-star-line"
          :active="request()->routeIs('supervisor.individual-circles.*')" />

        <x-sidebar.nav-item
          :href="route('supervisor.trial-sessions.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.trial_sessions')"
          icon="ri-user-add-line"
          :active="request()->routeIs('supervisor.trial-sessions.*')" />
      </x-sidebar.nav-section>
      @endif

      <!-- Academic Programs -->
      @if($hasAcademicTeachers)
      <x-sidebar.nav-section :title="__('supervisor.sidebar.academic_programs')">
        <x-sidebar.nav-item
          :href="route('supervisor.academic-lessons.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.academic_lessons')"
          icon="ri-user-3-line"
          :active="request()->routeIs('supervisor.academic-lessons.*')" />

        <x-sidebar.nav-item
          :href="route('supervisor.interactive-courses.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.interactive_courses')"
          icon="ri-book-open-line"
          :active="request()->routeIs('supervisor.interactive-courses.*')" />
      </x-sidebar.nav-section>
      @endif

      <!-- Calendar & Monitoring -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.calendar_monitoring')">
        <x-sidebar.nav-item
          :href="route('supervisor.calendar.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.calendar')"
          icon="ri-calendar-schedule-line"
          :active="request()->routeIs('supervisor.calendar.*')" />

        <x-sidebar.nav-item
          :href="route('supervisor.sessions-monitoring.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.sessions_monitoring')"
          icon="ri-live-line"
          :active="request()->routeIs('supervisor.sessions-monitoring.*')" />
      </x-sidebar.nav-section>

      <!-- Management -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.management')">
        <x-sidebar.nav-item
          :href="route('supervisor.quizzes.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.quizzes')"
          icon="ri-questionnaire-line"
          :active="request()->routeIs('supervisor.quizzes.*')" />

        <x-sidebar.nav-item
          :href="route('supervisor.session-reports.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.session_reports')"
          icon="ri-file-chart-line"
          :active="request()->routeIs('supervisor.session-reports.*')" />

        <x-sidebar.nav-item
          :href="route('supervisor.certificates.index', ['subdomain' => $subdomain])"
          :label="__('supervisor.sidebar.certificates')"
          icon="ri-award-line"
          :active="request()->routeIs('supervisor.certificates.*')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
