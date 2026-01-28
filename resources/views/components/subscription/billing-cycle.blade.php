@props(['package', 'selectedCycle' => 'monthly'])

@php
    // Get prices
    $monthlyPrice = $package->monthly_price ?? 0;
    $quarterlyPrice = $package->quarterly_price ?? 0;
    $yearlyPrice = $package->yearly_price ?? 0;
    $currency = $package->currency ?? getCurrencyCode(null, $package->academy);

    // Calculate actual savings percentages
    $quarterlyFullPrice = $monthlyPrice * 3;
    $yearlyFullPrice = $monthlyPrice * 12;
    $quarterlySavings = $quarterlyFullPrice > 0 ? round((($quarterlyFullPrice - $quarterlyPrice) / $quarterlyFullPrice) * 100) : 0;
    $yearlySavings = $yearlyFullPrice > 0 ? round((($yearlyFullPrice - $yearlyPrice) / $yearlyFullPrice) * 100) : 0;
@endphp

<div>
  <label class="block text-sm font-medium text-gray-700 mb-3">
    <i class="ri-money-dollar-circle-line text-primary ms-2"></i>
    {{ __('public.booking.quran.form.billing_cycle') }} *
  </label>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Monthly -->
    <label class="relative h-full">
      <input type="radio" name="billing_cycle" value="monthly"
             {{ old('billing_cycle', $selectedCycle) == 'monthly' ? 'checked' : '' }}
             class="sr-only peer">
      <div class="h-full p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary card-hover">
        <div class="h-full flex flex-col items-center justify-between text-center">
          <div class="text-base font-semibold text-gray-900">{{ __('public.booking.quran.form.monthly') }}</div>
          <div class="my-2 flex items-baseline gap-1" dir="ltr">
            <span class="text-xl font-bold text-primary">{{ number_format($monthlyPrice) }} {{ $currency }}</span>
            <span class="text-sm text-gray-500">{{ __('public.booking.quran.package_info.per_month') }}</span>
          </div>
          <div class="h-5 mt-2"></div>
        </div>
      </div>
    </label>

    <!-- Quarterly -->
    @if($quarterlyPrice)
      <label class="relative h-full">
        <input type="radio" name="billing_cycle" value="quarterly"
               {{ old('billing_cycle') == 'quarterly' ? 'checked' : '' }}
               class="sr-only peer">
        <div class="h-full p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary card-hover">
          <div class="h-full flex flex-col items-center justify-between text-center">
            <div class="text-base font-semibold text-gray-900">{{ __('public.booking.quran.form.quarterly') }}</div>
            <div class="my-2 flex items-baseline gap-1" dir="ltr">
              <span class="text-xl font-bold text-primary">{{ number_format($quarterlyPrice) }} {{ $currency }}</span>
              <span class="text-sm text-gray-500">{{ __('public.booking.quran.package_info.per_quarter') }}</span>
            </div>
            <div class="h-5 mt-2">
              @if($quarterlySavings > 0)
                <span class="text-xs text-green-600 font-medium">{{ __('public.booking.quran.package_info.save_percent', ['percent' => $quarterlySavings]) }}</span>
              @endif
            </div>
          </div>
        </div>
      </label>
    @endif

    <!-- Yearly -->
    @if($yearlyPrice)
      <label class="relative h-full">
        <input type="radio" name="billing_cycle" value="yearly"
               {{ old('billing_cycle') == 'yearly' ? 'checked' : '' }}
               class="sr-only peer">
        <div class="h-full p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 peer-checked:ring-2 peer-checked:ring-primary card-hover">
          <div class="h-full flex flex-col items-center justify-between text-center">
            <div class="text-base font-semibold text-gray-900">{{ __('public.booking.quran.form.yearly') }}</div>
            <div class="my-2 flex items-baseline gap-1" dir="ltr">
              <span class="text-xl font-bold text-primary">{{ number_format($yearlyPrice) }} {{ $currency }}</span>
              <span class="text-sm text-gray-500">{{ __('public.booking.quran.package_info.per_year') }}</span>
            </div>
            <div class="h-5 mt-2">
              @if($yearlySavings > 0)
                <span class="text-xs text-green-600 font-medium">{{ __('public.booking.quran.package_info.save_percent', ['percent' => $yearlySavings]) }}</span>
              @endif
            </div>
          </div>
        </div>
      </label>
    @endif
  </div>
</div>
