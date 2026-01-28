@props(['package', 'packageType' => 'academic', 'selectedPeriod' => 'monthly'])

@php
    // Get currency from package
    $currency = $package->currency ?? 'SAR';

    // Get price based on selected period
    $displayPrice = match($selectedPeriod) {
        'quarterly' => $package->quarterly_price ?? $package->monthly_price,
        'yearly' => $package->yearly_price ?? $package->monthly_price,
        default => $package->monthly_price,
    };
    $periodLabel = match($selectedPeriod) {
        'quarterly' => __('public.booking.quran.package_info.per_quarter'),
        'yearly' => __('public.booking.quran.package_info.per_year'),
        default => __('public.booking.quran.package_info.per_month'),
    };
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
  <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
    <i class="ri-package-line text-primary"></i>
    {{ __('public.booking.quran.package_info.title') }}
  </h3>
  <div class="space-y-3">
    <div class="flex justify-between items-center">
      <span class="font-medium text-gray-900">
        @if($packageType === 'quran')
          {{ $package->getDisplayName() }}
        @else
          {{ $package->name }}
        @endif
      </span>
      <div class="text-end" dir="ltr">
        <span class="text-xl font-bold text-primary">{{ number_format($displayPrice) }} {{ $currency }}</span>
        <span class="text-sm text-gray-600">{{ $periodLabel }}</span>
      </div>
    </div>

    <div class="text-sm text-gray-600">
      {{ $package->description }}
    </div>

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
      @if($packageType === 'academic' && ($package->package_type ?? '') === 'individual')
        <div class="flex items-center gap-2 text-sm">
          <i class="ri-user-line text-orange-500"></i>
          <span>{{ __('public.booking.quran.package_info.individual_sessions') }}</span>
        </div>
      @endif
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
