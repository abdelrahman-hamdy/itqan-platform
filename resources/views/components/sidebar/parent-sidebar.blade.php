@php
  $user = auth()->user();
  $parent = $user ? $user->parentProfile : null;
  $fullName = $parent ? $parent->getFullNameAttribute() : ($user ? $user->name : 'ولي أمر');
  $parentGender = $parent?->gender ?? $user?->gender ?? 'male';
  $roleLabel = 'ولي أمر';
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
  <nav id="nav-menu" class="p-4 transition-all duration-300" role="navigation" aria-label="قائمة التنقل">
    <div class="space-y-2">

      <!-- Profile Section (First) -->
      <x-sidebar.nav-section title="الملف الشخصي">
        <x-sidebar.nav-item
          :href="route('parent.profile', ['subdomain' => $subdomain])"
          label="الصفحة الرئيسية"
          icon="ri-home-line"
          :active="request()->routeIs('parent.profile') || request()->routeIs('parent.dashboard')" />

        <x-sidebar.nav-item
          :href="route('parent.profile.edit', ['subdomain' => $subdomain])"
          label="تعديل الملف الشخصي"
          icon="ri-edit-line"
          :active="request()->routeIs('parent.profile.edit')" />

        <x-sidebar.nav-item
          :href="route('parent.children.index', ['subdomain' => $subdomain])"
          label="إدارة الأبناء"
          icon="ri-team-line"
          :active="request()->routeIs('parent.children.*')" />
      </x-sidebar.nav-section>

      <!-- Learning Progress Section -->
      <x-sidebar.nav-section title="التقدم الدراسي">
        <x-sidebar.nav-item
          :href="route('parent.calendar.index', ['subdomain' => $subdomain])"
          label="التقويم والجلسات"
          icon="ri-calendar-2-line"
          :active="request()->routeIs('parent.calendar.*')" />

        <x-sidebar.nav-item
          :href="route('parent.homework.index', ['subdomain' => $subdomain])"
          label="الواجبات"
          icon="ri-book-2-line"
          :active="request()->routeIs('parent.homework.*')" />

        <x-sidebar.nav-item
          :href="route('parent.quizzes.index', ['subdomain' => $subdomain])"
          label="الاختبارات"
          icon="ri-file-list-3-line"
          :active="request()->routeIs('parent.quizzes.*')" />

        <x-sidebar.nav-item
          :href="route('parent.reports.progress', ['subdomain' => $subdomain])"
          label="التقارير"
          icon="ri-bar-chart-line"
          :active="request()->routeIs('parent.reports.*')" />

        <x-sidebar.nav-item
          :href="route('parent.certificates.index', ['subdomain' => $subdomain])"
          label="الشهادات"
          icon="ri-award-line"
          :active="request()->routeIs('parent.certificates.*')" />
      </x-sidebar.nav-section>

      <!-- Subscriptions & Payments Section -->
      <x-sidebar.nav-section title="الاشتراكات والمدفوعات">
        <x-sidebar.nav-item
          :href="route('parent.subscriptions.index', ['subdomain' => $subdomain])"
          label="الاشتراكات"
          icon="ri-file-list-line"
          :active="request()->routeIs('parent.subscriptions.*')" />

        <x-sidebar.nav-item
          :href="route('parent.payments.index', ['subdomain' => $subdomain])"
          label="سجل المدفوعات"
          icon="ri-money-dollar-circle-line"
          :active="request()->routeIs('parent.payments.*')" />
      </x-sidebar.nav-section>

    </div>
  </nav>

</x-sidebar.container>
