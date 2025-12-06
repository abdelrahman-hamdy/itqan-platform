@php
    // Get gradient palette for this academy
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $colors = $gradientPalette->getColors();
    $gradientFrom = $colors['from'];
    $gradientTo = $colors['to'];
@endphp

<!-- Academic Section -->
<section id="academic" class="py-24 bg-gradient-to-br from-blue-100 via-white to-violet-100 relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-20">
      <h2 class="text-3xl font-bold text-black mb-12">القسم الأكاديمي</h2>
    </div>
    
    <!-- Interactive Courses Section -->
    <div class="mb-24">
      <div class="mb-12 flex items-center justify-between">
        <div>
          <h3 class="text-3xl font-bold text-gray-900 mb-2">الكورسات التفاعلية المتاحة</h3>
          <p class="text-gray-600">كورسات شاملة ومتطورة تغطي جميع المواد الأكاديمية بأسلوب تفاعلي ممتع</p>
        </div>
        @if($interactiveCourses->count() > 0)
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center px-6 py-3 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition-colors whitespace-nowrap">
          عرض المزيد
          <i class="ri-arrow-left-line mr-2"></i>
        </a>
        @endif
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
      </div>
      
    <!-- Academic Teachers Section -->
    <div class="mb-12">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h3 class="text-3xl font-bold text-gray-900 mb-2">المعلمون الأكاديميون المتميزون</h3>
          <p class="text-gray-600">نخبة من أفضل المعلمين المتخصصين في جميع المواد الأكاديمية</p>
        </div>
        @if($academicTeachers->count() > 0)
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center px-6 py-3 bg-violet-600 text-white rounded-lg font-semibold hover:bg-violet-700 transition-colors whitespace-nowrap">
          عرض المزيد
          <i class="ri-arrow-left-line mr-2"></i>
        </a>
        @endif
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($academicTeachers->take(2) as $teacher)
          <x-academic-teacher-card-list :teacher="$teacher" :academy="$academy" :subjects="$subjects ?? collect()" :gradeLevels="$gradeLevels ?? collect()" />
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
    </div>
      </div>
    </div>
  </div>
</section> 