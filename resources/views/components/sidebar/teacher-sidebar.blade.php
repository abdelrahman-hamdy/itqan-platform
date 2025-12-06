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
    :gender="$teacherGender" />

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
        <x-sidebar.nav-item
          :href="route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          :label="$isQuran ? 'طلاب الحلقات' : 'طلاب الدورات'"
          icon="ri-group-line"
          :active="request()->routeIs('teacher.students')" />

        <x-sidebar.nav-item
          :href="route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])"
          :label="$isQuran ? 'الجدول والمواعيد' : 'جدول المواعيد'"
          icon="ri-calendar-schedule-line"
          :active="request()->routeIs('teacher.schedule.*')" />

        @if($isQuran)
          <x-sidebar.nav-item
            href="/teacher-panel/quran-trial-requests"
            label="طلبات الجلسات التجريبية"
            icon="ri-user-add-line"
            :external="true" />

          <x-sidebar.nav-item
            href="/teacher-panel/quran-subscriptions"
            label="اشتراكات طلابي"
            icon="ri-book-open-line"
            :external="true" />

          <x-sidebar.nav-item
            href="/teacher-panel/quran-sessions"
            label="جلسات القرآن"
            icon="ri-time-line"
            :external="true" />
        @else
          <x-sidebar.nav-item
            href="#"
            label="الجلسات والدروس"
            icon="ri-time-line" />

          <x-sidebar.nav-item
            href="#"
            label="الواجبات والاختبارات"
            icon="ri-file-list-3-line" />
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

      <!-- Reports & Analytics -->
      <x-sidebar.nav-section title="التقارير والتحليلات">
        <x-sidebar.nav-item
          href="#"
          label="تقرير الأداء"
          icon="ri-bar-chart-line" />

        <x-sidebar.nav-item
          href="#"
          :label="$isQuran ? 'تقدم الطلاب في الحفظ' : 'تقدم الطلاب الأكاديمي'"
          icon="ri-line-chart-line" />

        <x-sidebar.nav-item
          href="#"
          label="التقييمات والمراجعات"
          icon="ri-star-line" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
