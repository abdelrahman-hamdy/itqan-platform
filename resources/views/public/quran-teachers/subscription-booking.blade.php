<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>اشتراك {{ $package->getDisplayName() }} - {{ $teacher->full_name }} - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
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
  <header class="bg-white shadow-sm">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo and Academy Name -->
        <div class="flex items-center space-x-3 space-x-reverse">
          @if($academy->logo)
            <img src="{{ asset('storage/' . $academy->logo) }}" alt="{{ $academy->name }}" class="h-10 w-10 rounded-lg">
          @endif
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $academy->name ?? 'أكاديمية إتقان' }}</h1>
            <p class="text-sm text-gray-600">اشتراك جديد</p>
          </div>
        </div>

        <!-- Back Button -->
        <a href="{{ route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherCode' => $teacher->teacher_code]) }}" 
           class="text-gray-600 hover:text-primary transition-colors">
          <i class="ri-arrow-right-line text-xl"></i>
        </a>
      </div>
    </div>
  </header>

  <!-- Main Content -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      
      <!-- Teacher & Package Info -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        
        <!-- Teacher Info -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-bold text-gray-900 mb-4">معلومات المعلم</h3>
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-xl font-bold">
              @if($teacher->avatar)
                <img src="{{ asset('storage/' . $teacher->avatar) }}" alt="{{ $teacher->full_name }}" class="w-full h-full rounded-full object-cover">
              @else
                {{ substr($teacher->first_name, 0, 1) }}{{ substr($teacher->last_name, 0, 1) }}
              @endif
            </div>
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
              <span class="text-lg font-bold text-primary">{{ $package->monthly_price }} {{ $package->currency }}</span>
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
            <i class="ri-vip-crown-line text-primary ml-2"></i>
            اشتراك جديد
          </h3>
          <p class="text-gray-600">املأ البيانات أدناه لإتمام عملية الاشتراك</p>
        </div>

        <form action="{{ route('public.quran-teachers.subscribe.submit', ['subdomain' => $academy->subdomain, 'teacherCode' => $teacher->teacher_code, 'packageId' => $package->id]) }}" method="POST" class="space-y-6">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
          <input type="hidden" name="package_id" value="{{ $package->id }}">
          <input type="hidden" name="academy_id" value="{{ $academy->id }}">

          <!-- Student Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="student_name" class="block text-sm font-medium text-gray-700 mb-2">اسم الطالب *</label>
              <input type="text" id="student_name" name="student_name" required
                     value="{{ auth()->user()->name ?? '' }}"
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
            </div>
            
            <div>
              <label for="student_age" class="block text-sm font-medium text-gray-700 mb-2">عمر الطالب</label>
              <select id="student_age" name="student_age"
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                <option value="">اختر العمر</option>
                @for($i = 5; $i <= 50; $i++)
                  <option value="{{ $i }}">{{ $i }} سنة</option>
                @endfor
              </select>
            </div>
          </div>

          <!-- Contact Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">رقم الهاتف *</label>
              <input type="tel" id="phone" name="phone" required
                     value="{{ auth()->user()->phone ?? '' }}"
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
            </div>
            
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">البريد الإلكتروني</label>
              <input type="email" id="email" name="email"
                     value="{{ auth()->user()->email ?? '' }}"
                     class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
            </div>
          </div>

          <!-- Billing Cycle -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-3">دورة الفوترة *</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <label class="relative">
                <input type="radio" name="billing_cycle" value="monthly" checked
                       class="sr-only peer">
                <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5">
                  <div class="text-center">
                    <div class="text-lg font-bold text-gray-900">شهرياً</div>
                    <div class="text-sm text-gray-600">{{ $package->monthly_price }} {{ $package->currency }}/شهر</div>
                  </div>
                </div>
              </label>
              
              @if($package->quarterly_price)
                <label class="relative">
                  <input type="radio" name="billing_cycle" value="quarterly"
                         class="sr-only peer">
                  <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5">
                    <div class="text-center">
                      <div class="text-lg font-bold text-gray-900">ربع سنوي</div>
                      <div class="text-sm text-gray-600">{{ $package->quarterly_price }} {{ $package->currency }}/3 أشهر</div>
                      <div class="text-xs text-green-600 font-medium">وفر 10%</div>
                    </div>
                  </div>
                </label>
              @endif
              
              @if($package->yearly_price)
                <label class="relative">
                  <input type="radio" name="billing_cycle" value="yearly"
                         class="sr-only peer">
                  <div class="p-4 border-2 border-gray-200 rounded-lg cursor-pointer peer-checked:border-primary peer-checked:bg-primary/5">
                    <div class="text-center">
                      <div class="text-lg font-bold text-gray-900">سنوياً</div>
                      <div class="text-sm text-gray-600">{{ $package->yearly_price }} {{ $package->currency }}/سنة</div>
                      <div class="text-xs text-green-600 font-medium">وفر 20%</div>
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
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">اختر مستواك</option>
              <option value="beginner">مبتدئ (لا أعرف القراءة)</option>
              <option value="basic">أساسي (أقرأ ببطء)</option>
              <option value="intermediate">متوسط (أقرأ بطلاقة)</option>
              <option value="advanced">متقدم (أحفظ أجزاء من القرآن)</option>
              <option value="expert">متمكن (أحفظ أكثر من 10 أجزاء)</option>
            </select>
          </div>

          <!-- Learning Goals -->
          <div>
            <label for="learning_goals" class="block text-sm font-medium text-gray-700 mb-2">أهدافك من تعلم القرآن *</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="reading" class="text-primary focus:ring-primary border-gray-300 rounded">
                <span class="mr-2">تعلم القراءة الصحيحة</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="tajweed" class="text-primary focus:ring-primary border-gray-300 rounded">
                <span class="mr-2">تعلم أحكام التجويد</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="memorization" class="text-primary focus:ring-primary border-gray-300 rounded">
                <span class="mr-2">حفظ القرآن الكريم</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="improvement" class="text-primary focus:ring-primary border-gray-300 rounded">
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
                         class="text-primary focus:ring-primary border-gray-300 rounded">
                  <span class="mr-2 text-sm">{{ $arabicName }}</span>
                </label>
              @endforeach
            </div>
            
            <select name="preferred_time" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="">اختر الوقت المفضل</option>
              <option value="morning">صباحاً (6:00 - 12:00)</option>
              <option value="afternoon">بعد الظهر (12:00 - 18:00)</option>
              <option value="evening">مساءً (18:00 - 22:00)</option>
            </select>
          </div>

          <!-- Additional Notes -->
          <div>
            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">ملاحظات إضافية</label>
            <textarea id="notes" name="notes" rows="4"
                      placeholder="أي معلومات إضافية تود مشاركتها مع المعلم..."
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary"></textarea>
          </div>

          <!-- Pricing Summary -->
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
            <h4 class="font-semibold text-gray-900 mb-4">ملخص التكلفة</h4>
            <div class="space-y-2">
              <div class="flex justify-between">
                <span>سعر الباقة (شهرياً)</span>
                <span>{{ $package->monthly_price }} {{ $package->currency }}</span>
              </div>
              <div class="flex justify-between">
                <span>رسوم الخدمة</span>
                <span>0 {{ $package->currency }}</span>
              </div>
              <div class="border-t border-gray-300 pt-2 flex justify-between font-bold text-lg">
                <span>المجموع</span>
                <span class="text-primary">{{ $package->monthly_price }} {{ $package->currency }}</span>
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

          <!-- Agreement -->
          <div class="flex items-start">
            <input type="checkbox" id="agree_terms" name="agree_terms" required
                   class="mt-1 text-primary focus:ring-primary border-gray-300 rounded">
            <label for="agree_terms" class="mr-2 text-sm text-gray-700">
              أوافق على <a href="#" class="text-primary hover:underline">شروط الخدمة</a> و 
              <a href="#" class="text-primary hover:underline">سياسة الخصوصية</a> و
              <a href="#" class="text-primary hover:underline">سياسة الاسترداد</a>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-4">
            <button type="submit" 
                    class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-medium hover:bg-secondary transition-colors">
              <i class="ri-secure-payment-line ml-2"></i>
              المتابعة للدفع
            </button>
            
            <a href="{{ route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherCode' => $teacher->teacher_code]) }}" 
               class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              إلغاء
            </a>
          </div>
        </form>
      </div>

    </div>
  </section>

</body>
</html>