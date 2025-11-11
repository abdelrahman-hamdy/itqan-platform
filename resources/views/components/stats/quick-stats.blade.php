<!-- Quick Stats Component -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <!-- Total Learning Hours -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">إجمالي ساعات التعلم</p>
        <p class="text-2xl font-bold text-gray-900">{{ $totalHours ?? 24 }}</p>
        <p class="text-xs text-green-600 mt-1">
          <i class="ri-arrow-up-line ml-1"></i>
          +{{ $hoursGrowth ?? 12 }}% هذا الشهر
        </p>
      </div>
      <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
        <i class="ri-time-line text-xl text-blue-600"></i>
      </div>
    </div>
  </div>

  <!-- Active Courses -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">الكورسات النشطة</p>
        <p class="text-2xl font-bold text-gray-900">{{ $activeCourses ?? 3 }}</p>
        <p class="text-xs text-blue-600 mt-1">
          <i class="ri-book-line ml-1"></i>
          {{ $completedLessons ?? 15 }} درس مكتمل
        </p>
      </div>
      <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
        <i class="ri-book-open-line text-xl text-green-600"></i>
      </div>
    </div>
  </div>

  <!-- Quran Progress -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">تقدم القرآن</p>
        <p class="text-2xl font-bold text-gray-900">{{ $quranProgress ?? 75 }}%</p>
        <p class="text-xs text-purple-600 mt-1">
          <i class="ri-check-line ml-1"></i>
          {{ $quranPages ?? 12 }} صفحة محفوظة
        </p>
      </div>
      <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
        <i class="ri-book-mark-line text-xl text-purple-600"></i>
      </div>
    </div>
  </div>

  <!-- Achievement Score -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-sm font-medium text-gray-500">نقاط الإنجاز</p>
        <p class="text-2xl font-bold text-gray-900">{{ $achievementPoints ?? 850 }}</p>
        <p class="text-xs text-yellow-600 mt-1">
          <i class="ri-medal-line ml-1"></i>
          {{ $achievementsCount ?? 8 }} إنجاز
        </p>
      </div>
      <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
        <i class="ri-medal-line text-xl text-yellow-600"></i>
      </div>
    </div>
  </div>
</div> 