@php
    $user = auth()->user();
    $subdomain = $user->academy->subdomain ?? 'itqan-academy';
    $displayName = $user->supervisorProfile->full_name ?? $user->name;
    $roleLabel = $user->supervisorProfile->supervisor_code ?? __('supervisor.sidebar.supervisor');
    $supervisorGender = $user->supervisorProfile?->gender ?? $user->gender ?? 'male';
@endphp

<x-sidebar.container sidebar-id="supervisor-sidebar" storage-key="supervisorSidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="$user"
    user-type="supervisor"
    :display-name="$displayName"
    :role-label="$roleLabel"
    :gender="$supervisorGender"
    :profile-route="'#'" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="{{ __('supervisor.sidebar.navigation_label') }}">
    <div class="space-y-2">

      <!-- Overview -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.overview')">
        <!-- placeholder: dashboard will be added here -->
      </x-sidebar.nav-section>

      <!-- Supervision -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.supervision')">
        <!-- placeholder: sessions, circles, lessons, courses -->
      </x-sidebar.nav-section>

      <!-- Reports & Analytics -->
      <x-sidebar.nav-section :title="__('supervisor.sidebar.reports')">
        <!-- placeholder: session reports, teacher earnings, calendar -->
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
