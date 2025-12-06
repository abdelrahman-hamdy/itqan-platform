# Parent Dashboard UI Refactoring Plan

## Overview
Comprehensive refactoring of the parent dashboard to:
1. Match the student profile design language
2. Fix the broken children dropdown filter using session-based approach in the top bar
3. Delete redundant parent pages and reuse existing student/teacher views
4. Ensure consistent design language across all pages

---

## Phase 1: Session-Based Child Selection System

### 1.1 Create ChildSelectionMiddleware
**File:** `app/Http/Middleware/ChildSelectionMiddleware.php`

This middleware will:
- Check for `child_id` in request and store in session
- Default to "all" if no child is selected
- Make `selectedChild` and `allChildren` available to all parent views

```php
class ChildSelectionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $parent = auth()->user()->parentProfile;
        $children = $parent->students ?? collect();

        // Check if child_id is being changed via request
        if ($request->has('child_id')) {
            session(['parent_selected_child_id' => $request->get('child_id')]);
        }

        // Get current selection from session
        $selectedChildId = session('parent_selected_child_id', 'all');

        // Validate that selected child belongs to this parent
        if ($selectedChildId !== 'all' && !$children->contains('id', $selectedChildId)) {
            $selectedChildId = 'all';
            session(['parent_selected_child_id' => 'all']);
        }

        // Share with all views
        view()->share('parentChildren', $children);
        view()->share('selectedChildId', $selectedChildId);
        view()->share('selectedChild', $selectedChildId !== 'all'
            ? $children->firstWhere('id', $selectedChildId)
            : null);

        return $next($request);
    }
}
```

### 1.2 Update App Navigation Component
**File:** `resources/views/components/navigation/app-navigation.blade.php`

Add child selector dropdown in the top bar for parent role:
- Position: After the main navigation items, before notifications
- Design: Dropdown with child avatars and names
- Functionality: Changes session via AJAX and reloads current page

```blade
@if($role === 'parent')
  <!-- Child Selector in Top Bar -->
  <div class="relative" x-data="childSelector()">
    <button @click="open = !open" class="flex items-center gap-2 px-3 py-2 rounded-lg hover:bg-gray-100">
      @if($selectedChild)
        <x-avatar :user="$selectedChild->user" size="xs" />
        <span class="text-sm font-medium">{{ $selectedChild->user->name }}</span>
      @else
        <i class="ri-team-line text-purple-600"></i>
        <span class="text-sm font-medium">جميع الأبناء</span>
      @endif
      <i class="ri-arrow-down-s-line"></i>
    </button>

    <!-- Dropdown -->
    <div x-show="open" @click.away="open = false" class="absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-xl border">
      <div class="p-2">
        <button @click="selectChild('all')" class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
          <i class="ri-team-line text-purple-600 text-xl"></i>
          <span>جميع الأبناء ({{ $parentChildren->count() }})</span>
        </button>
        @foreach($parentChildren as $child)
          <button @click="selectChild('{{ $child->id }}')" class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50">
            <x-avatar :user="$child->user" size="xs" />
            <div class="text-right">
              <p class="text-sm font-medium">{{ $child->user->name }}</p>
              <p class="text-xs text-gray-500">{{ $child->student_code }}</p>
            </div>
          </button>
        @endforeach
      </div>
    </div>
  </div>
@endif
```

### 1.3 Create Child Selection API Endpoint
**File:** `routes/web.php` - Add route
**File:** `app/Http/Controllers/ParentDashboardController.php` - Add method

```php
// Route
Route::post('/parent/select-child', [ParentDashboardController::class, 'selectChildSession'])
    ->name('parent.select-child');

// Controller method
public function selectChildSession(Request $request)
{
    $childId = $request->input('child_id', 'all');
    session(['parent_selected_child_id' => $childId]);

    return response()->json(['success' => true, 'child_id' => $childId]);
}
```

---

## Phase 2: Redesign Parent Profile Page

### 2.1 Update Parent Profile Controller
**File:** `app/Http/Controllers/ParentProfileController.php`

