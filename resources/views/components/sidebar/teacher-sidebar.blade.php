@php
  $teacher = auth()->user()->isQuranTeacher()
            ? auth()->user()->quranTeacherProfile
            : auth()->user()->academicTeacherProfile;
  $isQuran = auth()->user()->isQuranTeacher();
  $teacherType = $isQuran ? 'quran_teacher' : 'academic_teacher';
  $teacherGender = $teacher?->gender ?? auth()->user()?->gender ?? 'male';
  $displayName = $teacher->full_name ?? auth()->user()->name;
  $roleLabel = $teacher->teacher_code ?? ($isQuran ? __('teacher.sidebar.quran_teacher') : __('teacher.sidebar.academic_teacher'));
@endphp

<x-sidebar.container sidebar-id="teacher-sidebar" storage-key="teacherSidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="auth()->user()"
    :user-type="$teacherType"
    :display-name="$displayName"
    :role-label="$roleLabel"
    :gender="$teacherGender"
    :profile-route="route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="{{ __('teacher.sidebar.navigation_label') }}">
    <div class="space-y-2">

      <!-- Profile Management -->
      <x-sidebar.nav-section :title="__('teacher.sidebar.profile_management')">
        <x-sidebar.nav-item
          :href="route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          :label="__('teacher.sidebar.profile')"
          icon="ri-user-line"
          :active="request()->routeIs('teacher.profile')" />

        <x-sidebar.nav-item
          :href="route('teacher.profile.edit', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          :label="__('teacher.sidebar.edit_profile')"
          icon="ri-edit-line"
          :active="request()->routeIs('teacher.profile.edit')" />
      </x-sidebar.nav-section>

      <!-- Teaching Management -->
      <x-sidebar.nav-section :title="__('teacher.sidebar.teaching_management')">
        @if($isQuran)
          <x-sidebar.nav-item
            :href="route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            :label="__('teacher.sidebar.individual_circles')"
            icon="ri-user-star-line"
            :active="request()->routeIs('teacher.individual-circles.*') || request()->routeIs('individual-circles.*')" />

          <x-sidebar.nav-item
            :href="route('teacher.group-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            :label="__('teacher.sidebar.group_circles')"
            icon="ri-group-line"
            :active="request()->routeIs('teacher.group-circles.*')" />

          <x-sidebar.nav-item
            :href="route('teacher.trial-sessions.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            :label="__('teacher.sidebar.trial_sessions')"
            icon="ri-user-add-line"
            :active="request()->routeIs('teacher.trial-sessions.*')" />
        @else
          <x-sidebar.nav-item
            :href="route('teacher.academic.lessons.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            :label="__('teacher.sidebar.private_lessons')"
            icon="ri-user-3-line"
            :active="request()->routeIs('teacher.academic.lessons.*')" />

          <x-sidebar.nav-item
            :href="route('teacher.interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            :label="__('teacher.sidebar.interactive_courses')"
            icon="ri-book-open-line"
            :active="request()->routeIs('teacher.interactive-courses.*')" />
        @endif
      </x-sidebar.nav-section>

      <!-- Financial Management -->
      <x-sidebar.nav-section :title="__('teacher.sidebar.financial_management')">
        <x-sidebar.nav-item
          :href="route('teacher.earnings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          :label="__('teacher.sidebar.monthly_earnings')"
          icon="ri-money-dollar-circle-line"
          :active="request()->routeIs('teacher.earnings')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
