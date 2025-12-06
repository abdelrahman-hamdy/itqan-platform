<!-- Academic Section - Template 2: Clean Professional Design with Tabs -->
<section id="academic" class="py-24 relative overflow-hidden transition-colors duration-500"
         x-data="{ activeTab: 'courses' }"
         :class="activeTab === 'courses' ? 'bg-gradient-to-br from-blue-100 via-blue-50 to-white' : 'bg-gradient-to-br from-violet-100 via-violet-50 to-white'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'البرامج الأكاديمية' }}</h2>
      @if(isset($subheading))
        <p class="text-lg text-gray-600 mb-8">{{ $subheading }}</p>
      @endif

      <!-- Tab Toggle -->
      <div class="inline-flex bg-white rounded-2xl p-1.5 shadow-md border border-gray-200">
        <button
          @click="activeTab = 'courses'"
          :class="activeTab === 'courses' ? 'bg-blue-500 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-8 py-3 rounded-xl font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-book-open-line ml-2"></i>
          الكورسات التفاعلية
        </button>
        <button
          @click="activeTab = 'teachers'"
          :class="activeTab === 'teachers' ? 'bg-violet-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-8 py-3 rounded-xl font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-user-star-line ml-2"></i>
          المعلمون
        </button>
      </div>
    </div>

    <!-- Interactive Courses Section -->
    <div x-show="activeTab === 'courses'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-12 text-center">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">الكورسات التفاعلية المتاحة</h3>
        <p class="text-gray-600">كورسات شاملة ومتطورة تغطي جميع المواد الأكاديمية بأسلوب تفاعلي ممتع</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        @forelse($interactiveCourses->take(3) as $course)
          <x-interactive-course-card :course="$course" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-12">
            <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-book-open-line text-blue-400 text-4xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات تفاعلية متاحة حالياً</h3>
            <p class="text-gray-600">سيتم إضافة الكورسات قريباً</p>
          </div>
        @endforelse
      </div>

      @if($interactiveCourses->count() > 0)
      <div class="text-center">
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-blue-500 font-semibold hover:text-blue-600 transition-colors hover:gap-3">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>

    <!-- Academic Teachers Section -->
    <div x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-12 text-center">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">المعلمون الأكاديميون المتميزون</h3>
        <p class="text-gray-600">نخبة من أفضل المعلمين المتخصصين في جميع المواد الأكاديمية</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
        @forelse($academicTeachers->take(2) as $teacher)
          <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-12">
            <div class="w-24 h-24 bg-violet-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-user-star-line text-violet-400 text-4xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا يوجد معلمون أكاديميون متاحون حالياً</h3>
            <p class="text-gray-600">سيتم إضافة المعلمين قريباً</p>
          </div>
        @endforelse
      </div>

      @if($academicTeachers->count() > 0)
      <div class="text-center">
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-violet-600 font-semibold hover:text-violet-700 transition-colors hover:gap-3">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>
  </div>
</section>
