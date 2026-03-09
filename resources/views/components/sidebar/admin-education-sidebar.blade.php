@php
    $user = auth()->user();
    $subdomain = $user->academy->subdomain ?? 'itqan-academy';
@endphp

<x-sidebar.container sidebar-id="admin-education-sidebar" storage-key="adminEducationSidebarCollapsed">

  <!-- Profile -->
  <x-sidebar.profile-card
    :user="$user"
    user-type="admin"
    :display-name="$user->name"
    :role-label="__('admin.sidebar.education_manager')"
    :gender="$user->gender ?? 'male'"
    :profile-route="'#'" />

  <!-- Navigation Menu -->
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="{{ __('admin.sidebar.navigation_label') }}">
    <div class="space-y-2">

      <!-- Education Management -->
      <x-sidebar.nav-section :title="__('admin.sidebar.education_management')">
        <!-- placeholder: education management items will be added here -->
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
