<x-subscription.page-layout
  :academy="$academy"
  title="اشتراك {{ $package->name_ar ?? $package->name_en }} - {{ $teacher->user->name }}">

  <x-booking.top-bar
    :academy="$academy"
    title="اشتراك أكاديمي جديد"
    :backRoute="route('public.academic-packages.teacher', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id])" />


  <!-- Main Content -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      
      <!-- Teacher & Package Info -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Teacher Info -->
        <x-subscription.teacher-info-card :teacher="$teacher" teacherType="academic" />

        <!-- Package Info -->
        <x-subscription.package-info-card :package="$package" packageType="academic" />
      </div>

      <!-- Subscription Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
          <h3 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="ri-graduation-cap-line text-primary ml-2"></i>
            تفاصيل الاشتراك الأكاديمي
          </h3>
          <p class="text-gray-600">املأ البيانات أدناه لإتمام عملية الاشتراك</p>
        </div>

        <form action="{{ route('public.academic-packages.subscribe.submit', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id, 'packageId' => $package->id]) }}" method="POST" class="space-y-6">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
          <input type="hidden" name="package_id" value="{{ $package->id }}">
          <input type="hidden" name="academy_id" value="{{ $academy->id }}">

          <x-subscription.messages />

          <!-- Student Info Display -->
          <x-subscription.student-info :user="auth()->user()" />

          <!-- Billing Cycle -->
          <x-subscription.billing-cycle :package="$package" selectedCycle="monthly" />

          <!-- Academic Specific Fields -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Subject Selection -->
            <div class="md:col-span-1">
              <label for="subject_id" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="ri-book-line text-blue-500 ml-1"></i>
                المادة الدراسية *
              </label>
              <select id="subject_id" name="subject_id" required
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary @error('subject_id') border-red-500 @enderror">
                <option value="">اختر المادة الدراسية</option>
                @php
                  $subjects = $teacher->subjects_text ?? [];
                  if (is_string($subjects)) {
                    $subjects = json_decode($subjects, true) ?: [];
                  }
                  if (!is_array($subjects)) {
                    $subjects = [];
                  }
                @endphp
                @if(count($subjects) > 0)
                  @foreach($subjects as $subjectId => $subjectName)
                    <option value="{{ $subjectId }}" {{ old('subject_id') == $subjectId ? 'selected' : '' }}>
                      {{ $subjectName }}
                    </option>
                  @endforeach
                @endif
              </select>
              @error('subject_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>

            <!-- Grade Level Selection -->
            <div class="md:col-span-1">
              <label for="grade_level_id" class="block text-sm font-medium text-gray-700 mb-2">
                <i class="ri-school-line text-green-500 ml-1"></i>
                المرحلة الدراسية *
              </label>
              <select id="grade_level_id" name="grade_level_id" required
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary @error('grade_level_id') border-red-500 @enderror">
                <option value="">اختر المرحلة الدراسية</option>
                @php
                  $gradeLevels = $teacher->grade_levels_text ?? [];
                  if (is_string($gradeLevels)) {
                    $gradeLevels = json_decode($gradeLevels, true) ?: [];
                  }
                  if (!is_array($gradeLevels)) {
                    $gradeLevels = [];
                  }
                @endphp
                @if(count($gradeLevels) > 0)
                  @foreach($gradeLevels as $gradeLevelId => $gradeLevelName)
                    <option value="{{ $gradeLevelId }}" {{ old('grade_level_id') == $gradeLevelId ? 'selected' : '' }}>
                      {{ $gradeLevelName }}
                    </option>
                  @endforeach
                @endif
              </select>
              @error('grade_level_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
              @enderror
            </div>


          </div>

          <!-- Preferred Schedule -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">الجدول الزمني المفضل</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
              @php
                $daysInArabic = [
                  'saturday' => 'السبت',
                  'sunday' => 'الأحد',
                  'monday' => 'الاثنين',
                  'tuesday' => 'الثلاثاء', 
                  'wednesday' => 'الأربعاء',
                  'thursday' => 'الخميس',
                  'friday' => 'الجمعة'
                ];
              @endphp
              @foreach($daysInArabic as $day => $arabicName)
                <label class="flex items-center">
                  <input type="checkbox" name="preferred_days[]" value="{{ $day }}" 
                         {{ in_array($day, old('preferred_days', [])) ? 'checked' : '' }}
                         class="text-primary focus:ring-primary border-gray-300 rounded">
                  <span class="mr-2 text-sm">{{ $arabicName }}</span>
                </label>
              @endforeach
            </div>
            
            <select name="preferred_time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">اختر الوقت المفضل</option>
              <option value="morning" {{ old('preferred_time') == 'morning' ? 'selected' : '' }}>صباحاً (6:00 - 12:00)</option>
              <option value="afternoon" {{ old('preferred_time') == 'afternoon' ? 'selected' : '' }}>بعد الظهر (12:00 - 18:00)</option>
              <option value="evening" {{ old('preferred_time') == 'evening' ? 'selected' : '' }}>مساءً (18:00 - 22:00)</option>
            </select>
          </div>

          <!-- Additional Notes -->
          <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">ملاحظات إضافية</label>
            <textarea id="notes" name="notes" rows="4"
                      placeholder="أي معلومات إضافية تود مشاركتها مع المعلم..."
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">{{ old('notes') }}</textarea>
          </div>

          <!-- Pricing Summary -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h4 class="font-semibold text-gray-900 mb-4">
              <i class="ri-calculator-line text-primary ml-2"></i>
              ملخص التكلفة
            </h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span>سعر الباقة (شهرياً)</span>
                <span id="package-price">{{ number_format($package->monthly_price) }} {{ $package->currency }}</span>
              </div>
              <div class="flex justify-between">
                <span>رسوم الخدمة</span>
                <span>0 {{ $package->currency }}</span>
              </div>
              <div class="border-t border-gray-300 pt-2 flex justify-between font-bold text-lg">
                <span>المجموع</span>
                <span class="text-primary" id="total-amount">{{ number_format($package->monthly_price) }} {{ $package->currency }}</span>
              </div>
            </div>
          </div>

          <!-- Payment Terms -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-semibold text-blue-900 mb-2">شروط الدفع والاشتراك:</h4>
            <ul class="text-sm text-blue-800 space-y-1">
              <li>• سيتم تحصيل الرسوم في بداية كل دورة فوترة</li>
              <li>• يمكنك إلغاء الاشتراك في أي وقت قبل التجديد التلقائي</li>
              <li>• سيقوم المعلم بالتواصل معك خلال 24 ساعة لتحديد مواعيد الجلسات</li>
              <li>• يمكن إعادة جدولة الجلسات بتنسيق مسبق مع المعلم</li>
            </ul>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-4">
            <button type="submit" 
                    class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-secondary transition-colors">
              <i class="ri-secure-payment-line ml-2"></i>
              المتابعة للدفع
            </button>
            
            <a href="{{ route('public.academic-packages.teacher', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id]) }}" 
               class="flex items-center justify-center gap-2 px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              <i class="ri-arrow-left-line"></i>
              <span>إلغاء</span>
            </a>
          </div>
        </form>
      </div>

    </div>
  </section>

  <script>
    // Enhanced form validation and pricing update
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      const packagePrices = {
        monthly: {{ $package->monthly_price }},
        quarterly: {{ $package->quarterly_price ?? 0 }},
        yearly: {{ $package->yearly_price ?? 0 }}
      };

      // Update pricing when billing cycle changes
      function updatePricing() {
        const billingCycleInputs = document.querySelectorAll('input[name="billing_cycle"]');
        const packagePrice = document.getElementById('package-price');
        const totalAmount = document.getElementById('total-amount');
        
        let selectedCycle = null;
        billingCycleInputs.forEach(input => {
          if (input.checked) {
            selectedCycle = input.value;
          }
        });

        if (selectedCycle && packagePrices[selectedCycle]) {
          const price = packagePrices[selectedCycle];
          const formattedPrice = `${price.toLocaleString()} {{ $package->currency }}`;
          if (packagePrice) packagePrice.textContent = formattedPrice;
          if (totalAmount) totalAmount.textContent = formattedPrice;
        }
      }

      // Add event listeners to billing cycle radio buttons
      const billingCycleInputs = document.querySelectorAll('input[name="billing_cycle"]');
      billingCycleInputs.forEach(input => {
        input.addEventListener('change', updatePricing);
      });

      // Initialize pricing
      updatePricing();

      if (form) {
        // Function to show error message
        function showError(message) {
          const existingError = document.querySelector('.validation-error');
          if (existingError) {
            existingError.remove();
          }
          
          const errorDiv = document.createElement('div');
          errorDiv.className = 'validation-error bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6';
          errorDiv.innerHTML = `
            <div class="flex">
              <i class="ri-error-warning-line text-red-500 mt-0.5 ml-2"></i>
              <div>
                <h4 class="font-medium mb-1">خطأ في النموذج:</h4>
                <p class="text-sm">${message}</p>
              </div>
            </div>
          `;
          
          form.insertBefore(errorDiv, form.firstChild);
          errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // ENHANCED FORM SUBMISSION WITH ERROR CAPTURE
        form.addEventListener('submit', function(e) {
          
          // Log all form values
          const formData = new FormData(form);
          for (let [key, value] of formData.entries()) {
          }
          
          const existingError = document.querySelector('.validation-error');
          if (existingError) {
            existingError.remove();
          }
        });

        // Capture any form submission errors
        window.addEventListener('error', function(e) {
        });

        // Capture unhandled promise rejections
        window.addEventListener('unhandledrejection', function(e) {
          e.preventDefault();
        });
      }
    });
  </script>

</x-subscription.page-layout>
