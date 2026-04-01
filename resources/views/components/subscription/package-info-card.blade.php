@props(['package', 'packageType' => 'academic', 'selectedPeriod' => 'monthly', 'compact' => false, 'academy' => null])

@php
    // Use passed academy to avoid lazy-loading $package->academy
    $packageAcademy = $academy ?? $package->academy;
    $currency = getCurrencySymbol(null, $packageAcademy);

    $originalPrice = match($selectedPeriod) {
        'quarterly' => $package->quarterly_price ?? $package->monthly_price,
        'yearly' => $package->yearly_price ?? $package->monthly_price,
        default => $package->monthly_price,
    };

    $salePrice = $package->getSalePriceForBillingCycle($selectedPeriod);
    $displayPrice = $salePrice ?? $originalPrice;
    $hasSale = $salePrice !== null;

    $periodLabel = match($selectedPeriod) {
        'quarterly' => __('public.booking.quran.package_info.per_quarter'),
        'yearly' => __('public.booking.quran.package_info.per_year'),
        default => __('public.booking.quran.package_info.per_month'),
    };

    $packageName = $packageType === 'quran' ? $package->getDisplayName() : $package->name;
@endphp

@if($compact)
{{-- Compact collapsible mode --}}
<div x-data="{ expanded: false }">
  <button type="button" @click="expanded = !expanded" class="w-full flex items-center justify-between group">
    <div class="flex items-center gap-3">
      <div class="w-12 h-12 flex items-center justify-center rounded-lg bg-indigo-100 flex-shrink-0">
        <i class="ri-price-tag-3-line text-xl text-indigo-600"></i>
      </div>
      <div class="text-start">
        <div class="font-semibold text-gray-900 text-sm">{{ $packageName }}</div>
        <div class="text-xs text-gray-500">
          {{ $package->sessions_per_month }} {{ __('public.booking.quran.package_info.sessions_monthly') }}
          &middot; {{ $package->session_duration_minutes }} {{ __('public.booking.quran.package_info.minutes_per_session') }}
        </div>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <div class="text-end" dir="ltr">
        <span class="text-sm font-bold text-gray-900">{{ number_format($displayPrice) }} {{ $currency }}</span>
        <div class="text-[10px] text-gray-500">{{ $periodLabel }}</div>
      </div>
      <i class="ri-arrow-down-s-line text-gray-400 transition-transform duration-200"
         :class="expanded && 'rotate-180'"></i>
    </div>
  </button>

  <div x-show="expanded" x-collapse x-cloak class="mt-3 ps-[3.75rem]">
    @if($hasSale)
      <div class="mb-2">
        <span class="text-[11px] text-gray-400 line-through">{{ number_format($originalPrice) }} {{ $currency }}</span>
      </div>
    @endif

    @if($package->description)
      <p class="text-xs text-gray-600 mb-2">{{ $package->description }}</p>
    @endif

    <div class="space-y-1.5">
      <div class="flex items-center gap-2 text-xs text-gray-600">
        <i class="ri-video-line text-purple-500"></i>
        <span>{{ __('public.booking.quran.package_info.live_sessions') }}</span>
      </div>
      @if($packageType === 'quran' && $package->features && count($package->features) > 0)
        @foreach($package->features as $feature)
          <div class="flex items-center gap-2 text-xs text-gray-600">
            <i class="ri-check-line text-green-500"></i>
            <span>{{ $feature }}</span>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>

@else
{{-- Full card mode --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
  <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
    <i class="ri-package-line text-primary"></i>
    {{ __('public.booking.quran.package_info.title') }}
  </h3>
  <div class="space-y-3">
    <div class="flex justify-between items-center">
      <span class="font-medium text-gray-900">{{ $packageName }}</span>
      <div class="text-end" dir="ltr">
        <span class="text-xl font-bold text-primary">{{ number_format($displayPrice) }} {{ $currency }}</span>
        @if($hasSale)
          <div>
            <span class="text-[11px] text-gray-400">{{ __('components.packages.instead_of', ['price' => number_format($originalPrice) . ' ' . $currency]) }}</span>
          </div>
        @endif
        <span class="text-sm text-gray-600">{{ $periodLabel }}</span>
      </div>
    </div>

    @if($package->description)
      <div class="text-sm text-gray-600">
        {{ $package->description }}
      </div>
    @endif

    <div class="space-y-2 pt-2 border-t border-gray-200">
      <div class="flex items-center gap-2 text-sm">
        <i class="ri-check-line text-green-500"></i>
        <span>{{ $package->sessions_per_month }} {{ __('public.booking.quran.package_info.sessions_monthly') }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <i class="ri-time-line text-blue-500"></i>
        <span>{{ $package->session_duration_minutes }} {{ __('public.booking.quran.package_info.minutes_per_session') }}</span>
      </div>
      <div class="flex items-center gap-2 text-sm">
        <i class="ri-video-line text-purple-500"></i>
        <span>{{ __('public.booking.quran.package_info.live_sessions') }}</span>
      </div>
      @if($packageType === 'quran' && $package->features && count($package->features) > 0)
        @foreach($package->features as $feature)
          <div class="flex items-center gap-2 text-sm">
            <i class="ri-check-line text-green-500"></i>
            <span>{{ $feature }}</span>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>
@endif