Modify the `index()` method to match student profile data structure:
- Return learning sections data (Quran circles, private sessions, interactive courses, academic sessions)
- Group data by children for the "all" view
- Filter by selected child when one is chosen

### 2.2 Redesign Parent Profile View
**File:** `resources/views/parent/profile.blade.php`

Transform to match student profile design:

```blade
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <!-- Same meta setup as student/profile.blade.php -->
</head>
<body class="bg-gray-50 text-gray-900">
  <x-navigation.app-navigation role="parent" />
  @include('components.sidebar.parent-sidebar')

  <main class="pt-20 min-h-screen transition-all duration-300" id="main-content" style="margin-right: 320px;">
    <div class="w-full px-4 sm:px-6 lg:px-8 py-8">

      <!-- Welcome Section -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          مرحباً، {{ $parent->first_name }}! 👋
        </h1>
        <p class="text-gray-600">
          @if($selectedChild)
            متابعة تقدم {{ $selectedChild->user->name }} في رحلة التعلم
          @else
            متابعة تقدم أبنائك في رحلة التعلم
          @endif
        </p>
      </div>

      <!-- Quick Stats (aggregated for all children or filtered) -->
      @include('components.stats.parent-quick-stats')

      <!-- Children Overview Cards (when viewing all) -->
      @if(!$selectedChild)
        <div class="mb-8">
          <h2 class="text-2xl font-bold text-gray-900 mb-4">نظرة عامة على الأبناء</h2>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($children as $child)
              @include('components.parent.child-overview-card', ['child' => $child])
            @endforeach
          </div>
        </div>
      @endif

      <!-- Learning Sections Grid (same as student) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Quran Circles Section -->
        @include('components.cards.learning-section-card', [
          'title' => 'حلقات القرآن الجماعية',
          'subtitle' => $selectedChild
            ? 'حلقات ' . $selectedChild->user->name
            : 'حلقات جميع الأبناء',
          'icon' => 'ri-group-line',
          'iconBgColor' => 'bg-green-500',
          'primaryColor' => 'green',
          'items' => $quranCircles,
          // ... same structure as student
        ])

        <!-- Quran Private Sessions -->
        @include('components.cards.learning-section-card', [...])

        <!-- Interactive Courses -->
        @include('components.cards.learning-section-card', [...])

        <!-- Academic Private Sessions -->
        @include('components.cards.learning-section-card', [...])
      </div>

      <!-- Recent Activity -->
      @include('components.parent.recent-activity-section')

    </div>
  </main>
</body>
</html>
```

### 2.3 Create Parent-Specific Components
**New Files:**
- `resources/views/components/stats/parent-quick-stats.blade.php` - Aggregated stats for all children
- `resources/views/components/parent/child-overview-card.blade.php` - Individual child summary card
- `resources/views/components/parent/recent-activity-section.blade.php` - Recent activities timeline

---

## Phase 3: Delete Redundant Parent Pages & Reuse Student Views

### 3.1 Pages to DELETE (completely remove)
```
resources/views/parent/sessions/upcoming.blade.php
resources/views/parent/sessions/history.blade.php
resources/views/parent/sessions/show.blade.php
resources/views/parent/subscriptions/index.blade.php
resources/views/parent/subscriptions/show.blade.php
resources/views/parent/payments/index.blade.php
resources/views/parent/payments/show.blade.php
resources/views/parent/certificates/index.blade.php
resources/views/parent/certificates/show.blade.php
resources/views/parent/reports/progress.blade.php
resources/views/parent/reports/attendance.blade.php
```

### 3.2 Delete Parent-Specific Child Filter Component
```
resources/views/components/parent/child-filter.blade.php
```
(Functionality moved to top bar)

### 3.3 Update Controllers to Return Shared Views

