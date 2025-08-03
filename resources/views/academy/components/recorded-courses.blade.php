<!-- Recorded Courses Section -->
<section id="courses" class="py-20 bg-gradient-to-b from-gray-50 to-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <div class="w-20 h-20 flex items-center justify-center bg-primary/10 rounded-full mx-auto mb-6">
        <i class="ri-play-circle-line text-3xl text-primary"></i>
      </div>
      <h2 class="text-4xl font-bold text-gray-900 mb-4">الكورسات المسجلة</h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        كورسات جاهزة ومسجلة بجودة عالية، تعلم في أي وقت وبالسرعة التي تناسبك
      </p>
    </div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
      @forelse($recordedCourses as $course)
        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
          <div class="h-48 relative overflow-hidden">
            @if($course->thumbnail)
              <img src="{{ $course->thumbnail }}" alt="{{ $course->name }}" class="w-full h-full object-cover object-top">
            @else
              <div class="w-full h-full bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center">
                <i class="ri-play-circle-line text-6xl text-white opacity-50"></i>
              </div>
            @endif
            <div class="absolute top-4 right-4 bg-primary text-white px-3 py-1 rounded-full text-sm font-semibold">
              {{ $course->grade_level ?? 'الصف الثالث الثانوي' }}
            </div>
          </div>
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $course->name }}</h3>
            <p class="text-gray-600 mb-4">{{ $course->description ?? 'شرح شامل لمنهج الرياضيات مع حل التمارين والأمثلة التطبيقية' }}</p>
            <div class="flex items-center justify-between mb-4">
              <span class="text-2xl font-bold text-primary">{{ $course->price ?? 299 }} ر.س</span>
              <div class="flex items-center text-yellow-500">
                <div class="w-4 h-4 flex items-center justify-center ml-1">
                  <i class="ri-star-fill"></i>
                </div>
                <span class="text-gray-700">{{ $course->rating ?? 4.8 }} ({{ $course->reviews_count ?? 124 }})</span>
              </div>
            </div>
            <a href="#" 
               class="w-full bg-primary text-white py-3 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom text-center block" 
               aria-label="عرض تفاصيل الكورس المسجل">
              عرض التفاصيل
            </a>
          </div>
        </div>
      @empty
        <!-- Default courses -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
          <div class="h-48 relative overflow-hidden">
            <img src="https://readdy.ai/api/search-image?query=Mathematics%20course%20thumbnail%20with%20equations%20and%20geometric%20shapes%2C%20educational%20content%20design%2C%20professional%20course%20cover%2C%20mathematical%20formulas%20and%20graphs%2C%20clean%20educational%20layout%2C%20academic%20subject%20illustration&width=400&height=300&seq=course001&orientation=landscape" alt="كورس الرياضيات" class="w-full h-full object-cover object-top">
            <div class="absolute top-4 right-4 bg-primary text-white px-3 py-1 rounded-full text-sm font-semibold">
              الصف الثالث الثانوي
            </div>
          </div>
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">الرياضيات المتقدمة</h3>
            <p class="text-gray-600 mb-4">شرح شامل لمنهج الرياضيات مع حل التمارين والأمثلة التطبيقية</p>
            <div class="flex items-center justify-between mb-4">
              <span class="text-2xl font-bold text-primary">299 ر.س</span>
              <div class="flex items-center text-yellow-500">
                <div class="w-4 h-4 flex items-center justify-center ml-1">
                  <i class="ri-star-fill"></i>
                </div>
                <span class="text-gray-700">4.8 (124)</span>
              </div>
            </div>
            <button class="w-full bg-primary text-white py-3 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل الكورس المسجل">
              عرض التفاصيل
            </button>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
          <div class="h-48 relative overflow-hidden">
            <img src="https://readdy.ai/api/search-image?query=Physics%20course%20thumbnail%20with%20scientific%20formulas%20and%20laboratory%20equipment%2C%20educational%20physics%20content%2C%20atoms%20and%20molecules%20illustration%2C%20scientific%20diagrams%20and%20experiments%2C%20professional%20educational%20design&width=400&height=300&seq=course002&orientation=landscape" alt="كورس الفيزياء" class="w-full h-full object-cover object-top">
            <div class="absolute top-4 right-4 bg-primary text-white px-3 py-1 rounded-full text-sm font-semibold">
              الصف الثاني الثانوي
            </div>
          </div>
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">الفيزياء التطبيقية</h3>
            <p class="text-gray-600 mb-4">فهم قوانين الفيزياء من خلال التجارب العملية والأمثلة الواقعية</p>
            <div class="flex items-center justify-between mb-4">
              <span class="text-2xl font-bold text-primary">349 ر.س</span>
              <div class="flex items-center text-yellow-500">
                <div class="w-4 h-4 flex items-center justify-center ml-1">
                  <i class="ri-star-fill"></i>
                </div>
                <span class="text-gray-700">4.9 (89)</span>
              </div>
            </div>
            <button class="w-full bg-primary text-white py-3 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل الكورس المسجل">
              عرض التفاصيل
            </button>
          </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover">
          <div class="h-48 relative overflow-hidden">
            <img src="https://readdy.ai/api/search-image?query=Arabic%20language%20course%20thumbnail%20with%20beautiful%20Arabic%20calligraphy%20and%20literature%20books%2C%20educational%20Arabic%20content%20design%2C%20classical%20Arabic%20texts%20and%20poetry%2C%20linguistic%20studies%20illustration%2C%20professional%20educational%20layout&width=400&height=300&seq=course003&orientation=landscape" alt="كورس اللغة العربية" class="w-full h-full object-cover object-top">
            <div class="absolute top-4 right-4 bg-primary text-white px-3 py-1 rounded-full text-sm font-semibold">
              الصف الأول الثانوي
            </div>
          </div>
          <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-2">اللغة العربية والأدب</h3>
            <p class="text-gray-600 mb-4">تعلم قواعد اللغة العربية وتذوق الأدب والشعر العربي الأصيل</p>
            <div class="flex items-center justify-between mb-4">
              <span class="text-2xl font-bold text-primary">249 ر.س</span>
              <div class="flex items-center text-yellow-500">
                <div class="w-4 h-4 flex items-center justify-center ml-1">
                  <i class="ri-star-fill"></i>
                </div>
                <span class="text-gray-700">4.7 (156)</span>
              </div>
            </div>
            <button class="w-full bg-primary text-white py-3 !rounded-button font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="عرض تفاصيل الكورس المسجل">
              عرض التفاصيل
            </button>
          </div>
        </div>
      @endforelse
    </div>
    <div class="text-center mt-8">
      <a href="#" 
         class="bg-white border-2 border-primary text-primary px-8 py-3 !rounded-button font-semibold hover:bg-primary hover:text-white transition-colors duration-200 whitespace-nowrap focus:ring-custom" 
         aria-label="اعرض المزيد من الكورسات المسجلة المتاحة">
        اعرض المزيد من الكورسات المسجلة
      </a>
    </div>
  </div>
</section> 