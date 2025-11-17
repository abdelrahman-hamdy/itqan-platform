<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>حجز جلسة تجريبية - {{ $teacher->full_name }} - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
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

  @php
    // Get academy branding
    $brandColor = $academy && $academy->brand_color ? $academy->brand_color->value : 'sky';
    $brandColorClass = "text-{$brandColor}-600";
    $brandBgClass = "bg-{$brandColor}-600";
    $brandBgHoverClass = "hover:bg-{$brandColor}-700";
  @endphp

  <!-- Header -->
  <x-booking.top-bar
    :academy="$academy"
    title="حجز جلسة تجريبية"
    :backRoute="route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id])" />


  <!-- Main Content -->
  <section class="py-8">
    <div class="container mx-auto px-4 max-w-4xl">
      
      <!-- Teacher Info -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center gap-4">
          <x-teacher-avatar :teacher="$teacher" size="lg" />
          <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $teacher->full_name }}</h2>
            <p class="text-gray-600">معلم القرآن الكريم المعتمد</p>
            <p class="text-sm text-gray-500">{{ $teacher->teacher_code }}</p>
          </div>
        </div>
      </div>

      <!-- Trial Session Form -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
          <h3 class="text-2xl font-bold text-gray-900 mb-2">
            <i class="ri-gift-line {{ $brandColorClass }} ml-2"></i>
            حجز جلسة تجريبية مجانية
          </h3>
          <p class="text-gray-600">املأ النموذج أدناه وسيتم إرسال إشعار لك بموعد الجلسة التجريبية بعد مراجعة المعلم لطلبك</p>
        </div>

        <form action="{{ route('public.quran-teachers.trial.submit', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id]) }}" method="POST" class="space-y-6">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
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
          <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 mb-6">
            <div class="flex items-center gap-2 mb-4">
              <div class="w-8 h-8 rounded-lg bg-{{ $brandColor }}-100 flex items-center justify-center">
                <i class="ri-user-line {{ $brandColorClass }} text-lg"></i>
              </div>
              <h4 class="font-semibold text-gray-900">معلومات الطالب</h4>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">الاسم</span>
                <span class="font-medium text-gray-900">{{ auth()->user()->studentProfile?->full_name ?? auth()->user()->name }}</span>
              </div>
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">البريد الإلكتروني</span>
                <span class="font-medium text-gray-900">{{ auth()->user()->email }}</span>
              </div>
              @if(auth()->user()->studentProfile?->phone)
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">رقم الهاتف</span>
                <span class="font-medium text-gray-900">{{ auth()->user()->studentProfile->phone }}</span>
              </div>
              @endif
              @if(auth()->user()->studentProfile?->birth_date)
              <div class="flex flex-col gap-1">
                <span class="{{ $brandColorClass }} font-medium text-xs">العمر</span>
                <span class="font-medium text-gray-900">{{ floor(auth()->user()->studentProfile->birth_date->diffInYears(now())) }} سنة</span>
              </div>
              @endif
            </div>
          </div>

          <!-- Current Level -->
          <div>
            <label for="current_level" class="block text-sm font-medium text-gray-700 mb-2">المستوى الحالي في تعلم القرآن *</label>
            <select id="current_level" name="current_level" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-{{ $brandColor }}-500 focus:border-{{ $brandColor }}-600">
              <option value="">اختر مستواك</option>
              <option value="beginner">مبتدئ (لا أعرف القراءة)</option>
              <option value="elementary">أساسي (أقرأ ببطء)</option>
              <option value="intermediate">متوسط (أقرأ بطلاقة)</option>
              <option value="advanced">متقدم (أحفظ أجزاء من القرآن)</option>
              <option value="expert">متمكن (أحفظ أكثر من 10 أجزاء)</option>
              <option value="hafiz">حافظ (أحفظ القرآن كاملاً)</option>
            </select>
          </div>

          <!-- Goals -->
          <div>
            <label for="learning_goals" class="block text-sm font-medium text-gray-700 mb-2">أهدافك من تعلم القرآن *</label>
            <div class="space-y-2">
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="reading" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="mr-2">تعلم القراءة الصحيحة</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="tajweed" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="mr-2">تعلم أحكام التجويد</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="memorization" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="mr-2">حفظ القرآن الكريم</span>
              </label>
              <label class="flex items-center">
                <input type="checkbox" name="learning_goals[]" value="improvement" class="text-{{ $brandColor }}-600 focus:ring-{{ $brandColor }}-500 border-gray-300 rounded">
                <span class="mr-2">تحسين الأداء والإتقان</span>
              </label>
            </div>
          </div>

          <!-- Preferred Time -->
          <div>
            <label for="preferred_time" class="block text-sm font-medium text-gray-700 mb-2">الوقت المفضل للجلسة</label>
            <select id="preferred_time" name="preferred_time"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-{{ $brandColor }}-500 focus:border-{{ $brandColor }}-600">
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
                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-{{ $brandColor }}-500 focus:border-{{ $brandColor }}-600"></textarea>
          </div>

          <!-- Information -->
          <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start gap-2">
              <i class="ri-information-line text-amber-600 text-xl mt-0.5"></i>
              <div class="flex-1">
                <h4 class="font-semibold text-amber-900 mb-2">معلومات هامة:</h4>
                <ul class="text-sm text-amber-800 space-y-1.5">
                  <li>• الجلسة التجريبية مجانية تماماً ومدتها 30 دقيقة</li>
                  <li>• سيتم إرسال إشعار لك بموعد الجلسة خلال 24 ساعة من مراجعة المعلم</li>
                  <li>• يتم إجراء الجلسة عبر نظام الفيديو المدمج في المنصة</li>
                  <li>• يمكنك حجز جلسة تجريبية واحدة فقط مع كل معلم</li>
                </ul>
              </div>
            </div>
          </div>



          <!-- Submit Button -->
          <div class="flex gap-4">
            <button type="submit"
                    onclick="console.log('Trial form submit clicked'); return true;"
                    class="flex-1 {{ $brandBgClass }} text-white py-3 px-6 rounded-lg font-medium {{ $brandBgHoverClass }} transition-colors">
              <i class="ri-send-plane-line ml-2"></i>
              إرسال طلب الجلسة التجريبية
            </button>
            
            <a href="{{ route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacher' => $teacher->id]) }}" 
               class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
              إلغاء
            </a>
          </div>
        </form>
      </div>

    </div>
  </section>

  <script>
    // Debug form submission
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      if (form) {
        form.addEventListener('submit', function(e) {
          console.log('Form submit event fired');
          console.log('Form data:', new FormData(form));
          
          // Check if required fields are filled
          const currentLevel = form.querySelector('[name="current_level"]');
          const learningGoals = form.querySelectorAll('[name="learning_goals[]"]:checked');
          
          console.log('Current level:', currentLevel ? currentLevel.value : 'not found');
          console.log('Learning goals checked:', learningGoals.length);
          
          if (!currentLevel || !currentLevel.value) {
            console.error('Current level not selected');
          }
          
          if (learningGoals.length === 0) {
            console.error('No learning goals selected');
          }
        });
      }
    });
  </script>

</body>
</html>