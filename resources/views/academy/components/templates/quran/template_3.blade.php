<!-- Quran Section - Template 3: Classic Design with Dynamic Colored Background -->
<section id="quran" class="py-16 relative overflow-hidden transition-colors duration-500" x-data="{ activeTab: 'circles' }"
         :class="activeTab === 'circles' ? 'bg-green-50/70' : 'bg-yellow-50/70'">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header with Tabs Alongside -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-10">
      <div class="text-right">
        <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? 'برامج القرآن الكريم' }}</h2>
        @if(isset($subheading))
          <p class="text-base text-gray-600">{{ $subheading }}</p>
        @endif
      </div>

      <!-- Tab Toggle Floated Left -->
      <div class="flex gap-2 bg-white rounded-lg p-1 shadow-sm border border-gray-200 flex-shrink-0">
        <button
          @click="activeTab = 'circles'"
          :class="activeTab === 'circles' ? 'bg-green-600 text-white' : 'text-gray-600 hover:text-gray-900'"
          class="px-5 py-2 rounded-md font-medium transition-all duration-200 text-sm whitespace-nowrap">
          <i class="ri-group-line ml-1"></i>
          حلقات التحفيظ
        </button>
        <button
          @click="activeTab = 'teachers'"
          :class="activeTab === 'teachers' ? 'bg-yellow-600 text-white' : 'text-gray-600 hover:text-gray-900'"
          class="px-5 py-2 rounded-md font-medium transition-all duration-200 text-sm whitespace-nowrap">
          <i class="ri-user-star-line ml-1"></i>
          المعلمون
        </button>
      </div>
    </div>

    <!-- Quran Group Circles Section -->
    <div x-show="activeTab === 'circles'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($quranCircles->take(3) as $circle)
          <x-quran-circle-card-list :circle="$circle" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-10">
            <div class="w-16 h-16 bg-green-50 rounded-lg flex items-center justify-center mx-auto mb-3">
              <i class="ri-group-line text-xl text-green-600"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">لا توجد حلقات متاحة حالياً</h3>
            <p class="text-sm text-gray-600">سيتم إضافة حلقات القرآن الكريم قريباً</p>
          </div>
        @endforelse
      </div>

      @if($quranCircles->count() > 0)
      <div class="text-center">
        <a href="{{ route('quran-circles.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-600/10 text-green-700 rounded-md font-medium hover:bg-green-600/20 transition-colors text-sm">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>

    <!-- Quran Teachers Section -->
    <div x-show="activeTab === 'teachers'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        @forelse($quranTeachers->take(2) as $teacher)
          <x-quran-teacher-card-list :teacher="$teacher" :academy="$academy" />
        @empty
          <div class="col-span-full text-center py-10">
            <div class="w-16 h-16 bg-gray-50 rounded-lg flex items-center justify-center mx-auto mb-3">
              <i class="ri-user-star-line text-xl text-gray-400"></i>
            </div>
            <h3 class="text-base font-semibold text-gray-900 mb-1">لا توجد معلمين متاحين حالياً</h3>
            <p class="text-sm text-gray-600">سيتم إضافة معلمي القرآن الكريم قريباً</p>
          </div>
        @endforelse
      </div>

      @if($quranTeachers->count() > 0)
      <div class="text-center">
        <a href="{{ route('quran-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-yellow-600/10 text-yellow-700 rounded-md font-medium hover:bg-yellow-600/20 transition-colors text-sm">
          عرض المزيد
          <i class="ri-arrow-left-line"></i>
        </a>
      </div>
      @endif
    </div>
  </div>
</section>
