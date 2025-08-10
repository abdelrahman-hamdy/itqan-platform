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
    
    <!-- Available Quran Circles Section -->
    <div class="mb-24">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-12 gap-4">
        <div>
          <h3 class="text-3xl font-bold text-gray-900 mb-2">حلقات التحفيظ المتاحة</h3>
          <p class="text-gray-600">اختر الحلقة المناسبة لمستواك وابدأ رحلتك في حفظ القرآن الكريم</p>
        </div>
        <div class="flex gap-3">
          <button class="circle-prev w-14 h-14 flex items-center justify-center bg-white rounded-xl shadow-lg hover:bg-gray-50 hover:shadow-xl transition-all duration-300 focus:ring-custom border border-gray-100" aria-label="الحلقة السابقة">
            <i class="ri-arrow-right-s-line text-xl text-gray-700"></i>
          </button>
          <button class="circle-next w-14 h-14 flex items-center justify-center bg-white rounded-xl shadow-lg hover:bg-gray-50 hover:shadow-xl transition-all duration-300 focus:ring-custom border border-gray-100" aria-label="الحلقة التالية">
            <i class="ri-arrow-left-s-line text-xl text-gray-700"></i>
          </button>
        </div>
      </div>
      <div class="circles-slider overflow-hidden">
        <div class="circles-track flex gap-8 transition-transform duration-300 w-[calc(320px*3+2rem*2)] mx-auto">
          @forelse($quranCircles as $circle)
            <div class="bg-white rounded-xl shadow-lg p-6 min-w-[300px] card-hover">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h4 class="text-lg font-bold text-gray-900">{{ $circle->name }}</h4>
                  <p class="text-sm text-gray-600">مع {{ $circle->teacher->name ?? 'الشيخ عبدالرحمن السديس' }}</p>
                </div>
                <span class="bg-{{ $circle->status === 'available' ? 'green' : 'yellow' }}-100 text-{{ $circle->status === 'available' ? 'green' : 'yellow' }}-800 text-xs font-semibold px-3 py-1 rounded-full">
                  {{ $circle->status === 'available' ? 'متاح' : 'قريباً' }}
                </span>
              </div>
              <div class="space-y-3 mb-4">
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-time-line"></i>
                  </div>
                  {{ $circle->schedule ?? 'السبت والثلاثاء - 8:00 مساءً' }}
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-group-line"></i>
                  </div>
                  {{ $circle->enrolled_students ?? 8 }} من {{ $circle->max_students ?? 12 }} طالب
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-calendar-line"></i>
                  </div>
                  يبدأ {{ $circle->start_date ?? '15 أغسطس 2025' }}
                </div>
              </div>
              <a href="{{ route('public.quran-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}" 
                 class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
                 aria-label="انضم لحلقة {{ $circle->name }}">
                {{ $circle->status === 'available' ? 'انضم للحلقة' : 'سجل اهتمامك' }}
              </a>
            </div>
          @empty
            <!-- Default circles -->
            <div class="bg-white rounded-xl shadow-lg p-6 min-w-[300px] card-hover">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h4 class="text-lg font-bold text-gray-900">حلقة سورة البقرة</h4>
                  <p class="text-sm text-gray-600">مع الشيخ عبدالرحمن السديس</p>
                </div>
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">متاح</span>
              </div>
              <div class="space-y-3 mb-4">
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-time-line"></i>
                  </div>
                  السبت والثلاثاء - 8:00 مساءً
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-group-line"></i>
                  </div>
                  8 من 12 طالب
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-calendar-line"></i>
                  </div>
                  يبدأ 15 أغسطس 2025
                </div>
              </div>
              <a href="{{ route('public.quran-circles.index', ['subdomain' => $academy->subdomain]) }}" 
                 class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
                 aria-label="انضم لحلقة سورة البقرة">
                انضم للحلقة
              </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 min-w-[300px] card-hover">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h4 class="text-lg font-bold text-gray-900">حلقة جزء عم</h4>
                  <p class="text-sm text-gray-600">مع الشيخ محمد الغامدي</p>
                </div>
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">متاح</span>
              </div>
              <div class="space-y-3 mb-4">
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-time-line"></i>
                  </div>
                  الأحد والأربعاء - 7:00 مساءً
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-group-line"></i>
                  </div>
                  5 من 10 طالب
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-calendar-line"></i>
                  </div>
                  يبدأ 20 أغسطس 2025
                </div>
              </div>
              <a href="{{ route('public.quran-circles.index', ['subdomain' => $academy->subdomain]) }}" 
                 class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap text-center block">
                انضم للحلقة
              </a>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-6 min-w-[300px] card-hover">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h4 class="text-lg font-bold text-gray-900">حلقة سورة آل عمران</h4>
                  <p class="text-sm text-gray-600">مع الشيخ فهد الكندري</p>
                </div>
                <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">متاح</span>
              </div>
              <div class="space-y-3 mb-4">
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-time-line"></i>
                  </div>
                  الاثنين والخميس - 9:00 مساءً
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-group-line"></i>
                  </div>
                  6 من 12 طالب
                </div>
                <div class="flex items-center text-sm text-gray-600">
                  <div class="w-5 h-5 flex items-center justify-center ml-2">
                    <i class="ri-calendar-line"></i>
                  </div>
                  يبدأ 25 أغسطس 2025
                </div>
              </div>
              <a href="{{ route('public.quran-circles.index', ['subdomain' => $academy->subdomain]) }}" 
                 class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap text-center block">
                انضم للحلقة
              </a>
            </div>
          @endforelse
        </div>
        <!-- Carousel Pagination Dots -->
        <div class="carousel-dots" id="circles-dots">
          @for($i = 0; $i < min(count($quranCircles), 4); $i++)
            <div class="carousel-dot {{ $i === 0 ? 'active' : '' }}" data-slide="{{ $i }}" aria-label="الحلقة {{ $i + 1 }}"></div>
          @endfor
        </div>
      </div>
      <div class="text-center mb-16 mt-12">
        <a href="{{ route('public.quran-circles.index', ['subdomain' => $academy->subdomain]) }}" 
           class="bg-white border-2 border-primary text-primary px-8 py-3 !rounded-button font-semibold hover:bg-primary hover:text-white transition-colors duration-200 whitespace-nowrap focus:ring-custom" 
           aria-label="عرض جميع حلقات التحفيظ المتاحة">
          عرض جميع حلقات التحفيظ
        </a>
      </div>
      
      <!-- Quran Teachers Section -->
      <div class="mb-12">
        <div class="flex justify-between items-center mb-8">
          <h3 class="text-2xl font-bold text-gray-900">معلمو القرآن المتميزون</h3>
          <div class="flex gap-2">
            <button class="teacher-prev w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-md hover:bg-gray-50 focus:ring-custom" aria-label="المعلم السابق">
              <i class="ri-arrow-right-s-line text-xl text-gray-600"></i>
            </button>
            <button class="teacher-next w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-md hover:bg-gray-50 focus:ring-custom" aria-label="المعلم التالي">
              <i class="ri-arrow-left-s-line text-xl text-gray-600"></i>
            </button>
          </div>
        </div>
        <div class="teachers-slider overflow-hidden">
          <div class="teachers-track flex gap-6 transition-transform duration-300">
            @forelse($quranTeachers as $teacher)
              <div class="bg-white rounded-xl shadow-lg p-6 min-w-[280px] card-hover">
                <div class="flex items-center gap-4 mb-4">
                  <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                    @if($teacher->avatar)
                      <img src="{{ $teacher->avatar }}" alt="{{ $teacher->name }}" class="w-16 h-16 rounded-full object-cover">
                    @else
                      <i class="ri-user-smile-line text-3xl text-gray-400"></i>
                    @endif
                  </div>
                  <div>
                    <h4 class="text-lg font-bold text-gray-900">{{ $teacher->name }}</h4>
                    <p class="text-sm text-gray-600">{{ $teacher->qualification ?? 'إجازة في القراءات العشر' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-2 mb-4">
                  <div class="flex text-yellow-400">
                    @for($i = 1; $i <= 5; $i++)
                      <i class="ri-star-{{ $i <= ($teacher->rating ?? 5) ? 'fill' : 'line' }}"></i>
                    @endfor
                  </div>
                  <span class="text-sm text-gray-600">{{ $teacher->rating ?? 5.0 }} ({{ $teacher->reviews_count ?? 120 }} تقييم)</span>
                </div>
                <div class="space-y-2 mb-4">
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-time-line"></i>
                    </div>
                    {{ $teacher->experience_years ?? 15 }} سنة خبرة
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-group-line"></i>
                    </div>
                    {{ $teacher->students_count ?? 500 }}+ طالب
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-book-open-line"></i>
                    </div>
                    {{ $teacher->specialization ?? 'متخصص في تعليم الكبار' }}
                  </div>
                </div>
                <a href="{{ route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id]) }}" 
                   class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
                   aria-label="عرض تفاصيل {{ $teacher->name }}">
                  عرض التفاصيل
                </a>
              </div>
            @empty
              <!-- Default teachers -->
              <div class="bg-white rounded-xl shadow-lg p-6 min-w-[280px] card-hover">
                <div class="flex items-center gap-4 mb-4">
                  <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="ri-user-smile-line text-3xl text-gray-400"></i>
                  </div>
                  <div>
                    <h4 class="text-lg font-bold text-gray-900">الشيخ عبدالله العتيبي</h4>
                    <p class="text-sm text-gray-600">إجازة في القراءات العشر</p>
                  </div>
                </div>
                <div class="flex items-center gap-2 mb-4">
                  <div class="flex text-yellow-400">
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                  </div>
                  <span class="text-sm text-gray-600">5.0 (120 تقييم)</span>
                </div>
                <div class="space-y-2 mb-4">
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-time-line"></i>
                    </div>
                    15 سنة خبرة
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-group-line"></i>
                    </div>
                    500+ طالب
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-book-open-line"></i>
                    </div>
                    متخصص في تعليم الكبار
                  </div>
                </div>
                <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
                   class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
                   aria-label="عرض تفاصيل المعلم">
                  عرض التفاصيل
                </a>
              </div>
              
              <div class="bg-white rounded-xl shadow-lg p-6 min-w-[280px] card-hover">
                <div class="flex items-center gap-4 mb-4">
                  <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="ri-user-smile-line text-3xl text-gray-400"></i>
                  </div>
                  <div>
                    <h4 class="text-lg font-bold text-gray-900">الشيخة نورة القحطاني</h4>
                    <p class="text-sm text-gray-600">إجازة في رواية حفص</p>
                  </div>
                </div>
                <div class="flex items-center gap-2 mb-4">
                  <div class="flex text-yellow-400">
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-half-fill"></i>
                  </div>
                  <span class="text-sm text-gray-600">4.8 (95 تقييم)</span>
                </div>
                <div class="space-y-2 mb-4">
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-time-line"></i>
                    </div>
                    10 سنوات خبرة
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-group-line"></i>
                    </div>
                    300+ طالبة
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-book-open-line"></i>
                    </div>
                    متخصصة في تعليم الأطفال
                  </div>
                </div>
                <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
                   class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
                   aria-label="عرض تفاصيل المعلم">
                  عرض التفاصيل
                </a>
              </div>
              
              <div class="bg-white rounded-xl shadow-lg p-6 min-w-[280px] card-hover">
                <div class="flex items-center gap-4 mb-4">
                  <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                    <i class="ri-user-smile-line text-3xl text-gray-400"></i>
                  </div>
                  <div>
                    <h4 class="text-lg font-bold text-gray-900">الشيخ محمد السبيعي</h4>
                    <p class="text-sm text-gray-600">إجازة في القراءات السبع</p>
                  </div>
                </div>
                <div class="flex items-center gap-2 mb-4">
                  <div class="flex text-yellow-400">
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                    <i class="ri-star-fill"></i>
                  </div>
                  <span class="text-sm text-gray-600">4.9 (150 تقييم)</span>
                </div>
                <div class="space-y-2 mb-4">
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-time-line"></i>
                    </div>
                    12 سنة خبرة
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-group-line"></i>
                    </div>
                    400+ طالب
                  </div>
                  <div class="flex items-center text-sm text-gray-600">
                    <div class="w-5 h-5 flex items-center justify-center ml-2">
                      <i class="ri-book-open-line"></i>
                    </div>
                    متخصص في القراءات
                  </div>
                </div>
                <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
                   class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
                   aria-label="عرض تفاصيل المعلم">
                  عرض التفاصيل
                </a>
              </div>
            @endforelse
          </div>
          <!-- Teachers Pagination Dots -->
          <div class="carousel-dots" id="teachers-dots">
            @for($i = 0; $i < min(count($quranTeachers), 4); $i++)
              <div class="carousel-dot {{ $i === 0 ? 'active' : '' }}" data-slide="{{ $i }}" aria-label="المعلم {{ $i + 1 }}"></div>
            @endfor
          </div>
        </div>
      </div>
      <div class="text-center mt-8">
        <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
           class="bg-white border-2 border-primary text-primary px-8 py-3 !rounded-button font-semibold hover:bg-primary hover:text-white transition-colors duration-200 whitespace-nowrap focus:ring-custom" 
           aria-label="عرض جميع معلمي القرآن الكريم">
          عرض جميع معلمي القرآن الكريم
        </a>
      </div>
    </div>
  </div>
</section> 