#### ParentCertificateController
```php
public function index()
{
    $childIds = $this->getChildIds();
    $certificates = Certificate::whereIn('student_profile_id', $childIds)
        ->with(['student', 'issuedBy'])
        ->paginate(12);

    // Return the STUDENT certificates view with parent layout flag
    return view('student.certificates', [
        'certificates' => $certificates,
        'layout' => 'parent', // Signal to use parent layout
    ]);
}
```

#### ParentSubscriptionController
```php
public function index()
{
    $childIds = $this->getChildIds();

    // Get subscriptions for all children (or selected child)
    $individualQuranSubscriptions = QuranSubscription::whereIn('student_profile_id', $childIds)
        ->where('circle_type', 'individual')
        ->with(['quranTeacher', 'individualCircle'])
        ->get();

    // ... same data gathering as student

    return view('student.subscriptions', [
        'individualQuranSubscriptions' => $individualQuranSubscriptions,
        'groupQuranSubscriptions' => $groupQuranSubscriptions,
        'academicSubscriptions' => $academicSubscriptions,
        'courseEnrollments' => $courseEnrollments,
        'quranTrialRequests' => $quranTrialRequests,
        'layout' => 'parent',
    ]);
}
```

### 3.4 Update Student Views to Support Parent Layout

Modify views to check for `layout` parameter:

```blade
{{-- At the top of student/subscriptions.blade.php --}}
@php
    $useParentLayout = ($layout ?? null) === 'parent';
    $layoutComponent = $useParentLayout ? 'layouts.parent-layout' : 'student';
@endphp

@if($useParentLayout)
<x-layouts.parent-layout title="...">
    {{-- Content --}}
</x-layouts.parent-layout>
@else
<x-student title="...">
    {{-- Same content --}}
</x-student>
@endif
```

**Better approach - Create unified layout component:**

```blade
{{-- resources/views/components/layouts/dashboard-layout.blade.php --}}
@props([
    'title',
    'role' => 'student', // student | parent | teacher
])

@if($role === 'parent')
    <x-layouts.parent-layout :title="$title">
        {{ $slot }}
    </x-layouts.parent-layout>
@else
    <x-student :title="$title">
        {{ $slot }}
    </x-student>
@endif
```

---

## Phase 4: Update Routes

### 4.1 Modify Parent Routes to Point to Shared Controllers/Views

```php
// routes/web.php - Parent section

Route::middleware(['auth', 'role:parent', 'child.selection'])->prefix('parent')->name('parent.')->group(function () {
    // Profile - Custom parent view
    Route::get('/', [ParentProfileController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ParentProfileController::class, 'index'])->name('profile');
    Route::get('/profile/edit', [ParentProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ParentProfileController::class, 'update'])->name('profile.update');

    // Child selection API
    Route::post('/select-child', [ParentDashboardController::class, 'selectChildSession'])->name('select-child');

    // REUSE STUDENT VIEWS - Controllers return student views with parent layout

    // Certificates - Reuse student view
    Route::get('/certificates', [ParentCertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/{certificate}', [ParentCertificateController::class, 'show'])->name('certificates.show');
    Route::get('/certificates/{certificate}/download', [ParentCertificateController::class, 'download'])->name('certificates.download');

    // Subscriptions - Reuse student view
    Route::get('/subscriptions', [ParentSubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/{type}/{subscription}', [ParentSubscriptionController::class, 'show'])->name('subscriptions.show');

    // Payments - Reuse student view
    Route::get('/payments', [ParentPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [ParentPaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/receipt', [ParentPaymentController::class, 'downloadReceipt'])->name('payments.receipt');

    // Calendar - Reuse student view
    Route::get('/calendar', [ParentCalendarController::class, 'index'])->name('calendar');

    // Sessions - Reuse student session views
    Route::get('/sessions/upcoming', [ParentSessionController::class, 'upcoming'])->name('sessions.upcoming');
    Route::get('/sessions/history', [ParentSessionController::class, 'history'])->name('sessions.history');
    Route::get('/sessions/{sessionType}/{session}', [ParentSessionController::class, 'show'])->name('sessions.show');

    // Reports - Keep custom parent views (aggregated data)
    Route::get('/reports/progress', [ParentReportController::class, 'progressReport'])->name('reports.progress');
    Route::get('/reports/attendance', [ParentReportController::class, 'attendanceReport'])->name('reports.attendance');
});
```

