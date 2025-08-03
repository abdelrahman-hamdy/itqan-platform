<!-- Teacher Stats Component -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <!-- Total Students -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">إجمالي الطلاب</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['totalStudents'] ?? 0 }}</p>
        <p class="text-xs text-blue-600 mt-1">
          <i class="ri-group-line ml-1"></i>
          @if(isset($stats['activeCircles']))
            {{ $stats['activeCircles'] }} دائرة نشطة
          @elseif(isset($stats['activeCourses']))
            {{ $stats['activeCourses'] }} دورة نشطة
          @endif
        </p>
      </div>
      <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
        <i class="ri-group-line text-xl text-blue-600"></i>
      </div>
    </div>
  </div>

  <!-- Monthly Sessions -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">جلسات هذا الشهر</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['thisMonthSessions'] ?? 0 }}</p>
        <p class="text-xs text-green-600 mt-1">
          <i class="ri-calendar-check-line ml-1"></i>
          جلسة مكتملة
        </p>
      </div>
      <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
        <i class="ri-calendar-check-line text-xl text-green-600"></i>
      </div>
    </div>
  </div>

  <!-- Monthly Earnings -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">أرباح هذا الشهر</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['monthlyEarnings'] ?? 0, 0) }}</p>
        <p class="text-xs text-yellow-600 mt-1">
          <i class="ri-money-dollar-circle-line ml-1"></i>
          ريال سعودي
        </p>
      </div>
      <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
        <i class="ri-money-dollar-circle-line text-xl text-yellow-600"></i>
      </div>
    </div>
  </div>

  <!-- Teacher Rating -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">تقييم المعلم</p>
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['teacherRating'] ?? 0, 1) }}</p>
        <p class="text-xs text-purple-600 mt-1">
          <i class="ri-star-line ml-1"></i>
          من 5 نجوم
        </p>
      </div>
      <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
        <i class="ri-star-line text-xl text-purple-600"></i>
      </div>
    </div>
  </div>
</div>