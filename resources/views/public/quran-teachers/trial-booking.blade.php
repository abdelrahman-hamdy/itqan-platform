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
            <p class="text-sm text-gray-600">حجز جلسة تجريبية</p>
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
      
      <!-- Teacher Info -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex items-center gap-4">
          <div class="w-16 h-16 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-xl font-bold">
            @if($teacher->avatar)
              <img src="{{ asset('storage/' . $teacher->avatar) }}" alt="{{ $teacher->full_name }}" class="w-full h-full rounded-full object-cover">
            @else
              {{ substr($teacher->first_name, 0, 1) }}{{ substr($teacher->last_name, 0, 1) }}
            @endif
          </div>
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
            <i class="ri-gift-line text-green-600 ml-2"></i>
            حجز جلسة تجريبية مجانية
          </h3>
          <p class="text-gray-600">املأ النموذج أدناه وسيقوم المعلم بالتواصل معك لتحديد موعد الجلسة التجريبية</p>
        </div>

        <form action="{{ route('public.quran-teachers.trial.submit', ['subdomain' => $academy->subdomain, 'teacherCode' => $teacher->teacher_code]) }}" method="POST" class="space-y-6">
          @csrf
          <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
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

          <!-- Goals -->
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

          <!-- Preferred Time -->
          <div>
            <label for="preferred_time" class="block text-sm font-medium text-gray-700 mb-2">الوقت المفضل للجلسة</label>
            <select id="preferred_time" name="preferred_time"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
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

          <!-- Terms -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-semibold text-blue-900 mb-2">شروط الجلسة التجريبية:</h4>
            <ul class="text-sm text-blue-800 space-y-1">
              <li>• الجلسة التجريبية مجانية ومدتها 30 دقيقة</li>
              <li>• سيتم التواصل معك خلال 24 ساعة لتحديد الموعد</li>
              <li>• يمكن إجراء الجلسة عبر Google Meet أو Zoom</li>
              <li>• يمكنك حجز جلسة تجريبية واحدة فقط مع كل معلم</li>
            </ul>
          </div>

          <!-- Agreement -->
          <div class="flex items-start">
            <input type="checkbox" id="agree_terms" name="agree_terms" required
                   class="mt-1 text-primary focus:ring-primary border-gray-300 rounded">
            <label for="agree_terms" class="mr-2 text-sm text-gray-700">
              أوافق على <a href="#" class="text-primary hover:underline">شروط الخدمة</a> و 
              <a href="#" class="text-primary hover:underline">سياسة الخصوصية</a>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="flex gap-4">
            <button type="submit" 
                    class="flex-1 bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 transition-colors">
              <i class="ri-send-plane-line ml-2"></i>
              إرسال طلب الجلسة التجريبية
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