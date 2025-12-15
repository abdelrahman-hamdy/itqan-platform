@php
  $teacher = auth()->user()->isQuranTeacher()
            ? auth()->user()->quranTeacherProfile
            : auth()->user()->academicTeacherProfile;
  $isQuran = auth()->user()->isQuranTeacher();
  $teacherType = $isQuran ? 'quran_teacher' : 'academic_teacher';
  $teacherGender = $teacher?->gender ?? auth()->user()?->gender ?? 'male';
  $displayName = $teacher->full_name ?? auth()->user()->name;
  $roleLabel = $teacher->teacher_code ?? ($isQuran ? 'معلم قرآن' : 'معلم أكاديمي');
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
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="قائمة التنقل الشخصية">
    <div class="space-y-2">

      <!-- Profile Management -->
      <x-sidebar.nav-section title="إدارة الملف الشخصي">
        <x-sidebar.nav-item
          :href="route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          label="الملف الشخصي"
          icon="ri-user-line"
          :active="request()->routeIs('teacher.profile')" />

        <x-sidebar.nav-item
          :href="route('teacher.profile.edit', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          label="تعديل الملف الشخصي"
          icon="ri-edit-line"
          :active="request()->routeIs('teacher.profile.edit')" />
      </x-sidebar.nav-section>

      <!-- Teaching Management -->
      <x-sidebar.nav-section title="إدارة التدريس">
        @if($isQuran)
          <x-sidebar.nav-item
            :href="route('teacher.individual-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            label="الحلقات الفردية"
            icon="ri-user-star-line"
            :active="request()->routeIs('teacher.individual-circles.*') || request()->routeIs('individual-circles.*')" />

          <x-sidebar.nav-item
            :href="route('teacher.group-circles.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            label="الحلقات الجماعية"
            icon="ri-group-line"
            :active="request()->routeIs('teacher.group-circles.*')" />
        @else
          <x-sidebar.nav-item
            :href="route('teacher.academic.lessons.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            label="الدروس الخاصة"
            icon="ri-user-3-line"
            :active="request()->routeIs('teacher.academic.lessons.*')" />

          <x-sidebar.nav-item
            :href="route('teacher.interactive-courses.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
            label="الدورات التفاعلية"
            icon="ri-book-open-line"
            :active="request()->routeIs('teacher.interactive-courses.*')" />
        @endif
      </x-sidebar.nav-section>

      <!-- Financial Management -->
      <x-sidebar.nav-section title="الإدارة المالية">
        <x-sidebar.nav-item
          :href="route('teacher.earnings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          label="الأرباح الشهرية"
          icon="ri-money-dollar-circle-line"
          :active="request()->routeIs('teacher.earnings')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
