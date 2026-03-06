<!-- Parent Quick Stats Component -->
@props(['stats', 'selectedChild' => null])

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <!-- Total Children / Selected Child -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div class="w-full">
        @if($selectedChild)
          <p class="text-sm font-medium text-gray-500">{{ __('parent.quick_stats.selected_child') }}</p>
          <p class="text-lg font-bold text-gray-900 mt-1">{{ $selectedChild->user->name ?? $selectedChild->first_name }}</p>
          <p class="text-xs text-purple-600 mt-1">
            <i class="ri-user-star-line ms-1"></i>
            {{ $selectedChild->student_code ?? __('parent.quick_stats.student') }}
          </p>
        @else
          <p class="text-sm font-medium text-gray-500">{{ __('parent.quick_stats.children_count') }}</p>
          <p class="text-2xl font-bold text-gray-900">{{ $stats['total_children'] ?? 0 }}</p>
          <p class="text-xs text-purple-600 mt-1">
            <i class="ri-team-line ms-1"></i>
            {{ __('parent.quick_stats.viewing_all_children') }}
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 me-4">
        <i class="ri-team-line text-xl text-purple-600"></i>
      </div>
    </div>
  </div>

  <!-- Active Subscriptions -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ __('parent.quick_stats.active_subscriptions') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['active_subscriptions'] ?? 0 }}</p>
        @if(($stats['active_subscriptions'] ?? 0) > 0)
          <p class="text-xs text-green-600 mt-1">
            <i class="ri-checkbox-circle-line ms-1"></i>
            {{ ($stats['active_subscriptions'] ?? 0) > 1 ? __('parent.quick_stats.active_subscriptions_label_plural') : __('parent.quick_stats.active_subscriptions_label') }}
          </p>
        @else
          <p class="text-xs text-gray-400 mt-1">
            <i class="ri-information-line ms-1"></i>
            {{ __('parent.quick_stats.no_active_subscriptions') }}
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
        <i class="ri-file-list-line text-xl text-green-600"></i>
      </div>
    </div>
  </div>

  <!-- Upcoming Sessions -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ __('parent.quick_stats.upcoming_sessions') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['upcoming_sessions'] ?? 0 }}</p>
        @if(($stats['upcoming_sessions'] ?? 0) > 0)
          <p class="text-xs text-blue-600 mt-1">
            <i class="ri-calendar-event-line ms-1"></i>
            {{ ($stats['upcoming_sessions'] ?? 0) > 1 ? __('parent.quick_stats.scheduled_sessions') : __('parent.quick_stats.scheduled_session') }}
          </p>
        @else
          <p class="text-xs text-gray-400 mt-1">
            <i class="ri-calendar-line ms-1"></i>
            {{ __('parent.quick_stats.no_upcoming_sessions') }}
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
        <i class="ri-calendar-event-line text-xl text-blue-600"></i>
      </div>
    </div>
  </div>

  <!-- Attendance Rate -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">{{ __('parent.quick_stats.attendance_rate') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['attendance_rate'] ?? 100 }}%</p>
        @php
          $rate = $stats['attendance_rate'] ?? 100;
          $rateColor = $rate >= 80 ? 'green' : ($rate >= 60 ? 'yellow' : 'red');
          $rateText = $rate >= 80 ? __('parent.quick_stats.rate_excellent') : ($rate >= 60 ? __('parent.quick_stats.rate_good') : __('parent.quick_stats.rate_needs_improvement'));
        @endphp
        <p class="text-xs text-{{ $rateColor }}-600 mt-1">
          <i class="ri-{{ $rate >= 80 ? 'checkbox-circle' : ($rate >= 60 ? 'error-warning' : 'alert') }}-line ms-1"></i>
          {{ $rateText }}
        </p>
      </div>
      <div class="w-12 h-12 bg-{{ $rateColor }}-100 rounded-lg flex items-center justify-center">
        <i class="ri-user-follow-line text-xl text-{{ $rateColor }}-600"></i>
      </div>
    </div>
  </div>
</div>
