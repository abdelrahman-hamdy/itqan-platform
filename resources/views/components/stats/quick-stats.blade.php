<!-- Quick Stats Component -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <!-- Next Upcoming Session -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div class="w-full">
        <p class="text-sm font-medium text-gray-500">الجلسة القادمة</p>
        <p class="text-lg font-bold text-gray-900 mt-1">{{ $nextSessionText ?? 'لا توجد جلسات' }}</p>
        @if(isset($nextSessionDate))
          <p class="text-xs text-blue-600 mt-1">
            <i class="ri-calendar-line ml-1"></i>
            {{ $nextSessionDate->locale('ar')->isoFormat('dddd، D MMMM - HH:mm') }}
          </p>
        @else
          <p class="text-xs text-gray-400 mt-1">
            <i class="ri-calendar-line ml-1"></i>
            لا توجد جلسات قادمة مجدولة
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0 mr-4">
        <i class="{{ $nextSessionIcon ?? 'ri-calendar-event-line' }} text-xl text-blue-600"></i>
      </div>
    </div>
  </div>

  <!-- Pending Homework (Not Yet Graded) -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">واجبات تنتظر التقييم</p>
        <p class="text-2xl font-bold text-gray-900">{{ $pendingHomework ?? 0 }}</p>
        @if(($pendingHomework ?? 0) > 0)
          <p class="text-xs text-orange-600 mt-1">
            <i class="ri-alert-line ml-1"></i>
            من جلسات مكتملة سابقة
          </p>
        @else
          <p class="text-xs text-green-600 mt-1">
            <i class="ri-check-line ml-1"></i>
            جميع الواجبات تم تقييمها
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
        <i class="ri-file-text-line text-xl text-orange-600"></i>
      </div>
    </div>
  </div>

  <!-- Pending Quizzes -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">الاختبارات المعلقة</p>
        <p class="text-2xl font-bold text-gray-900">{{ $pendingQuizzes ?? 0 }}</p>
        @if(($pendingQuizzes ?? 0) > 0)
          <p class="text-xs text-orange-600 mt-1">
            <i class="ri-alert-line ml-1"></i>
            يجب إكمالها
          </p>
        @else
          <p class="text-xs text-green-600 mt-1">
            <i class="ri-check-line ml-1"></i>
            جميع الاختبارات مكتملة
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
        <i class="ri-file-list-3-line text-xl text-purple-600"></i>
      </div>
    </div>
  </div>

  <!-- Today's Learning Hours -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">ساعات التعلم اليوم</p>
        <p class="text-2xl font-bold text-gray-900">
          @if(($todayLearningHours ?? 0) > 0)
            {{ $todayLearningHours }} ساعة
          @else
            {{ $todayLearningMinutes ?? 0 }} دقيقة
          @endif
        </p>
        @if(($todayLearningMinutes ?? 0) > 0)
          <p class="text-xs text-green-600 mt-1">
            <i class="ri-time-line ml-1"></i>
            استمر بالتعلم!
          </p>
        @else
          <p class="text-xs text-gray-400 mt-1">
            <i class="ri-calendar-line ml-1"></i>
            لا توجد جلسات اليوم
          </p>
        @endif
      </div>
      <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
        <i class="ri-time-fill text-xl text-green-600"></i>
      </div>
    </div>
  </div>
</div> 