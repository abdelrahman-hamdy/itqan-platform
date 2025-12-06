<!-- Features Section - Template 3: Classic Simple Design -->
<section id="features" class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header - Right Aligned -->
    <div class="text-right mb-10">
      <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? 'لماذا تختار أكاديميتنا؟' }}</h2>
      @if(isset($subheading))
        <p class="text-base text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Features Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Feature 1 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="w-12 h-12 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center mb-4">
          <i class="ri-team-line text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">معلمون متخصصون</h3>
        <p class="text-sm text-gray-600 leading-relaxed">نخبة من أفضل المعلمين المؤهلين والمتخصصين في مجالاتهم</p>
      </div>

      <!-- Feature 2 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="w-12 h-12 rounded-lg bg-green-500/10 text-green-600 flex items-center justify-center mb-4">
          <i class="ri-calendar-check-line text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">جدول مرن</h3>
        <p class="text-sm text-gray-600 leading-relaxed">اختر الأوقات المناسبة لك من بين مجموعة واسعة من المواعيد</p>
      </div>

      <!-- Feature 3 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="w-12 h-12 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center mb-4">
          <i class="ri-video-line text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">تعليم عن بعد</h3>
        <p class="text-sm text-gray-600 leading-relaxed">تعلم من منزلك باستخدام أحدث تقنيات التعليم الإلكتروني</p>
      </div>

      <!-- Feature 4 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="w-12 h-12 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center mb-4">
          <i class="ri-trophy-line text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">متابعة مستمرة</h3>
        <p class="text-sm text-gray-600 leading-relaxed">تقارير دورية عن التقدم والإنجازات مع توصيات للتحسين</p>
      </div>

      <!-- Feature 5 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="w-12 h-12 rounded-lg bg-cyan-500/10 text-cyan-600 flex items-center justify-center mb-4">
          <i class="ri-book-open-line text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">مناهج متطورة</h3>
        <p class="text-sm text-gray-600 leading-relaxed">محتوى تعليمي شامل ومنظم يواكب أحدث المعايير التعليمية</p>
      </div>

      <!-- Feature 6 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
        <div class="w-12 h-12 rounded-lg bg-rose-500/10 text-rose-600 flex items-center justify-center mb-4">
          <i class="ri-customer-service-line text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2">دعم فني متواصل</h3>
        <p class="text-sm text-gray-600 leading-relaxed">فريق دعم متاح للإجابة على استفساراتك وحل مشاكلك التقنية</p>
      </div>
    </div>
  </div>
</section>
