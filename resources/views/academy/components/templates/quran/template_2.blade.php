<!-- Quran Section - Template 2: Clean Professional Design with Tabs -->
<section id="quran" class="py-24 relative overflow-hidden transition-colors duration-500"
         x-data="{ activeTab: 'circles' }"
         :class="activeTab === 'circles' ? 'bg-gradient-to-br from-green-100 via-green-50 to-white' : 'bg-gradient-to-br from-yellow-100 via-yellow-50 to-white'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-12">
      <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'برامج القرآن الكريم' }}</h2>
      @if(isset($subheading))
        <p class="text-lg text-gray-600 mb-8">{{ $subheading }}</p>
      @endif

      <!-- Tab Toggle -->
      <div class="inline-flex bg-white rounded-2xl p-1.5 shadow-md border border-gray-200">
        <button
          @click="activeTab = 'circles'"
          :class="activeTab === 'circles' ? 'bg-green-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-8 py-3 rounded-xl font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-group-line ml-2"></i>
          حلقات التحفيظ
        </button>
        <button
          @click="activeTab = 'teachers'"
          :class="activeTab === 'teachers' ? 'bg-yellow-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
          class="px-8 py-3 rounded-xl font-semibold transition-all duration-200 whitespace-nowrap">
          <i class="ri-user-star-line ml-2"></i>
          المعلمون
        </button>
      </div>
    </div>

    <!-- Quran Group Circles Section -->
    <div x-show="activeTab === 'circles'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-12 text-center">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">حلقات التحفيظ المتاحة</h3>
        <p class="text-gray-600">اختر الحلقة المناسبة لمستواك وابدأ رحلتك في حفظ القرآن الكريم</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        @forelse($quranCircles->take(3) as $circle)
          <x-quran-circle-card-list :circle="$circle" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-12">
            <div class="w-24 h-24 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-group-line text-2xl text-green-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد حلقات متاحة حالياً</h3>
            <p class="text-gray-600">سيتم إضافة حلقات القرآن الكريم قريباً</p>
          </div>
        @endforelse
      </div>

      @if($quranCircles->count() > 0)
      <div class="text-center">
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-green-600 font-semibold hover:text-green-700 transition-colors hover:gap-3">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>

    <!-- Quran Teachers Section -->
    <div x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0">
      <div class="mb-12 text-center">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">معلمو القرآن المتميزون</h3>
        <p class="text-gray-600">نخبة من أفضل معلمي القرآن الكريم المؤهلين لتعليمك</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
        @forelse($quranTeachers->take(2) as $teacher)
          <x-quran-teacher-card-list :teacher="$teacher" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-12">
            <div class="w-24 h-24 bg-yellow-50 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="ri-user-star-line text-2xl text-yellow-600"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد معلمين متاحين حالياً</h3>
            <p class="text-gray-600">سيتم إضافة معلمي القرآن الكريم قريباً</p>
          </div>
        @endforelse
      </div>

      @if($quranTeachers->count() > 0)
      <div class="text-center">
        <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-yellow-600 font-semibold hover:text-yellow-700 transition-colors hover:gap-3">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>
  </div>
</section>
