<!-- Academic Section -->
<section id="academic" class="py-24 bg-gradient-to-br from-blue-50 via-white to-indigo-50 relative overflow-hidden">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-20">
      <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-secondary to-primary rounded-2xl mx-auto mb-8 shadow-lg">
        <i class="ri-graduation-cap-line text-4xl text-white"></i>
      </div>
      <div class="max-w-4xl mx-auto">
        <h2 class="text-5xl font-bold text-gray-900 mb-6 leading-tight">
          القسم <span class="text-secondary">الأكاديمي</span>
        </h2>
        <p class="text-xl text-gray-600 leading-relaxed mb-8">
          تعلم المواد الأكاديمية لجميع المراحل الدراسية مع أفضل المعلمين المتخصصين وأحدث الطرق التعليمية
        </p>
        <div class="flex flex-wrap justify-center gap-6 text-sm text-gray-500">
          <div class="flex items-center gap-2">
            <i class="ri-microscope-line text-secondary"></i>
            <span>مختبرات متطورة</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="ri-presentation-line text-secondary"></i>
            <span>تعلم تفاعلي</span>
          </div>
          <div class="flex items-center gap-2">
            <i class="ri-medal-line text-secondary"></i>
            <span>نتائج مضمونة</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Interactive Courses Section -->
    <div class="mb-24">
      <div class="mb-12">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">الكورسات التفاعلية المتاحة</h3>
        <p class="text-gray-600">كورسات شاملة ومتطورة تغطي جميع المواد الأكاديمية بأسلوب تفاعلي ممتع</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($interactiveCourses->take(3) as $course)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="relative mb-4">
              <div class="h-32 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg">
                @if($course->thumbnail_url)
                  <img src="{{ $course->thumbnail_url }}" alt="{{ $course->name }}" class="w-full h-full object-cover object-top rounded-lg">
                @else
                  <div class="w-full h-full flex items-center justify-center rounded-lg">
                    <i class="ri-book-open-line text-4xl text-white opacity-50"></i>
                  </div>
                @endif
              </div>
              <span class="absolute top-2 right-2 bg-{{ $course->status === 'available' ? 'green' : 'yellow' }}-100 text-{{ $course->status === 'available' ? 'green' : 'yellow' }}-800 text-xs font-semibold px-2 py-1 rounded-full">
                {{ $course->status === 'available' ? 'متاح' : 'قريباً' }}
              </span>
              <span class="absolute top-2 left-2 bg-primary text-white text-xs font-semibold px-2 py-1 rounded-full">{{ $course->subject ?? 'الرياضيات' }}</span>
            </div>
            
            <h4 class="text-lg font-bold text-gray-900 mb-2">{{ $course->name }}</h4>
            <p class="text-sm text-gray-600 mb-4">{{ $course->description ?? 'كورس أكاديمي تفاعلي مميز' }}</p>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>يبدأ {{ $course->start_date ?? '15 أغسطس 2025' }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>{{ $course->duration ?? 8 }} أسابيع</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>{{ $course->enrolled_students ?? 15 }}/{{ $course->max_students ?? 20 }} طالب</span>
              </div>
            </div>
            
            <div class="flex items-center justify-between mb-4">
              <span class="font-bold text-primary text-lg">{{ $course->price ?? 299 }} ر.س</span>
            </div>
            
            <a href="#" 
               class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
               aria-label="سجل في الكورس">
              {{ $course->status === 'available' ? 'عرض التفاصيل' : 'قريباً' }}
            </a>
          </div>
        @empty
          <!-- Default courses -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="relative mb-4">
              <div class="h-32 bg-gradient-to-br from-purple-400 to-purple-600 rounded-lg">
                <img src="https://readdy.ai/api/search-image?query=Mathematics%20education%20with%20modern%20digital%20tools%2C%20clean%20professional%20classroom%20environment%2C%20mathematical%20equations%20and%20graphs%20on%20screen&width=400&height=300&seq=course101&orientation=landscape" alt="الرياضيات المتقدمة" class="w-full h-full object-cover object-top rounded-lg">
              </div>
              <span class="absolute top-2 right-2 bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">متاح</span>
              <span class="absolute top-2 left-2 bg-primary text-white text-xs font-semibold px-2 py-1 rounded-full">الرياضيات</span>
            </div>
            
            <h4 class="text-lg font-bold text-gray-900 mb-2">الرياضيات المتقدمة - الصف الثالث</h4>
            <p class="text-sm text-gray-600 mb-4">كورس شامل في الرياضيات المتقدمة</p>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>يبدأ 15 أغسطس 2025</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>8 أسابيع</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>15/20 طالب</span>
              </div>
            </div>
            
            <div class="flex items-center justify-between mb-4">
              <span class="font-bold text-primary text-lg">299 ر.س</span>
            </div>
            
            <button class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل الكورس">
              عرض التفاصيل
            </button>
          </div>
          
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="relative mb-4">
              <div class="h-32 bg-gradient-to-br from-blue-400 to-blue-600 rounded-lg">
                <img src="https://readdy.ai/api/search-image?query=Physics%20laboratory%20with%20modern%20equipment%2C%20scientific%20experiments%20setup%2C%20professional%20educational%20environment&width=400&height=300&seq=course102&orientation=landscape" alt="الفيزياء" class="w-full h-full object-cover object-top rounded-lg">
              </div>
              <span class="absolute top-2 right-2 bg-green-100 text-green-800 text-xs font-semibold px-2 py-1 rounded-full">متاح</span>
              <span class="absolute top-2 left-2 bg-primary text-white text-xs font-semibold px-2 py-1 rounded-full">الفيزياء</span>
            </div>
            
            <h4 class="text-lg font-bold text-gray-900 mb-2">الفيزياء - الصف الثاني</h4>
            <p class="text-sm text-gray-600 mb-4">كورس تفاعلي في الفيزياء</p>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>يبدأ 20 أغسطس 2025</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>10 أسابيع</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>12/25 طالب</span>
              </div>
            </div>
            
            <div class="flex items-center justify-between mb-4">
              <span class="font-bold text-primary text-lg">349 ر.س</span>
            </div>
            
            <button class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل الكورس">
              عرض التفاصيل
            </button>
          </div>
          
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="relative mb-4">
              <div class="h-32 bg-gradient-to-br from-green-400 to-green-600 rounded-lg">
                <img src="https://readdy.ai/api/search-image?query=Chemistry%20class%20with%20modern%20lab%20equipment%2C%20scientific%20experiments%2C%20professional%20educational%20setting&width=400&height=300&seq=course103&orientation=landscape" alt="الكيمياء" class="w-full h-full object-cover object-top rounded-lg">
              </div>
              <span class="absolute top-2 right-2 bg-yellow-100 text-yellow-800 text-xs font-semibold px-2 py-1 rounded-full">قريباً</span>
              <span class="absolute top-2 left-2 bg-primary text-white text-xs font-semibold px-2 py-1 rounded-full">الكيمياء</span>
            </div>
            
            <h4 class="text-lg font-bold text-gray-900 mb-2">الكيمياء - الصف الثالث</h4>
            <p class="text-sm text-gray-600 mb-4">كورس متقدم في الكيمياء</p>
            
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>يبدأ 1 سبتمبر 2025</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>12 أسبوع</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>0/30 طالب</span>
              </div>
            </div>
            
            <div class="flex items-center justify-between mb-4">
              <span class="font-bold text-primary text-lg">399 ر.س</span>
            </div>
            
            <button class="w-full bg-gray-100 text-gray-400 py-2 !rounded-button font-semibold cursor-not-allowed whitespace-nowrap">
              قريباً
            </button>
          </div>
        @endforelse
        
        <!-- See More Card -->
        <a href="#" 
           class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 see-more-card block">
          <div class="flex flex-col items-center justify-center h-full text-center">
            <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center mb-4">
              <i class="ri-add-line text-secondary text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">عرض المزيد</h3>
            <p class="text-sm text-gray-600 mb-4">اكتشف جميع الكورسات الأكاديمية</p>
            <div class="w-full flex items-center justify-center">
              <span class="text-secondary font-semibold text-sm">
                <i class="ri-arrow-left-line see-more-arrow mr-2"></i>
                عرض جميع الكورسات
              </span>
            </div>
          </div>
        </a>
      </div>
      </div>
      
    <!-- Academic Teachers Section -->
    <div class="mb-12">
      <div class="mb-8">
        <h3 class="text-3xl font-bold text-gray-900 mb-2">المعلمون الأكاديميون المتميزون</h3>
        <p class="text-gray-600">نخبة من أفضل المعلمين المتخصصين في جميع المواد الأكاديمية</p>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        @forelse($academicTeachers->take(3) as $teacher)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-xl font-bold">
                @if($teacher->avatar)
                  <img src="{{ $teacher->avatar }}" alt="{{ $teacher->name }}" class="w-16 h-16 rounded-full object-cover">
                @else
                  {{ substr($teacher->name, 0, 2) }}
                @endif
              </div>
              <div>
                <h4 class="text-lg font-bold text-gray-900">{{ $teacher->name }}</h4>
                <p class="text-sm text-gray-600">{{ $teacher->qualification ?? 'دكتوراه في الرياضيات' }}</p>
              </div>
            </div>
            <div class="flex items-center gap-2 mb-4">
              <div class="flex text-yellow-400">
                @for($i = 1; $i <= 5; $i++)
                  <i class="ri-star-{{ $i <= ($teacher->rating ?? 5) ? 'fill' : 'line' }}"></i>
                @endfor
              </div>
              <span class="text-sm text-gray-600">{{ $teacher->rating ?? 5.0 }} ({{ $teacher->reviews_count ?? 180 }} تقييم)</span>
            </div>
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <div class="w-5 h-5 flex items-center justify-center ml-2">
                  <i class="ri-book-open-line"></i>
                </div>
                {{ $teacher->subject ?? 'الرياضيات المتقدمة' }}
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <div class="w-5 h-5 flex items-center justify-center ml-2">
                  <i class="ri-time-line"></i>
                </div>
                {{ $teacher->experience_years ?? 12 }} سنة خبرة
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <div class="w-5 h-5 flex items-center justify-center ml-2">
                  <i class="ri-group-line"></i>
                </div>
                {{ $teacher->students_count ?? 600 }}+ طالب
              </div>
            </div>
            <a href="#" 
               class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap text-center block" 
               aria-label="عرض تفاصيل {{ $teacher->name }}">
              عرض التفاصيل
            </a>
          </div>
        @empty
          <!-- Default teachers -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-xl font-bold">
                د.س
              </div>
              <div>
                <h4 class="text-lg font-bold text-gray-900">د. سارة المنصور</h4>
                <p class="text-sm text-gray-600">دكتوراه في الرياضيات</p>
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
              <span class="text-sm text-gray-600">5.0 (180 تقييم)</span>
            </div>
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <div class="w-5 h-5 flex items-center justify-center ml-2">
                  <i class="ri-book-open-line"></i>
                </div>
                الرياضيات المتقدمة
              </div>
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
                600+ طالب
              </div>
            </div>
            <button class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل المعلم">
              عرض التفاصيل
            </button>
          </div>
          
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-xl font-bold">
                د.م
              </div>
              <div>
                <h4 class="text-lg font-bold text-gray-900">د. محمد العمري</h4>
                <p class="text-sm text-gray-600">دكتوراه في الفيزياء</p>
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
              <span class="text-sm text-gray-600">4.8 (150 تقييم)</span>
            </div>
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <div class="w-5 h-5 flex items-center justify-center ml-2">
                  <i class="ri-book-open-line"></i>
                </div>
                الفيزياء
              </div>
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
                800+ طالب
              </div>
            </div>
            <button class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل المعلم">
              عرض التفاصيل
            </button>
          </div>
          
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-center gap-4 mb-4">
              <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-xl font-bold">
                د.ف
              </div>
              <div>
                <h4 class="text-lg font-bold text-gray-900">د. فاطمة الزهراني</h4>
                <p class="text-sm text-gray-600">دكتوراه في الكيمياء</p>
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
              <span class="text-sm text-gray-600">4.9 (200 تقييم)</span>
            </div>
            <div class="space-y-2 mb-4">
              <div class="flex items-center text-sm text-gray-600">
                <div class="w-5 h-5 flex items-center justify-center ml-2">
                  <i class="ri-book-open-line"></i>
                </div>
                الكيمياء
              </div>
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
                500+ طالب
              </div>
            </div>
            <button class="w-full bg-primary text-white py-2 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل المعلم">
              عرض التفاصيل
            </button>
          </div>
        @endforelse
        
        <!-- See More Card -->
        <a href="#" 
           class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 see-more-card block">
          <div class="flex flex-col items-center justify-center h-full text-center">
            <div class="w-12 h-12 bg-secondary/10 rounded-lg flex items-center justify-center mb-4">
              <i class="ri-add-line text-secondary text-xl"></i>
            </div>
            <h3 class="font-semibold text-gray-900 mb-2">عرض المزيد</h3>
            <p class="text-sm text-gray-600 mb-4">اكتشف جميع المعلمين الأكاديميين</p>
            <div class="w-full flex items-center justify-center">
              <span class="text-secondary font-semibold text-sm">
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
  </div>
</section> 