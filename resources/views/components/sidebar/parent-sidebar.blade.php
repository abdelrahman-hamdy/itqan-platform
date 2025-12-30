@php
  $user = auth()->user();
  $parent = $user ? $user->parentProfile : null;
  $fullName = $parent ? $parent->getFullNameAttribute() : ($user ? $user->name : __('parent.sidebar.role'));
  $parentGender = $parent?->gender ?? $user?->gender ?? 'male';
  $roleLabel = __('parent.sidebar.role');
  $subdomain = request()->route('subdomain') ?? $user?->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-sidebar.container sidebar-id="parent-sidebar" storage-key="parentSidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="$user"
    user-type="parent"
    :display-name="$fullName"
    :role-label="$roleLabel"
    :gender="$parentGender"
    :profile-route="route('parent.profile', ['subdomain' => $subdomain])" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="{{ __('parent.sidebar.navigation_label') }}">
    <div class="space-y-2">

      <!-- Profile Section (First) -->
      <x-sidebar.nav-section :title="__('parent.sidebar.profile_section')">
        <x-sidebar.nav-item
          :href="route('parent.profile', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.home')"
          icon="ri-home-line"
          :active="request()->routeIs('parent.profile') || request()->routeIs('parent.dashboard')" />

        <x-sidebar.nav-item
          :href="route('parent.profile.edit', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.edit_profile')"
          icon="ri-edit-line"
          :active="request()->routeIs('parent.profile.edit')" />

        <x-sidebar.nav-item
          :href="route('parent.children.index', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.manage_children')"
          icon="ri-team-line"
          :active="request()->routeIs('parent.children.*')" />
      </x-sidebar.nav-section>

      <!-- Learning Progress Section -->
      <x-sidebar.nav-section :title="__('parent.sidebar.learning_progress')">
        <x-sidebar.nav-item
          :href="route('parent.calendar.index', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.calendar_sessions')"
          icon="ri-calendar-2-line"
          :active="request()->routeIs('parent.calendar.*')" />

        <x-sidebar.nav-item
          :href="route('parent.homework.index', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.homework')"
          icon="ri-book-2-line"
          :active="request()->routeIs('parent.homework.*')" />

        <x-sidebar.nav-item
          :href="route('parent.quizzes.index', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.quizzes')"
          icon="ri-file-list-3-line"
          :active="request()->routeIs('parent.quizzes.*')" />

        <x-sidebar.nav-item
          :href="route('parent.reports.progress', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.reports')"
          icon="ri-bar-chart-line"
          :active="request()->routeIs('parent.reports.*')" />

        <x-sidebar.nav-item
          :href="route('parent.certificates.index', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.certificates')"
          icon="ri-award-line"
          :active="request()->routeIs('parent.certificates.*')" />
      </x-sidebar.nav-section>

      <!-- Subscriptions & Payments Section -->
      <x-sidebar.nav-section :title="__('parent.sidebar.subscriptions_payments')">
        <x-sidebar.nav-item
          :href="route('parent.subscriptions.index', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.subscriptions')"
          icon="ri-file-list-line"
          :active="request()->routeIs('parent.subscriptions.*')" />

        <x-sidebar.nav-item
          :href="route('parent.payments.index', ['subdomain' => $subdomain])"
          :label="__('parent.sidebar.payment_history')"
          icon="ri-money-dollar-circle-line"
          :active="request()->routeIs('parent.payments.*')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