---

## Phase 5: Update Parent Sidebar Navigation

### 5.1 Update Parent Sidebar
**File:** `resources/views/components/sidebar/parent-sidebar.blade.php`

Update navigation items to match student sidebar structure:

```blade
<x-sidebar.container sidebar-id="parent-sidebar" storage-key="parentSidebarCollapsed">
  <x-sidebar.profile-card
    :user="$user"
    user-type="parent"
    :display-name="$parent->getFullNameAttribute()"
    :role-label="'ولي أمر'"
    :gender="$parent->gender ?? 'male'" />

  <nav id="nav-menu" class="p-4">
    <!-- Profile Section -->
    <x-sidebar.nav-section title="إدارة الملف الشخصي">
      <x-sidebar.nav-item
        :href="route('parent.profile', ['subdomain' => $subdomain])"
        label="الصفحة الرئيسية"
        icon="ri-home-4-line"
        :active="request()->routeIs('parent.profile') || request()->routeIs('parent.dashboard')" />
      <x-sidebar.nav-item
        :href="route('parent.profile.edit', ['subdomain' => $subdomain])"
        label="تعديل الملف الشخصي"
        icon="ri-edit-line"
        :active="request()->routeIs('parent.profile.edit')" />
    </x-sidebar.nav-section>

    <!-- Learning Progress Section -->
    <x-sidebar.nav-section title="التقدم الدراسي">
      <x-sidebar.nav-item
        :href="route('parent.calendar', ['subdomain' => $subdomain])"
        label="التقويم والجلسات"
        icon="ri-calendar-line"
        :active="request()->routeIs('parent.calendar')" />
      <x-sidebar.nav-item
        :href="route('parent.sessions.upcoming', ['subdomain' => $subdomain])"
        label="الجلسات القادمة"
        icon="ri-calendar-event-line"
        :active="request()->routeIs('parent.sessions.upcoming')" />
      <x-sidebar.nav-item
        :href="route('parent.sessions.history', ['subdomain' => $subdomain])"
        label="سجل الجلسات"
        icon="ri-history-line"
        :active="request()->routeIs('parent.sessions.history')" />
      <x-sidebar.nav-item
        :href="route('parent.reports.progress', ['subdomain' => $subdomain])"
        label="تقرير التقدم"
        icon="ri-bar-chart-line"
        :active="request()->routeIs('parent.reports.progress')" />
      <x-sidebar.nav-item
        :href="route('parent.reports.attendance', ['subdomain' => $subdomain])"
        label="تقرير الحضور"
        icon="ri-user-follow-line"
        :active="request()->routeIs('parent.reports.attendance')" />
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
  </nav>
</x-sidebar.container>
```

---

## Phase 6: Implementation Tasks

### Task List (Priority Order)

1. **Create ChildSelectionMiddleware** (High Priority)
   - Register in Kernel.php
   - Add to parent route group

2. **Add Child Selector to Top Navigation** (High Priority)
   - Update app-navigation.blade.php
   - Create Alpine.js component for selection
   - Add AJAX endpoint for selection

3. **Redesign Parent Profile Page** (High Priority)
   - Update ParentProfileController data structure
   - Create new profile.blade.php matching student design
   - Create parent-quick-stats.blade.php component
   - Create child-overview-card.blade.php component

4. **Create Unified Dashboard Layout Component** (Medium Priority)
   - Create dashboard-layout.blade.php
   - Support role parameter for different layouts

5. **Update Student Views for Parent Support** (Medium Priority)
   - Modify certificates.blade.php
   - Modify subscriptions.blade.php
   - Modify payments.blade.php
   - Modify calendar/index.blade.php

6. **Update Parent Controllers** (Medium Priority)
   - ParentCertificateController - return student view
   - ParentSubscriptionController - return student view
   - ParentPaymentController - return student view
   - Create ParentCalendarController

