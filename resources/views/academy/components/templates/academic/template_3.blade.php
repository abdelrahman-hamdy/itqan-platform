<!-- Academic Section - Template 3: Classic Design with Dynamic Colored Background -->
<section id="academic" class="py-16 relative overflow-hidden transition-colors duration-500" x-data="{ activeTab: 'courses' }"
         :class="activeTab === 'courses' ? 'bg-blue-50/70' : 'bg-violet-50/70'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header with Tabs Alongside -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-10">
      <div class="text-right">
        <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? 'البرامج الأكاديمية' }}</h2>
        @if(isset($subheading))
          <p class="text-base text-gray-600">{{ $subheading }}</p>
        @endif
      </div>

      <!-- Tab Toggle Floated Left -->
      <div class="flex gap-2 bg-white rounded-lg p-1 shadow-sm border border-gray-200 flex-shrink-0">
        <button
          @click="activeTab = 'courses'"
          :class="activeTab === 'courses' ? 'bg-blue-500 text-white' : 'text-gray-600 hover:text-gray-900'"
          class="px-5 py-2 rounded-md font-medium transition-all duration-200 text-sm whitespace-nowrap">
          <i class="ri-book-open-line ml-1"></i>
          الكورسات التفاعلية
        </button>
        <button
          @click="activeTab = 'teachers'"
          :class="activeTab === 'teachers' ? 'bg-violet-600 text-white' : 'text-gray-600 hover:text-gray-900'"
          class="px-5 py-2 rounded-md font-medium transition-all duration-200 text-sm whitespace-nowrap">
          <i class="ri-user-star-line ml-1"></i>
          المعلمون
        </button>
      </div>
    </div>

    <!-- Interactive Courses Section -->
    <div x-show="activeTab === 'courses'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($interactiveCourses->take(3) as $course)
          <x-interactive-course-card :course="$course" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-10">
            <div class="w-16 h-16 bg-blue-50 rounded-lg flex items-center justify-center mx-auto mb-3">
              <i class="ri-book-open-line text-xl text-blue-400"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">لا توجد كورسات تفاعلية متاحة حالياً</h3>
            <p class="text-sm text-gray-600">سيتم إضافة الكورسات قريباً</p>
          </div>
        @endforelse
      </div>

      @if($interactiveCourses->count() > 0)
      <div class="text-center">
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600/10 text-blue-700 rounded-md font-medium hover:bg-blue-600/20 transition-colors text-sm">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>

    <!-- Academic Teachers Section -->
    <div x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        @forelse($academicTeachers->take(2) as $teacher)
          <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" :subjects="$subjects ?? collect()" :gradeLevels="$gradeLevels ?? collect()" />
        @empty
          <div class="col-span-full text-center py-10">
            <div class="w-16 h-16 bg-gray-50 rounded-lg flex items-center justify-center mx-auto mb-3">
              <i class="ri-user-star-line text-xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">لا يوجد معلمون أكاديميون متاحون حالياً</h3>
            <p class="text-sm text-gray-600">سيتم إضافة المعلمين قريباً</p>
          </div>
        @endforelse
      </div>

      @if($academicTeachers->count() > 0)
      <div class="text-center">
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-violet-600/10 text-violet-700 rounded-md font-medium hover:bg-violet-600/20 transition-colors text-sm">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>
  </div>
</section>
