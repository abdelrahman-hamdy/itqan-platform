<!-- Error Messages -->
@if ($errors->any())
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
    <div class="flex">
      <i class="ri-error-warning-line text-red-500 mt-0.5 ml-2"></i>
      <div>
        <h4 class="font-medium mb-1">يرجى تصحيح الأخطاء التالية:</h4>
        <ul class="text-sm space-y-1">
          @foreach ($errors->all() as $error)
            <li>• {{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  </div>
@endif

<!-- Success Messages -->
@if (session('success'))
  <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
    <div class="flex">
      <i class="ri-check-line text-green-500 mt-0.5 ml-2"></i>
      <div>{{ session('success') }}</div>
    </div>
  </div>
@endif

<!-- Error Messages -->
@if (session('error'))
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
    <div class="flex">
      <i class="ri-error-warning-line text-red-500 mt-0.5 ml-2"></i>
      <div>{{ session('error') }}</div>
    </div>
  </div>
@endif
