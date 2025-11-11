<!-- Statistics Section -->
<section class="bg-white py-16">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
      <!-- Students Count -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary-100 rounded-full mx-auto mb-4">
          <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
          </svg>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary-600 mb-2 font-arabic" data-count="{{ $academy->student_count ?? 15000 }}">
          0
        </div>
        <p class="text-gray-600 font-arabic">طالب وطالبة</p>
      </div>

      <!-- Teachers Count -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary-100 rounded-full mx-auto mb-4">
          <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary-600 mb-2 font-arabic" data-count="{{ $academy->teacher_count ?? 500 }}">
          0
        </div>
        <p class="text-gray-600 font-arabic">معلم متخصص</p>
      </div>

      <!-- Courses Count -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary-100 rounded-full mx-auto mb-4">
          <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
          </svg>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary-600 mb-2 font-arabic" data-count="{{ $academy->course_count ?? 1200 }}">
          0
        </div>
        <p class="text-gray-600 font-arabic">كورس تعليمي</p>
      </div>

      <!-- Success Rate -->
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary-100 rounded-full mx-auto mb-4">
          <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary-600 mb-2 font-arabic" data-count="{{ $academy->success_rate ?? 95 }}">
          0
        </div>
        <p class="text-gray-600 font-arabic">نسبة النجاح</p>
      </div>
    </div>
  </div>
</section> 