@props(['package', 'selectedCycle' => 'monthly'])

<div>
  <label class="block text-sm font-medium text-gray-700 mb-3">
    <i class="ri-money-dollar-circle-line text-primary ml-2"></i>
    دورة الفوترة *
  </label>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Monthly -->
    <label class="relative">
      <input type="radio" name="billing_cycle" value="monthly" 
             {{ old('billing_cycle', $selectedCycle) == 'monthly' ? 'checked' : '' }}
             class="sr-only peer">
      <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
        <div class="text-center">
          <div class="text-lg font-bold text-gray-900">شهرياً</div>
          <div class="text-sm text-gray-600">{{ number_format($package->monthly_price) }} {{ $package->currency ?? $package->getDisplayCurrency() }}/شهر</div>
        </div>
      </div>
    </label>
    
    <!-- Quarterly -->
    @if($package->quarterly_price)
      <label class="relative">
        <input type="radio" name="billing_cycle" value="quarterly"
               {{ old('billing_cycle') == 'quarterly' ? 'checked' : '' }}
               class="sr-only peer">
        <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
          <div class="text-center">
            <div class="text-lg font-bold text-gray-900">ربع سنوي</div>
            <div class="text-sm text-gray-600">{{ number_format($package->quarterly_price) }} {{ $package->currency ?? $package->getDisplayCurrency() }}/3 أشهر</div>
            <div class="text-xs text-green-600 font-medium">وفر 10%</div>
          </div>
        </div>
      </label>
    @endif
    
    <!-- Yearly -->
    @if($package->yearly_price)
      <label class="relative">
        <input type="radio" name="billing_cycle" value="yearly"
               {{ old('billing_cycle') == 'yearly' ? 'checked' : '' }}
               class="sr-only peer">
        <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5 card-hover">
          <div class="text-center">
            <div class="text-lg font-bold text-gray-900">سنوياً</div>
            <div class="text-sm text-gray-600">{{ number_format($package->yearly_price) }} {{ $package->currency ?? $package->getDisplayCurrency() }}/سنة</div>
            <div class="text-xs text-green-600 font-medium">وفر 20%</div>
          </div>
        </div>
      </label>
    @endif
  </div>
</div>
