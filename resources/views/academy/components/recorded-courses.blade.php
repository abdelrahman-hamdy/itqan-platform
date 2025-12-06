<!-- Recorded Courses Section -->
<section id="courses" class="py-20 bg-gradient-to-b from-gray-50 to-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 class="text-3xl font-bold text-black mb-12">الكورسات المسجلة</h2>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
      @forelse($recordedCourses as $course)
        <x-course-card :course="$course" :academy="$academy" />
      @empty
        <div class="col-span-full text-center py-12">
          <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-play-circle-line text-green-400 text-4xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد كورسات مسجلة متاحة حالياً</h3>
          <p class="text-gray-600">سيتم إضافة الكورسات قريباً</p>
        </div>
      @endforelse
    </div>
    @if($recordedCourses->count() > 0)
    <div class="text-center mt-8">
      <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}"
         class="inline-flex items-center px-6 py-3 bg-cyan-500 text-white rounded-lg font-semibold hover:bg-cyan-600 transition-colors">
        عرض المزيد
        <i class="ri-arrow-left-line mr-2"></i>
      </a>
    </div>
    @endif
  </div>
</section> 