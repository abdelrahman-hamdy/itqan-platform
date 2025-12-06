<!-- Statistics Section - Template 3: Classic Simple Design -->
<section id="stats" class="py-16 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header - Right Aligned -->
    <div class="text-right mb-10">
      <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? 'إنجازاتنا بالأرقام' }}</h2>
      @if(isset($subheading))
        <p class="text-base text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
      <!-- Stat 1 -->
      <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
        <div class="w-12 h-12 rounded-lg bg-blue-500/10 text-blue-600 flex items-center justify-center mx-auto mb-3">
          <i class="ri-user-line text-2xl"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1">5000+</div>
        <div class="text-sm text-gray-600">طالب نشط</div>
      </div>

      <!-- Stat 2 -->
      <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
        <div class="w-12 h-12 rounded-lg bg-green-500/10 text-green-600 flex items-center justify-center mx-auto mb-3">
          <i class="ri-team-line text-2xl"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1">200+</div>
        <div class="text-sm text-gray-600">معلم متخصص</div>
      </div>

      <!-- Stat 3 -->
      <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
        <div class="w-12 h-12 rounded-lg bg-amber-500/10 text-amber-600 flex items-center justify-center mx-auto mb-3">
          <i class="ri-book-open-line text-2xl"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1">150+</div>
        <div class="text-sm text-gray-600">كورس متاح</div>
      </div>

      <!-- Stat 4 -->
      <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
        <div class="w-12 h-12 rounded-lg bg-purple-500/10 text-purple-600 flex items-center justify-center mx-auto mb-3">
          <i class="ri-trophy-line text-2xl"></i>
        </div>
        <div class="text-3xl font-bold text-gray-900 mb-1">98%</div>
        <div class="text-sm text-gray-600">نسبة الرضا</div>
      </div>
    </div>
  </div>
</section>
