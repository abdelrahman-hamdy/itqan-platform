<!-- Quran Section -->
<section id="quran" class="py-24 bg-gradient-to-br from-emerald-50 via-white to-teal-50 relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-20">
      <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-primary to-secondary rounded-2xl mx-auto mb-8 shadow-lg">
        <i class="ri-book-open-line text-4xl text-white"></i>
      </div>
      <div class="max-w-4xl mx-auto">
        <h2 class="text-5xl font-bold text-gray-900 mb-6 leading-tight">
          قسم <span class="text-primary">القرآن الكريم</span>
        </h2>
        <p class="text-xl text-gray-600 leading-relaxed mb-8">
          تعلم وحفظ القرآن الكريم مع معلمين متخصصين في بيئة تعليمية متميزة تجمع بين الأصالة والحداثة
        </p>
        <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-500">
          <div class="flex items-center gap-2">
            <i class="ri-shield-check-line text-primary"></i>
            <span>معلمون معتمدون</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="ri-time-line text-primary"></i>
            <span>مواعيد مرنة</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="ri-award-line text-primary"></i>
            <span>شهادات معتمدة</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Quran Group Circles Section -->
    <div class="mb-24">
      <div class="mb-12">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">حلقات التحفيظ المتاحة</h3>
        <p class="text-gray-600">اختر الحلقة المناسبة لمستواك وابدأ رحلتك في حفظ القرآن الكريم</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($quranCircles->take(3) as $circle)
          <x-quran-circle-card :circle="$circle" :academy="$academy" />
        @empty
          <!-- No circles available message -->
          <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-group-line text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد حلقات متاحة حالياً</h3>
            <p class="text-gray-600 mb-4">سيتم إضافة حلقات القرآن الكريم قريباً</p>
            <a href="{{ route('public.quran-circles.index', ['subdomain' => $academy->subdomain]) }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
              <i class="ri-arrow-left-line ml-2"></i>
              عرض جميع الحلقات
            </a>
          </div>
        @endforelse
        
        @if($quranCircles->count() > 0)
          <!-- See More Card - Only show when there are circles -->
          <a href="{{ route('public.quran-circles.index', ['subdomain' => $academy->subdomain]) }}" 
             class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 see-more-card block">
            <div class="flex flex-col items-center justify-center h-full text-center">
              <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
                <i class="ri-add-line text-primary text-xl"></i>
              </div>
              <h3 class="font-semibold text-gray-900 mb-2">عرض المزيد</h3>
              <p class="text-sm text-gray-600 mb-4">اكتشف جميع حلقات التحفيظ المتاحة</p>
              <div class="w-full flex items-center justify-center">
                <span class="text-primary font-semibold text-sm">
                  <i class="ri-arrow-left-line see-more-arrow mr-2"></i>
                  عرض جميع الحلقات
                </span>
              </div>
            </div>
          </a>
        @endif
      </div>
    </div>
      
    <!-- Quran Teachers Section -->
    <div class="mb-12">
      <div class="mb-8">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">معلمو القرآن المتميزون</h3>
        <p class="text-gray-600">نخبة من أفضل معلمي القرآن الكريم المؤهلين لتعليمك</p>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($quranTeachers->take(3) as $teacher)
          <x-quran-teacher-card :teacher="$teacher" :academy="$academy" />
        @empty
          <!-- No teachers available message -->
          <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-user-star-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد معلمين متاحين حالياً</h3>
            <p class="text-gray-600 mb-4">سيتم إضافة معلمي القرآن الكريم قريباً</p>
            <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
              <i class="ri-arrow-left-line ml-2"></i>
              عرض جميع المعلمين
            </a>
          </div>
        @endforelse
        
        <!-- See More Card -->
        <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
           class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 see-more-card block">
          <div class="flex flex-col items-center justify-center h-full text-center">
            <div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center mb-4">
              <i class="ri-add-line text-primary text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">عرض المزيد</h3>
            <p class="text-sm text-gray-600 mb-4">اكتشف جميع معلمي القرآن الكريم</p>
            <div class="w-full flex items-center justify-center">
              <span class="text-primary font-semibold text-sm">
                <i class="ri-arrow-left-line see-more-arrow mr-2"></i>
                عرض جميع المعلمين
              </span>
            </div>
          </div>
        </a>
      </div>
    </div>
    </div>
  </div>
</section> 