7. **Delete Redundant Files** (Low Priority - after testing)
   - Delete parent session views
   - Delete parent subscription views
   - Delete parent payment views
   - Delete parent certificate views
   - Delete parent report views
   - Delete parent child-filter component

8. **Update Parent Sidebar** (Medium Priority)
   - Match student sidebar structure
   - Update navigation items

9. **Update Routes** (Medium Priority)
   - Add child selection middleware
   - Update route definitions

10. **Testing & Refinement** (High Priority)
    - Test child selection persistence
    - Test data filtering by child
    - Test all pages with parent role
    - Test responsive design

---

## Files Summary

### Files to CREATE:
```
app/Http/Middleware/ChildSelectionMiddleware.php
resources/views/components/stats/parent-quick-stats.blade.php
resources/views/components/parent/child-overview-card.blade.php
resources/views/components/parent/recent-activity-section.blade.php
resources/views/components/layouts/dashboard-layout.blade.php (optional)
app/Http/Controllers/ParentCalendarController.php
```

### Files to MODIFY:
```
app/Http/Kernel.php (register middleware)
bootstrap/app.php (register middleware)
routes/web.php (update parent routes)
resources/views/components/navigation/app-navigation.blade.php
resources/views/parent/profile.blade.php
resources/views/components/sidebar/parent-sidebar.blade.php
app/Http/Controllers/ParentProfileController.php
app/Http/Controllers/ParentCertificateController.php
app/Http/Controllers/ParentSubscriptionController.php
app/Http/Controllers/ParentPaymentController.php
app/Http/Controllers/ParentSessionController.php
app/Http/Controllers/ParentReportController.php
resources/views/student/certificates.blade.php
resources/views/student/subscriptions.blade.php
resources/views/student/payments.blade.php
resources/views/student/calendar/index.blade.php
```

### Files to DELETE:
```
resources/views/parent/sessions/upcoming.blade.php
resources/views/parent/sessions/history.blade.php
resources/views/parent/sessions/show.blade.php
resources/views/parent/subscriptions/index.blade.php
resources/views/parent/subscriptions/show.blade.php
resources/views/parent/payments/index.blade.php
resources/views/parent/payments/show.blade.php
resources/views/parent/certificates/index.blade.php
resources/views/parent/certificates/show.blade.php
resources/views/parent/reports/progress.blade.php
resources/views/parent/reports/attendance.blade.php
resources/views/components/parent/child-filter.blade.php
```

---

## Design Specifications

### Color Scheme (matches student)
- Primary Purple: `#9333ea` (purple-600)
- Background: `bg-gray-50`
- Cards: `bg-white rounded-xl shadow-sm border border-gray-200`

### Typography
- Headings: `text-3xl font-bold text-gray-900`
- Subheadings: `text-2xl font-bold text-gray-900`
- Body: `text-gray-600`
- Small: `text-sm text-gray-500`

### Component Patterns
- Cards: `bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all`
- Buttons (Primary): `bg-purple-600 hover:bg-purple-700 text-white px-6 py-2.5 rounded-lg`
- Buttons (Secondary): `bg-gray-100 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-200`
- Badges: `px-3 py-1 bg-{color}-100 text-{color}-800 text-xs font-medium rounded-full`

### Responsive Breakpoints
- Mobile: Default
- Tablet: `md:` (768px+)
- Desktop: `lg:` (1024px+)

---

## Notes

1. **Session-based child selection** is better than URL parameters because:
   - Persists across page navigations
   - No need to pass `child_id` in every link
   - Better UX - selection "sticks"
   - Cleaner URLs

2. **Reusing student views** ensures:
   - Consistent design language
   - Single source of truth for UI
   - Easier maintenance
   - Faster development

3. **Parent-specific features** to keep:
   - Profile page shows children overview
   - Reports can aggregate across children
   - Child selector in top bar
   - Parent-specific sidebar navigation
