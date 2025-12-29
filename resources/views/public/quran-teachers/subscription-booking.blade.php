<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>اشتراك {{ $package->getDisplayName() }} - {{ $teacher->full_name }} - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
          }
        }
      }
    };
  </script>
</head>

<body class="bg-gray-50 font-sans">

  <!-- Header -->
  <x-booking.top-bar
    :academy="$academy"
    title="اشتراك جديد"
    :backRoute="route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id])" />


  <!-- Main Content -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      
      <!-- Teacher & Package Info -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Teacher Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">معلومات المعلم</h3>
          <div class="flex items-center gap-4">
            <x-avatar
              :user="$teacher"
              size="lg"
              userType="quran_teacher"
              :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'" />
            <div>
              <h4 class="font-bold text-gray-900">{{ $teacher->full_name }}</h4>
              <p class="text-gray-600">معلم القرآن الكريم المعتمد</p>
              <p class="text-sm text-gray-500">{{ $teacher->teacher_code }}</p>
            </div>
          </div>
          
          @if($teacher->rating > 0)
            <div class="flex items-center mt-3">
              <div class="flex text-yellow-400">
                @for($i = 1; $i <= 5; $i++)
                  <i class="ri-star-{{ $i <= $teacher->rating ? 'fill' : 'line' }} text-sm"></i>
                @endfor
              </div>
              <span class="text-sm text-gray-600 mr-2">({{ $teacher->rating }})</span>
            </div>
          @endif
        </div>

        <!-- Package Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">تفاصيل الباقة</h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="font-medium">{{ $package->getDisplayName() }}</span>
              <span class="text-lg font-bold text-blue-600">
                @if($selectedPeriod === 'quarterly')
                  {{ $package->quarterly_price }} {{ $package->getDisplayCurrency() }}
                @elseif($selectedPeriod === 'yearly')
                  {{ $package->yearly_price }} {{ $package->getDisplayCurrency() }}
                @else
                  {{ $package->monthly_price }} {{ $package->getDisplayCurrency() }}
                @endif
              </span>
            </div>
            
            <div class="text-sm text-gray-600">
              {{ $package->getDescription() }}
            </div>
            
            <div class="space-y-2 pt-2 border-t border-gray-200">
              <div class="flex items-center text-sm">
                <i class="ri-check-line text-green-500 ml-2"></i>
                <span>{{ $package->sessions_per_month }} جلسة شهرياً</span>
              </div>
              <div class="flex items-center text-sm">
                <i class="ri-check-line text-green-500 ml-2"></i>
                <span>{{ $package->session_duration_minutes }} دقيقة لكل جلسة</span>
              </div>
              @if($package->features && count($package->features) > 0)
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
      </div>

      <!-- Subscription Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
          <h3 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="ri-vip-crown-line text-blue-600 ml-2"></i>
            اشتراك جديد
          </h3>
          <p class="text-gray-600">املأ البيانات أدناه لإتمام عملية الاشتراك</p>
        </div>

        <form action="{{ route('quran-teachers.subscribe.submit', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id, 'packageId' => $package->id]) }}" method="POST" class="space-y-6"
              x-data="{
                billingCycle: '{{ old('billing_cycle', $selectedPeriod ?? 'monthly') }}',
                prices: {
                  monthly: {{ $package->monthly_price }},
                  quarterly: {{ $package->quarterly_price }},
                  yearly: {{ $package->yearly_price }}
                },
                get currentPrice() {
                  return this.prices[this.billingCycle];
                }
              }">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
          <input type="hidden" name="package_id" value="{{ $package->id }}">
          <input type="hidden" name="academy_id" value="{{ $academy->id }}">

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

          <!-- Student Info Display -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 mb-6">
            <h4 class="font-semibold text-gray-900 mb-3">معلومات الطالب</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-gray-600">الاسم:</span>
                <span class="font-medium">{{ auth()->user()->studentProfile?->full_name ?? auth()->user()->name }}</span>
              </div>
              <div>
                <span class="text-gray-600">البريد الإلكتروني:</span>
                <span class="font-medium">{{ auth()->user()->email }}</span>
              </div>
              @if(auth()->user()->studentProfile?->phone)
              <div>
                <span class="text-gray-600">رقم الهاتف:</span>
                <span class="font-medium">{{ auth()->user()->studentProfile->phone }}</span>
              </div>
              @endif
              @if(auth()->user()->studentProfile?->birth_date)
              <div>
                <span class="text-gray-600">العمر:</span>
                <span class="font-medium">{{ auth()->user()->studentProfile->birth_date->diffInYears(now()) }} سنة</span>
              </div>
              @endif
            </div>
          </div>

          <!-- Billing Cycle -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">دورة الفوترة *</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <label class="relative h-full">
                <input type="radio" name="billing_cycle" value="monthly"
                       x-model="billingCycle"
                       class="sr-only peer">
                <div class="h-full p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 flex items-center justify-center">
                  <div class="text-center">
                    <div class="text-lg font-bold text-gray-900">شهرياً</div>
                    <div class="text-sm text-gray-600">{{ $package->monthly_price }} {{ $package->getDisplayCurrency() }}/شهر</div>
                  </div>
                </div>
              </label>

              @if($package->quarterly_price)
                <label class="relative h-full">
                  <input type="radio" name="billing_cycle" value="quarterly"
                         x-model="billingCycle"
                         class="sr-only peer">
                  <div class="h-full p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 flex items-center justify-center">
                    <div class="text-center">
                      <div class="text-lg font-bold text-gray-900">ربع سنوي</div>
                      <div class="text-sm text-gray-600">{{ $package->quarterly_price }} {{ $package->getDisplayCurrency() }}/3 أشهر</div>
                      <div class="text-xs text-green-600 font-medium mt-1">وفر 10%</div>
                    </div>
                  </div>
                </label>
              @endif

              @if($package->yearly_price)
                <label class="relative h-full">
                  <input type="radio" name="billing_cycle" value="yearly"
                         x-model="billingCycle"
                         class="sr-only peer">
                  <div class="h-full p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-blue-500 peer-checked:bg-blue-50 flex items-center justify-center">
                    <div class="text-center">
                      <div class="text-lg font-bold text-gray-900">سنوياً</div>
                      <div class="text-sm text-gray-600">{{ $package->yearly_price }} {{ $package->getDisplayCurrency() }}/سنة</div>
                      <div class="text-xs text-green-600 font-medium mt-1">وفر 20%</div>
                    </div>
                  </div>
                </label>
              @endif
            </div>
          </div>

          <!-- Current Level -->
          <div>
            <label for="current_level" class="block text-sm font-medium text-gray-700 mb-2">المستوى الحالي في تعلم القرآن *</label>
            <select id="current_level" name="current_level" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
              <option value="">اختر مستواك</option>
              <option value="beginner" {{ old('current_level') == 'beginner' ? 'selected' : '' }}>مبتدئ (لا أعرف القراءة)</option>
              <option value="elementary" {{ old('current_level') == 'elementary' ? 'selected' : '' }}>أساسي (أقرأ ببطء)</option>
              <option value="intermediate" {{ old('current_level') == 'intermediate' ? 'selected' : '' }}>متوسط (أقرأ بطلاقة)</option>
              <option value="advanced" {{ old('current_level') == 'advanced' ? 'selected' : '' }}>متقدم (أحفظ أجزاء من القرآن)</option>
              <option value="expert" {{ old('current_level') == 'expert' ? 'selected' : '' }}>متمكن (أحفظ أكثر من 10 أجزاء)</option>
              <option value="hafiz" {{ old('current_level') == 'hafiz' ? 'selected' : '' }}>حافظ (أحفظ القرآن كاملاً)</option>
            </select>
          </div>

          <!-- Learning Goals -->
          <div>
            <label for="learning_goals" class="block text-sm font-medium text-gray-700 mb-2">أهدافك من تعلم القرآن *</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="reading"
                       {{ in_array('reading', old('learning_goals', [])) ? 'checked' : '' }}
                       class="text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <span class="mr-2">تعلم القراءة الصحيحة</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="tajweed"
                       {{ in_array('tajweed', old('learning_goals', [])) ? 'checked' : '' }}
                       class="text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <span class="mr-2">تعلم أحكام التجويد</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="memorization"
                       {{ in_array('memorization', old('learning_goals', [])) ? 'checked' : '' }}
                       class="text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <span class="mr-2">حفظ القرآن الكريم</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="improvement"
                       {{ in_array('improvement', old('learning_goals', [])) ? 'checked' : '' }}
                       class="text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                <span class="mr-2">تحسين الأداء والإتقان</span>
              </label>
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
                         class="text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                  <span class="mr-2 text-sm">{{ $arabicName }}</span>
                </label>
              @endforeach
            </div>

            <select name="preferred_time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
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
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">{{ old('notes') }}</textarea>
          </div>

          <!-- Pricing Summary -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h4 class="font-semibold text-gray-900 mb-4">ملخص التكلفة</h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span>
                  <span x-show="billingCycle === 'monthly'">سعر الباقة (شهرياً)</span>
                  <span x-show="billingCycle === 'quarterly'" x-cloak>سعر الباقة (ربع سنوي)</span>
                  <span x-show="billingCycle === 'yearly'" x-cloak>سعر الباقة (سنوياً)</span>
                </span>
                <span x-text="currentPrice.toFixed(2)"></span> <span>{{ $package->getDisplayCurrency() }}</span>
              </div>
              <div class="flex justify-between">
                <span>رسوم الخدمة</span>
                <span>0 {{ $package->getDisplayCurrency() }}</span>
              </div>
              <div class="border-t border-gray-300 pt-2 flex justify-between font-bold text-lg">
                <span>المجموع</span>
                <span class="text-blue-600"><span x-text="currentPrice.toFixed(2)"></span> {{ $package->getDisplayCurrency() }}</span>
              </div>
            </div>
          </div>

          <style>
            [x-cloak] { display: none !important; }
          </style>

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
                    class="flex-1 bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors">
              <i class="ri-secure-payment-line ml-2"></i>
              المتابعة للدفع
            </button>
            
            <a href="{{ route('quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}" 
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
    // Enhanced form validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      if (form) {
        // Function to show error message
        function showError(message) {
          // Remove existing error if any
          const existingError = document.querySelector('.validation-error');
          if (existingError) {
            existingError.remove();
          }
          
          // Create new error element
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
          
          // Insert at the beginning of the form
          form.insertBefore(errorDiv, form.firstChild);
          
          // Scroll to error
          errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        
        // Form submission validation
        form.addEventListener('submit', function(e) {
          
          // Check required fields
          const billingCycle = form.querySelector('[name="billing_cycle"]:checked');
          const currentLevel = form.querySelector('[name="current_level"]');
          const learningGoals = form.querySelectorAll('[name="learning_goals[]"]:checked');
          
          let errors = [];
          
          // Validate billing cycle
          if (!billingCycle) {
            errors.push('يجب اختيار دورة الفوترة');
          }
          
          // Validate current level
          if (!currentLevel || !currentLevel.value) {
            errors.push('يجب اختيار المستوى الحالي في تعلم القرآن');
          }
          
          // Validate learning goals
          if (learningGoals.length === 0) {
            errors.push('يجب اختيار هدف واحد على الأقل من أهداف التعلم');
          }
          
          // If there are errors, prevent submission and show them
          if (errors.length > 0) {
            e.preventDefault();
            showError(errors.join('<br>• '));
            return false;
          }
          
          // Remove any existing validation errors if form is valid
          const existingError = document.querySelector('.validation-error');
          if (existingError) {
            existingError.remove();
          }
          
        });
      }
    });
  </script>

</body>
</html>