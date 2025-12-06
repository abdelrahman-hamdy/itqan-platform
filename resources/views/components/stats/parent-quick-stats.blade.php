<!-- Parent Quick Stats Component -->
@props(['stats', 'selectedChild' => null])

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <!-- Total Children / Selected Child -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div class="w-full">
        @if($selectedChild)
          <p class="text-sm font-medium text-gray-500">الابن المحدد</p>
          <p class="text-lg font-bold text-gray-900 mt-1">{{ $selectedChild->user->name ?? $selectedChild->first_name }}</p>
          <p class="text-xs text-purple-600 mt-1">
            <i class="ri-user-star-line ml-1"></i>
            {{ $selectedChild->student_code ?? 'طالب' }}
          </p>
        @else
          <p class="text-sm font-medium text-gray-500">عدد الأبناء</p>
          <p class="text-2xl font-bold text-gray-900">{{ $stats['total_children'] ?? 0 }}</p>
          <p class="text-xs text-purple-600 mt-1">
            <i class="ri-team-line ml-1"></i>
            عرض بيانات جميع الأبناء
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0 mr-4">
        <i class="ri-team-line text-xl text-purple-600"></i>
      </div>
    </div>
  </div>

  <!-- Active Subscriptions -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">الاشتراكات النشطة</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['active_subscriptions'] ?? 0 }}</p>
        @if(($stats['active_subscriptions'] ?? 0) > 0)
          <p class="text-xs text-green-600 mt-1">
            <i class="ri-checkbox-circle-line ml-1"></i>
            اشتراك{{ ($stats['active_subscriptions'] ?? 0) > 1 ? 'ات' : '' }} فعال{{ ($stats['active_subscriptions'] ?? 0) > 1 ? 'ة' : '' }}
          </p>
        @else
          <p class="text-xs text-gray-400 mt-1">
            <i class="ri-information-line ml-1"></i>
            لا توجد اشتراكات نشطة
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
        <p class="text-sm font-medium text-gray-500">الجلسات القادمة</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['upcoming_sessions'] ?? 0 }}</p>
        @if(($stats['upcoming_sessions'] ?? 0) > 0)
          <p class="text-xs text-blue-600 mt-1">
            <i class="ri-calendar-event-line ml-1"></i>
            جلس{{ ($stats['upcoming_sessions'] ?? 0) > 1 ? 'ات' : 'ة' }} مجدول{{ ($stats['upcoming_sessions'] ?? 0) > 1 ? 'ة' : 'ة' }}
          </p>
        @else
          <p class="text-xs text-gray-400 mt-1">
            <i class="ri-calendar-line ml-1"></i>
            لا توجد جلسات قادمة
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
        <p class="text-sm font-medium text-gray-500">معدل الحضور</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['attendance_rate'] ?? 100 }}%</p>
        @php
          $rate = $stats['attendance_rate'] ?? 100;
          $rateColor = $rate >= 80 ? 'green' : ($rate >= 60 ? 'yellow' : 'red');
          $rateText = $rate >= 80 ? 'ممتاز!' : ($rate >= 60 ? 'جيد' : 'يحتاج تحسين');
        @endphp
        <p class="text-xs text-{{ $rateColor }}-600 mt-1">
          <i class="ri-{{ $rate >= 80 ? 'checkbox-circle' : ($rate >= 60 ? 'error-warning' : 'alert') }}-line ml-1"></i>
          {{ $rateText }}
        </p>
      </div>
      <div class="w-12 h-12 bg-{{ $rateColor }}-100 rounded-lg flex items-center justify-center">
        <i class="ri-user-follow-line text-xl text-{{ $rateColor }}-600"></i>
      </div>
    </div>
  </div>
</div>

<!-- Secondary Stats Row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
  <!-- Certificates -->
  <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-4 border border-amber-100">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
        <i class="ri-award-line text-lg text-amber-600"></i>
      </div>
      <div>
        <p class="text-lg font-bold text-gray-900">{{ $stats['total_certificates'] ?? 0 }}</p>
        <p class="text-xs text-gray-600">شهادة</p>
      </div>
    </div>
  </div>

  <!-- Payments -->
  <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-xl p-4 border border-indigo-100">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
        <i class="ri-money-dollar-circle-line text-lg text-indigo-600"></i>
      </div>
      <div>
        <p class="text-lg font-bold text-gray-900">{{ $stats['total_payments'] ?? 0 }}</p>
        <p class="text-xs text-gray-600">دفعة مكتملة</p>
      </div>
    </div>
  </div>

  <!-- Quran Circles -->
  <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
        <i class="ri-book-open-line text-lg text-green-600"></i>
      </div>
      <div>
        <p class="text-lg font-bold text-gray-900">{{ ($stats['quranCirclesCount'] ?? 0) + ($stats['activeQuranSubscriptions'] ?? 0) }}</p>
        <p class="text-xs text-gray-600">اشتراك قرآن</p>
      </div>
    </div>
  </div>

  <!-- Academic -->
  <div class="bg-gradient-to-br from-violet-50 to-purple-50 rounded-xl p-4 border border-violet-100">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 bg-violet-100 rounded-lg flex items-center justify-center">
        <i class="ri-graduation-cap-line text-lg text-violet-600"></i>
      </div>
      <div>
        <p class="text-lg font-bold text-gray-900">{{ ($stats['academicSubscriptionsCount'] ?? 0) + ($stats['interactiveCoursesCount'] ?? 0) }}</p>
        <p class="text-xs text-gray-600">اشتراك أكاديمي</p>
      </div>
    </div>
  </div>
</div>
