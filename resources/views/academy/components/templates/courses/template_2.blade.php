<!-- Courses Section - Template 2: Clean Simple Design -->
<section id="courses" class="py-16 sm:py-20 lg:py-24 bg-gradient-to-br from-cyan-100 via-cyan-50 to-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-10 sm:mb-12 lg:mb-16">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'الكورسات المسجلة' }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Courses Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8 mb-8 sm:mb-10 lg:mb-12">
      @forelse($recordedCourses->take(6) as $course)
        <x-course-card :course="$course" :academy="$academy" />
      @empty
        <div class="col-span-full text-center py-12">
          <div class="w-20 h-20 bg-cyan-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-play-circle-line text-cyan-500 text-3xl"></i>
          </div>
          <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات مسجلة متاحة حالياً</h3>
          <p class="text-sm text-gray-600">سيتم إضافة الكورسات قريباً</p>
        </div>
      @endforelse
    </div>

    <!-- View More Button -->
    @if($recordedCourses->count() > 0)
    <div class="text-center">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center gap-2 text-cyan-600 font-semibold hover:text-cyan-700 transition-colors hover:gap-3">
        عرض المزيد
        <i class="ri-arrow-left-line"></i>
      </a>
    </div>
    @endif
  </div>
</section>
