@props(['package', 'packageType' => 'academic'])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
  <h3 class="text-lg font-bold text-gray-900 mb-4">
    <i class="ri-package-line text-primary ml-2"></i>
    تفاصيل الباقة
  </h3>
  <div class="space-y-3">
    <div class="flex justify-between items-center">
      <span class="font-medium">
        @if($packageType === 'quran')
          {{ $package->getDisplayName() }}
        @else
          {{ $package->name_ar ?? $package->name_en }}
        @endif
      </span>
      <span class="text-lg font-bold text-primary">
        {{ number_format($package->monthly_price) }} {{ $package->currency ?? $package->getDisplayCurrency() }}
      </span>
    </div>
    
    <div class="text-sm text-gray-600">
      @if($packageType === 'quran')
        {{ $package->getDescription() }}
      @else
        {{ $package->description_ar ?? $package->description_en }}
      @endif
    </div>
    
    <div class="space-y-2 pt-2 border-t border-gray-200">
      <div class="flex items-center text-sm">
        <i class="ri-check-line text-green-500 ml-2"></i>
        <span>{{ $package->sessions_per_month }} جلسة شهرياً</span>
      </div>
      <div class="flex items-center text-sm">
        <i class="ri-time-line text-blue-500 ml-2"></i>
        <span>{{ $package->session_duration_minutes }} دقيقة لكل جلسة</span>
      </div>
      <div class="flex items-center text-sm">
        <i class="ri-video-line text-purple-500 ml-2"></i>
        <span>جلسات مباشرة عبر الإنترنت</span>
      </div>
      @if($packageType === 'academic' && ($package->package_type ?? '') === 'individual')
        <div class="flex items-center text-sm">
          <i class="ri-user-line text-orange-500 ml-2"></i>
          <span>جلسات فردية (1:1)</span>
        </div>
      @endif
      @if($packageType === 'quran' && $package->features && count($package->features) > 0)
        @foreach($package->features as $feature)
          <div class="flex items-center text-sm">
            <i class="ri-check-line text-green-500 ml-2"></i>
            <span>{{ $feature }}</span>
          </div>
        @endforeach
      @endif
    </div>
  </div>
</div>
