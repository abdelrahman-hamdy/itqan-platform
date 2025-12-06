<!-- Quran Section -->
<section id="quran" class="py-24 bg-gradient-to-br from-green-100 via-white to-yellow-100 relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-20">
      <h2 class="text-3xl font-bold text-black mb-4">{{ $heading ?? 'برامج القرآن الكريم' }}</h2>
      @if(isset($subheading))
        <p class="text-lg text-gray-700 mb-8">{{ $subheading }}</p>
      @endif
    </div>
    
    <!-- Quran Group Circles Section -->
    <div class="mb-24">
      <div class="mb-12 flex items-center justify-between">
        <div>
          <h3 class="text-3xl font-bold text-gray-900 mb-2">حلقات التحفيظ المتاحة</h3>
          <p class="text-gray-600">اختر الحلقة المناسبة لمستواك وابدأ رحلتك في حفظ القرآن الكريم</p>
        </div>
        @if($quranCircles->count() > 0)
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 transition-colors whitespace-nowrap">
          عرض المزيد
          <i class="ri-arrow-left-line mr-2"></i>
        </a>
        @endif
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($quranCircles->take(3) as $circle)
          <x-quran-circle-card-list :circle="$circle" :academy="$academy" />
        @empty
          <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-group-line text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد حلقات متاحة حالياً</h3>
            <p class="text-gray-600">سيتم إضافة حلقات القرآن الكريم قريباً</p>
          </div>
        @endforelse
      </div>
    </div>
      
    <!-- Quran Teachers Section -->
    <div class="mb-12">
      <div class="mb-8 flex items-center justify-between">
        <div>
          <h3 class="text-3xl font-bold text-gray-900 mb-2">معلمو القرآن المتميزون</h3>
          <p class="text-gray-600">نخبة من أفضل معلمي القرآن الكريم المؤهلين لتعليمك</p>
        </div>
        @if($quranTeachers->count() > 0)
        <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center px-6 py-3 bg-yellow-600 text-white rounded-lg font-semibold hover:bg-yellow-700 transition-colors whitespace-nowrap">
          عرض المزيد
          <i class="ri-arrow-left-line mr-2"></i>
        </a>
        @endif
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse($quranTeachers->take(2) as $teacher)
          <x-quran-teacher-card-list :teacher="$teacher" :academy="$academy" />
        @empty
          <div class="col-span-full bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-user-star-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد معلمين متاحين حالياً</h3>
            <p class="text-gray-600">سيتم إضافة معلمي القرآن الكريم قريباً</p>
          </div>
        @endforelse
      </div>
    </div>
    </div>
  </div>
</section> 