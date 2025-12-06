@php
  $user = auth()->user();
  $student = $user ? $user->studentProfile : null;
  $fullName = $student ?
             ($student->first_name && $student->last_name ? $student->first_name . ' ' . $student->last_name : $student->first_name) :
             ($user ? $user->name : 'زائر');
  $studentGender = $student?->gender ?? $user?->gender ?? 'male';
  $roleLabel = $student && $student->gradeLevel?->name ? 'المرحلة الدراسية: ' . $student->gradeLevel->name : ($student?->student_code ?? 'طالب');
@endphp

<x-sidebar.container sidebar-id="student-sidebar" storage-key="sidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="$user"
    user-type="student"
    :display-name="$fullName"
    :role-label="$roleLabel"
    :gender="$studentGender" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="قائمة التنقل الشخصية">
    <div class="space-y-2">

      <!-- Profile Management -->
      <x-sidebar.nav-section title="إدارة الملف الشخصي">
        <x-sidebar.nav-item
          :href="$user ? route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy']) : '#'"
          label="الملف الشخصي"
          icon="ri-user-line"
          :active="request()->routeIs('student.profile')" />

        <x-sidebar.nav-item
          :href="route('student.profile.edit', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          label="تعديل الملف الشخصي"
          icon="ri-edit-line"
          :active="request()->routeIs('student.profile.edit')" />
      </x-sidebar.nav-section>

      <!-- Learning Progress -->
      <x-sidebar.nav-section title="التقدم الدراسي">
        <x-sidebar.nav-item
          :href="route('student.calendar', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          label="التقويم والجلسات"
          icon="ri-calendar-line"
          :active="request()->routeIs('student.calendar')" />

        <x-sidebar.nav-item
          :href="route('student.homework.index', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          label="الواجبات"
          icon="ri-file-list-3-line"
          :active="request()->routeIs('student.homework.*')" />

        <x-sidebar.nav-item
          :href="route('student.quizzes', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          label="الاختبارات"
          icon="ri-questionnaire-line"
          :active="request()->routeIs('student.quizzes')" />

        <x-sidebar.nav-item
          :href="route('student.certificates', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          label="الشهادات"
          icon="ri-medal-line"
          :active="request()->routeIs('student.certificates')" />
      </x-sidebar.nav-section>

      <!-- Subscriptions & Payments -->
      <x-sidebar.nav-section title="الاشتراكات والمدفوعات">
        <x-sidebar.nav-item
          :href="route('student.subscriptions', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          label="الاشتراكات"
          icon="ri-wallet-3-line"
          :active="request()->routeIs('student.subscriptions')" />

        <x-sidebar.nav-item
          :href="route('student.payments', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          label="سجل المدفوعات"
          icon="ri-bill-line"
          :active="request()->routeIs('student.payments')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
