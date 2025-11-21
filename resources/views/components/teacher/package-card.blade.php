@props([
    'package',
    'teacher',
    'academy',
    'color' => 'blue', // blue or violet
    'subscribeRoute',
    'isPopular' => false
])

<div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-visible {{ $isPopular ? 'ring-2 ring-'.$color.'-500' : '' }} flex flex-col relative">

  <!-- Popular Badge - Centered on top border -->
  @if($isPopular)
    <div class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
      <div class="bg-{{ $color }}-600 text-white px-4 py-1.5 rounded-full text-xs font-bold overflow-hidden">
        الأكثر اختياراً
      </div>
    </div>
  @endif

  <!-- Header with decorative pattern and price -->
  <div class="bg-gradient-to-br from-{{ $color }}-50 via-white to-{{ $color }}-50 p-6 border-b border-{{ $color }}-100 rounded-t-2xl overflow-hidden">
    <!-- Package Name -->
    <div class="text-center mb-4">
      <h3 class="text-xl font-bold text-gray-900 mb-1">
        {{ $package->name_ar ?? $package->getDisplayName() }}
      </h3>
      @if($package->description_ar ?? $package->getDescription())
        <p class="text-sm text-gray-600">{{ $package->description_ar ?? $package->getDescription() }}</p>
      @endif
    </div>

    <!-- Price Display -->
    <div class="text-center">
      <!-- Price Label -->
      <p class="text-gray-600 text-sm mb-2 font-medium">
        <span x-show="pricingPeriod === 'monthly'">السعر الشهري</span>
        <span x-show="pricingPeriod === 'quarterly'" x-cloak>السعر الربع سنوي</span>
        <span x-show="pricingPeriod === 'yearly'" x-cloak>السعر السنوي</span>
      </p>

      <!-- Price Amount -->
      <div class="flex items-baseline justify-center gap-2 mb-1">
        <span x-show="pricingPeriod === 'monthly'" class="text-5xl font-black text-{{ $color }}-600">{{ number_format($package->monthly_price) }}</span>
        <span x-show="pricingPeriod === 'quarterly'" x-cloak class="text-5xl font-black text-{{ $color }}-600">{{ number_format($package->quarterly_price) }}</span>
        <span x-show="pricingPeriod === 'yearly'" x-cloak class="text-5xl font-black text-{{ $color }}-600">{{ number_format($package->yearly_price) }}</span>
        <span class="text-xl font-bold text-{{ $color }}-500">ر.س</span>
      </div>

      <!-- Renewal Text -->
      <p class="text-xs text-gray-500 mt-2">
        <span x-show="pricingPeriod === 'monthly'">يتجدد تلقائياً كل شهر</span>
        <span x-show="pricingPeriod === 'quarterly'" x-cloak>يتجدد تلقائياً كل 3 أشهر</span>
        <span x-show="pricingPeriod === 'yearly'" x-cloak>يتجدد تلقائياً كل سنة</span>
      </p>
    </div>
  </div>

  <!-- Content -->
  <div class="p-6 flex flex-col flex-grow">
    <!-- Features -->
    <div class="space-y-1 mb-6 flex-grow">
      <!-- Sessions and Duration - Side by Side -->
      <div class="grid grid-cols-2 gap-2">
        <!-- Sessions -->
        <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl">
          <div class="w-10 h-10 bg-{{ $color }}-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="ri-video-chat-line text-{{ $color }}-600"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-500 mb-0.5">عدد الجلسات</p>
            <p class="font-bold text-gray-900 text-sm">{{ $package->sessions_per_month }} جلسات</p>
          </div>
        </div>

        <!-- Duration -->
        <div class="flex items-center gap-2 p-3 bg-gray-50 rounded-xl">
          <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="ri-time-line text-green-600"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-gray-500 mb-0.5">مدة الجلسة</p>
            <p class="font-bold text-gray-900 text-sm">{{ $package->session_duration_minutes }} دقيقة</p>
          </div>
        </div>
      </div>

      @if($package->features && count($package->features) > 0)
        @foreach($package->features as $feature)
          <div class="flex items-start gap-3 p-2">
            <i class="ri-check-line text-green-500 text-lg mt-0.5 flex-shrink-0 font-bold"></i>
            <span class="text-gray-700 text-sm">{{ $feature }}</span>
          </div>
        @endforeach
      @endif
    </div>

    <!-- CTA Button and Security Badge - Stuck to bottom -->
    <div class="mt-auto">
      @auth
        @if(auth()->user()->user_type === 'student')
          <a :href="`{{ $subscribeRoute }}&period=${pricingPeriod}`"
             class="group block w-full bg-gradient-to-r from-{{ $color }}-500 to-{{ $color }}-600 hover:from-{{ $color }}-600 hover:to-{{ $color }}-700 text-white px-6 py-4 rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl text-center transform hover:-translate-y-1 relative overflow-hidden">
            <span class="relative z-10 flex items-center justify-center gap-2">
              <i class="ri-shopping-cart-line text-xl"></i>
              <span>اشترك الآن</span>
            </span>
            <!-- Shimmer effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
          </a>
        @else
          <div class="w-full bg-gray-100 text-gray-400 text-center font-bold py-4 rounded-xl cursor-not-allowed">
            <i class="ri-user-line ml-2"></i>
            متاح للطلاب فقط
          </div>
        @endif
      @else
        <a :href="`{{ route('login', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}?redirect={{ urlencode($subscribeRoute) }}&period=${pricingPeriod}`"
           class="group block w-full bg-gradient-to-r from-{{ $color }}-500 to-{{ $color }}-600 hover:from-{{ $color }}-600 hover:to-{{ $color }}-700 text-white px-6 py-4 rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl text-center transform hover:-translate-y-1 relative overflow-hidden">
          <span class="relative z-10 flex items-center justify-center gap-2">
            <i class="ri-login-box-line text-xl"></i>
            <span>سجل دخولك للاشتراك</span>
          </span>
          <!-- Shimmer effect -->
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
        </a>
      @endauth

      <!-- Security Badge -->
      <div class="flex items-center justify-center gap-2 mt-4 pt-4 border-t border-gray-100">
        <i class="ri-shield-check-line text-green-600"></i>
        <span class="text-xs text-gray-600">دفع آمن ومضمون</span>
      </div>
    </div>
  </div>
</div>
