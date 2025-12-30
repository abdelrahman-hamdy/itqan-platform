@php
  $user = auth()->user();
  $student = $user ? $user->studentProfile : null;
  $fullName = $student ?
             ($student->first_name && $student->last_name ? $student->first_name . ' ' . $student->last_name : $student->first_name) :
             ($user ? $user->name : __('student.guest'));
  $studentGender = $student?->gender ?? $user?->gender ?? 'male';
  $roleLabel = $student && $student->gradeLevel?->name ? __('student.grade_level') . ': ' . $student->gradeLevel->name : ($student?->student_code ?? __('student.role'));
@endphp

<x-sidebar.container sidebar-id="student-sidebar" storage-key="sidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="$user"
    user-type="student"
    :display-name="$fullName"
    :role-label="$roleLabel"
    :gender="$studentGender"
    :profile-route="$user ? route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy']) : '#'" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="{{ __('components.sidebar.personal_navigation') }}">
    <div class="space-y-2">

      <!-- Profile Management -->
      <x-sidebar.nav-section :title="__('components.sidebar.profile_management')">
        <x-sidebar.nav-item
          :href="$user ? route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy']) : '#'"
          :label="__('components.sidebar.profile')"
          icon="ri-user-line"
          :active="request()->routeIs('student.profile')" />

        <x-sidebar.nav-item
          :href="route('student.profile.edit', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          :label="__('components.sidebar.edit_profile')"
          icon="ri-edit-line"
          :active="request()->routeIs('student.profile.edit')" />
      </x-sidebar.nav-section>

      <!-- Learning Progress -->
      <x-sidebar.nav-section :title="__('components.sidebar.learning_progress')">
        <x-sidebar.nav-item
          :href="route('student.calendar', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          :label="__('components.sidebar.calendar_sessions')"
          icon="ri-calendar-line"
          :active="request()->routeIs('student.calendar')" />

        <x-sidebar.nav-item
          :href="route('student.homework.index', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          :label="__('components.sidebar.homework')"
          icon="ri-file-list-3-line"
          :active="request()->routeIs('student.homework.*')" />

        <x-sidebar.nav-item
          :href="route('student.quizzes', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          :label="__('components.sidebar.quizzes')"
          icon="ri-questionnaire-line"
          :active="request()->routeIs('student.quizzes')" />

        <x-sidebar.nav-item
          :href="route('student.certificates', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          :label="__('components.sidebar.certificates')"
          icon="ri-medal-line"
          :active="request()->routeIs('student.certificates')" />
      </x-sidebar.nav-section>

      <!-- Subscriptions & Payments -->
      <x-sidebar.nav-section :title="__('components.sidebar.subscriptions_payments')">
        <x-sidebar.nav-item
          :href="route('student.subscriptions', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          :label="__('components.sidebar.subscriptions')"
          icon="ri-wallet-3-line"
          :active="request()->routeIs('student.subscriptions')" />

        <x-sidebar.nav-item
          :href="route('student.payments', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy'])"
          :label="__('components.sidebar.payment_history')"
          icon="ri-bill-line"
          :active="request()->routeIs('student.payments')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
