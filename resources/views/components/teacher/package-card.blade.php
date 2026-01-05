@props([
    'package',
    'teacher',
    'academy',
    'color' => 'blue', // blue or violet
    'subscribeRoute',
    'isPopular' => false
])

<div class="bg-white rounded-xl md:rounded-2xl shadow-sm border border-gray-200 overflow-visible {{ $isPopular ? 'ring-2 ring-'.$color.'-500' : '' }} flex flex-col relative">

  <!-- Popular Badge - Centered on top border -->
  @if($isPopular)
    <div class="absolute left-1/2 -translate-x-1/2 -translate-y-1/2 z-10">
      <div class="bg-{{ $color }}-600 text-white px-3 md:px-4 py-1 md:py-1.5 rounded-full text-[10px] md:text-xs font-bold overflow-hidden">
        {{ __('components.packages.most_popular') }}
      </div>
    </div>
  @endif

  <!-- Header with decorative pattern and price -->
  <div class="bg-gradient-to-br from-{{ $color }}-50 via-white to-{{ $color }}-50 p-4 md:p-6 border-b border-{{ $color }}-100 rounded-t-xl md:rounded-t-2xl overflow-hidden">
    <!-- Package Name -->
    <div class="text-center mb-3 md:mb-4">
      <h3 class="text-base md:text-lg lg:text-xl font-bold text-gray-900 mb-0.5 md:mb-1">
        {{ $package->name }}
      </h3>
      @if($package->description)
        <p class="text-xs md:text-sm text-gray-600">{{ $package->description }}</p>
      @endif
    </div>

    <!-- Price Display -->
    <div class="text-center">
      <!-- Price Label -->
      <p class="text-gray-600 text-xs md:text-sm mb-1.5 md:mb-2 font-medium">
        <span x-show="pricingPeriod === 'monthly'">{{ __('components.packages.monthly_price') }}</span>
        <span x-show="pricingPeriod === 'quarterly'" x-cloak>{{ __('components.packages.quarterly_price') }}</span>
        <span x-show="pricingPeriod === 'yearly'" x-cloak>{{ __('components.packages.yearly_price') }}</span>
      </p>

      <!-- Price Amount -->
      <div class="flex items-baseline justify-center gap-1.5 md:gap-2 mb-0.5 md:mb-1">
        <span x-show="pricingPeriod === 'monthly'" class="text-3xl md:text-4xl lg:text-5xl font-black text-{{ $color }}-600">{{ number_format($package->monthly_price) }}</span>
        <span x-show="pricingPeriod === 'quarterly'" x-cloak class="text-3xl md:text-4xl lg:text-5xl font-black text-{{ $color }}-600">{{ number_format($package->quarterly_price) }}</span>
        <span x-show="pricingPeriod === 'yearly'" x-cloak class="text-3xl md:text-4xl lg:text-5xl font-black text-{{ $color }}-600">{{ number_format($package->yearly_price) }}</span>
        <span class="text-base md:text-lg lg:text-xl font-bold text-{{ $color }}-500">{{ __('components.packages.currency') }}</span>
      </div>

      <!-- Renewal Text -->
      <p class="text-[10px] md:text-xs text-gray-500 mt-1.5 md:mt-2">
        <span x-show="pricingPeriod === 'monthly'">{{ __('components.packages.renews_monthly') }}</span>
        <span x-show="pricingPeriod === 'quarterly'" x-cloak>{{ __('components.packages.renews_quarterly') }}</span>
        <span x-show="pricingPeriod === 'yearly'" x-cloak>{{ __('components.packages.renews_yearly') }}</span>
      </p>
    </div>
  </div>

  <!-- Content -->
  <div class="p-4 md:p-6 flex flex-col flex-grow">
    <!-- Features -->
    <div class="space-y-1 mb-4 md:mb-6 flex-grow">
      <!-- Sessions and Duration - Side by Side -->
      <div class="grid grid-cols-2 gap-1.5 md:gap-2">
        <!-- Sessions -->
        <div class="flex items-center gap-1.5 md:gap-2 p-2 md:p-3 bg-gray-50 rounded-lg md:rounded-xl">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-{{ $color }}-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="ri-video-chat-line text-sm md:text-base text-{{ $color }}-600"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-[10px] md:text-xs text-gray-500 mb-0.5 truncate">{{ __('components.packages.sessions_count') }}</p>
            <p class="font-bold text-gray-900 text-xs md:text-sm truncate">{{ $package->sessions_per_month }} {{ __('components.packages.sessions_unit') }}</p>
          </div>
        </div>

        <!-- Duration -->
        <div class="flex items-center gap-1.5 md:gap-2 p-2 md:p-3 bg-gray-50 rounded-lg md:rounded-xl">
          <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <i class="ri-time-line text-sm md:text-base text-green-600"></i>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-[10px] md:text-xs text-gray-500 mb-0.5 truncate">{{ __('components.packages.session_duration') }}</p>
            <p class="font-bold text-gray-900 text-xs md:text-sm truncate">{{ $package->session_duration_minutes }} {{ __('components.packages.minutes') }}</p>
          </div>
        </div>
      </div>

      @if($package->features && count($package->features) > 0)
        @foreach($package->features as $feature)
          <div class="flex items-start gap-2 md:gap-3 p-1.5 md:p-2">
            <i class="ri-check-line text-green-500 text-base md:text-lg mt-0.5 flex-shrink-0 font-bold"></i>
            <span class="text-gray-700 text-xs md:text-sm">{{ $feature }}</span>
          </div>
        @endforeach
      @endif
    </div>

    <!-- CTA Button and Security Badge - Stuck to bottom -->
    <div class="mt-auto">
      @auth
        @if(auth()->user()->user_type === 'student')
          <a :href="`{{ $subscribeRoute }}?period=${pricingPeriod}`"
             class="min-h-[44px] group block w-full bg-gradient-to-r from-{{ $color }}-500 to-{{ $color }}-600 hover:from-{{ $color }}-600 hover:to-{{ $color }}-700 text-white px-4 md:px-6 py-3 md:py-4 rounded-lg md:rounded-xl font-bold text-sm md:text-base lg:text-lg transition-all shadow-lg hover:shadow-xl text-center transform hover:-translate-y-1 relative overflow-hidden">
            <span class="relative z-10 flex items-center justify-center gap-1.5 md:gap-2">
              <i class="ri-shopping-cart-line text-base md:text-lg lg:text-xl"></i>
              <span>{{ __('components.packages.subscribe_now') }}</span>
            </span>
            <!-- Shimmer effect -->
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
          </a>
        @else
          <div class="min-h-[44px] w-full bg-gray-100 text-gray-400 text-center font-bold py-3 md:py-4 rounded-lg md:rounded-xl cursor-not-allowed text-sm md:text-base flex items-center justify-center">
            <i class="ri-user-line ms-1.5 md:ms-2"></i>
            {{ __('components.packages.students_only') }}
          </div>
        @endif
      @else
        <a :href="`{{ route('login', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}?redirect={{ urlencode($subscribeRoute . '?period=') }}${pricingPeriod}`"
           class="min-h-[44px] group block w-full bg-gradient-to-r from-{{ $color }}-500 to-{{ $color }}-600 hover:from-{{ $color }}-600 hover:to-{{ $color }}-700 text-white px-4 md:px-6 py-3 md:py-4 rounded-lg md:rounded-xl font-bold text-sm md:text-base lg:text-lg transition-all shadow-lg hover:shadow-xl text-center transform hover:-translate-y-1 relative overflow-hidden">
          <span class="relative z-10 flex items-center justify-center gap-1.5 md:gap-2">
            <i class="ri-login-box-line text-base md:text-lg lg:text-xl"></i>
            <span>{{ __('components.packages.login_to_subscribe') }}</span>
          </span>
          <!-- Shimmer effect -->
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
        </a>
      @endauth

      <!-- Security Badge -->
      <div class="flex items-center justify-center gap-1.5 md:gap-2 mt-3 md:mt-4 pt-3 md:pt-4 border-t border-gray-100">
        <i class="ri-shield-check-line text-green-600 text-sm md:text-base"></i>
        <span class="text-[10px] md:text-xs text-gray-600">{{ __('components.packages.secure_payment') }}</span>
      </div>
    </div>
  </div>
</div>
