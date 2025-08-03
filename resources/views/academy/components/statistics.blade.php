<!-- Statistics Section -->
<section class="bg-white py-16" role="region" aria-labelledby="stats-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h2 id="stats-heading" class="text-3xl font-bold text-center text-gray-900 mb-12">إنجازاتنا بالأرقام</h2>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary/10 rounded-full mx-auto mb-4" aria-hidden="true">
          <i class="ri-user-line text-2xl text-primary"></i>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary mb-2" data-target="{{ $academy->stats_students ?? 15000 }}">0</div>
        <p class="text-gray-600">طالب وطالبة نشط</p>
        <p class="text-sm text-gray-500 mt-1">من جميع أنحاء العالم</p>
      </div>
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary/10 rounded-full mx-auto mb-4" aria-hidden="true">
          <i class="ri-user-star-line text-2xl text-primary"></i>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary mb-2" data-target="{{ $academy->stats_teachers ?? 500 }}">0</div>
        <p class="text-gray-600">معلم متخصص</p>
        <p class="text-sm text-gray-500 mt-1">حاصلون على شهادات معتمدة</p>
      </div>
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary/10 rounded-full mx-auto mb-4" aria-hidden="true">
          <i class="ri-book-line text-2xl text-primary"></i>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary mb-2" data-target="{{ $academy->stats_courses ?? 1200 }}">0</div>
        <p class="text-gray-600">كورس تعليمي</p>
        <p class="text-sm text-gray-500 mt-1">في مختلف التخصصات</p>
      </div>
      <div class="text-center">
        <div class="w-16 h-16 flex items-center justify-center bg-primary/10 rounded-full mx-auto mb-4" aria-hidden="true">
          <i class="ri-award-line text-2xl text-primary"></i>
        </div>
        <div class="stats-counter text-3xl font-bold text-primary mb-2" data-target="{{ $academy->stats_success_rate ?? 95 }}">0</div>
        <p class="text-gray-600">نسبة النجاح</p>
        <p class="text-sm text-gray-500 mt-1">معدل إتمام الطلاب للكورسات</p>
      </div>
    </div>
  </div>
</section> 