<!-- Courses Section - Template 3: Classic Simple Design with Cyan Background -->
<section id="courses" class="py-16 bg-cyan-50/70">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header - Right Aligned -->
    <div class="text-right mb-10">
      <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? 'الكورسات المسجلة' }}</h2>
      @if(isset($subheading))
        <p class="text-base text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Courses Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      @forelse($recordedCourses->take(6) as $course)
        <x-course-card :course="$course" :academy="$academy" />
      @empty
        <div class="col-span-full text-center py-10">
          <div class="w-16 h-16 bg-gray-100 rounded-lg flex items-center justify-center mx-auto mb-3">
            <i class="ri-play-circle-line text-xl text-gray-400"></i>
          </div>
          <h3 class="text-base font-semibold text-gray-900 mb-1">لا توجد كورسات مسجلة متاحة حالياً</h3>
          <p class="text-sm text-gray-600">سيتم إضافة الكورسات قريباً</p>
        </div>
      @endforelse
    </div>

    <!-- View More Button -->
    @if($recordedCourses->count() > 0)
    <div class="text-center">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center gap-2 px-5 py-2.5 bg-cyan-600/10 text-cyan-700 rounded-md font-medium hover:bg-cyan-600/20 transition-colors text-sm">
        عرض المزيد
        <i class="ri-arrow-left-line"></i>
      </a>
    </div>
    @endif
  </div>
</section>
