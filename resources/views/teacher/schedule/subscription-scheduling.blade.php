<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $academy->name ?? 'أكاديمية إتقان' }} - إعداد جدول الاشتراك</title>
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

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.teacher-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.teacher-sidebar')

  <!-- Main Content -->
  <main class="mr-80 pt-20 min-h-screen" id="main-content">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center mb-4">
          <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => $academy->subdomain]) }}" 
             class="text-gray-600 hover:text-primary transition-colors ml-3">
            <i class="ri-arrow-right-line text-xl"></i>
          </a>
          <h1 class="text-3xl font-bold text-gray-900">
            <i class="ri-calendar-schedule-line text-primary ml-2"></i>
            إعداد جدول الاشتراك
          </h1>
        </div>
        <p class="text-gray-600">تحديد مواعيد الجلسات المنتظمة للاشتراك</p>
      </div>

      <!-- Error Messages -->
      @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">
          <div class="flex items-center mb-2">
            <i class="ri-error-warning-line text-lg ml-2"></i>
            <span class="font-medium">يرجى تصحيح الأخطاء التالية:</span>
          </div>
          <ul class="list-disc list-inside space-y-1">
            @foreach($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Subscription Information -->
        <div class="lg:col-span-1 space-y-6">
          
          <!-- Student & Subscription Details -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              <i class="ri-user-line text-primary ml-2"></i>
              تفاصيل الاشتراك
            </h3>
            
            <div class="space-y-4">
              <!-- Student Info -->
              <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-sm font-bold ml-3">
                  {{ substr($subscription->student->name, 0, 1) }}{{ substr($subscription->student->name, strpos($subscription->student->name, ' ') !== false ? strpos($subscription->student->name, ' ') + 1 : 1, 1) }}
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ $subscription->student->name }}</div>
                  <div class="text-sm text-gray-600">الطالب</div>
                </div>
              </div>

              <!-- Subscription Info -->
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">الباقة:</span>
                  <span class="font-medium">{{ $subscription->package->getDisplayName() ?? 'باقة مخصصة' }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-gray-600">نوع الاشتراك:</span>
                  <span class="font-medium">{{ $subscription->subscription_type === 'private' ? 'جلسات خاصة' : 'جلسات جماعية' }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-gray-600">إجمالي الجلسات:</span>
                  <span class="font-medium">{{ $subscription->total_sessions }} جلسة</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-gray-600">الجلسات المتبقية:</span>
                  <span class="font-medium text-green-600">{{ $subscription->sessions_remaining }} جلسة</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-gray-600">بداية الاشتراك:</span>
                  <span class="font-medium">{{ $subscription->starts_at->format('Y/m/d') }}</span>
                </div>
                
                <div class="flex justify-between">
                  <span class="text-gray-600">نهاية الاشتراك:</span>
                  <span class="font-medium">{{ $subscription->expires_at->format('Y/m/d') }}</span>
                </div>
              </div>

              @if($subscription->metadata && isset($subscription->metadata['learning_goals']))
                <div>
                  <h4 class="font-medium text-gray-900 mb-2">أهداف التعلم:</h4>
                  <div class="space-y-1">
                    @foreach($subscription->metadata['learning_goals'] as $goal)
                      <div class="flex items-center text-sm text-gray-600">
                        <i class="ri-check-line text-green-500 ml-1"></i>
                        @switch($goal)
                          @case('reading') تعلم القراءة الصحيحة @break
                          @case('tajweed') تعلم أحكام التجويد @break
                          @case('memorization') حفظ القرآن الكريم @break
                          @case('improvement') تحسين الأداء والإتقان @break
                          @default {{ $goal }}
                        @endswitch
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>
          </div>

          <!-- Existing Sessions -->
          @if($existingSessions->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="ri-calendar-check-line text-green-600 ml-2"></i>
                الجلسات المجدولة
              </h3>
              
              <div class="space-y-3">
                @foreach($existingSessions->take(5) as $session)
                  <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div>
                      <div class="font-medium text-gray-900">
                        {{ $session->scheduled_at->translatedFormat('l') }}
                      </div>
                      <div class="text-sm text-gray-600">
                        {{ $session->scheduled_at->format('Y/m/d H:i') }}
                      </div>
                    </div>
                    <div class="text-sm text-gray-600">
                      {{ $session->duration_minutes }} دقيقة
                    </div>
                  </div>
                @endforeach
                
                @if($existingSessions->count() > 5)
                  <div class="text-center text-sm text-gray-500">
                    و {{ $existingSessions->count() - 5 }} جلسات أخرى...
                  </div>
                @endif
              </div>
            </div>
          @endif
        </div>

        <!-- Scheduling Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6">
              <i class="ri-calendar-add-line text-primary ml-2"></i>
              إعداد الجدول الأسبوعي
            </h3>

            <form action="{{ route('teacher.schedule.subscription.setup', ['subdomain' => $academy->subdomain, 'subscription' => $subscription->id]) }}" 
                  method="POST" class="space-y-6">
              @csrf
              
              <!-- Start Date -->
              <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="ri-calendar-line ml-1"></i>
                  تاريخ بداية الجلسات *
                </label>
                <input type="date" 
                       id="start_date" 
                       name="start_date" 
                       min="{{ max(now(), $subscription->starts_at)->format('Y-m-d') }}"
                       max="{{ $subscription->expires_at->format('Y-m-d') }}"
                       value="{{ old('start_date', max(now(), $subscription->starts_at)->format('Y-m-d')) }}"
                       required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              </div>

              <!-- Weekly Schedule -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-4">
                  <i class="ri-time-line ml-1"></i>
                  الجدول الأسبوعي *
                </label>
                
                <div id="weekly-sessions" class="space-y-4">
                  <!-- Session Template (will be cloned by JavaScript) -->
                  <div class="session-template hidden border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-4">
                      <h4 class="font-medium text-gray-900">جلسة <span class="session-number">1</span></h4>
                      <button type="button" class="remove-session text-red-600 hover:text-red-800 transition-colors">
                        <i class="ri-delete-bin-line"></i>
                        حذف
                      </button>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">اليوم *</label>
                        <select name="sessions[0][day]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                          <option value="">اختر اليوم</option>
                          <option value="sunday">الأحد</option>
                          <option value="monday">الاثنين</option>
                          <option value="tuesday">الثلاثاء</option>
                          <option value="wednesday">الأربعاء</option>
                          <option value="thursday">الخميس</option>
                          <option value="friday">الجمعة</option>
                          <option value="saturday">السبت</option>
                        </select>
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">الوقت *</label>
                        <input type="time" 
                               name="sessions[0][time]" 
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                      </div>
                      
                      <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">المدة (دقيقة) *</label>
                        <select name="sessions[0][duration]" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                          <option value="">اختر المدة</option>
                          <option value="30">30 دقيقة</option>
                          <option value="45">45 دقيقة</option>
                          <option value="60">60 دقيقة</option>
                          <option value="90">90 دقيقة</option>
                        </select>
                      </div>
                    </div>
                  </div>
                </div>

                <button type="button" 
                        id="add-session" 
                        class="mt-4 bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                  <i class="ri-add-line ml-1"></i>
                  إضافة جلسة أخرى
                </button>
              </div>

              <!-- Meeting Details -->
              <div class="space-y-4">
                <h4 class="font-medium text-gray-900">
                  <i class="ri-video-line text-primary ml-2"></i>
                  تفاصيل الاجتماع (ستطبق على جميع الجلسات)
                </h4>
                
                <div>
                  <label for="meeting_link" class="block text-sm font-medium text-gray-700 mb-2">
                    رابط الاجتماع (Google Meet، Zoom، إلخ)
                  </label>
                  <input type="url" 
                         id="meeting_link" 
                         name="meeting_link" 
                         value="{{ old('meeting_link') }}"
                         placeholder="https://meet.google.com/xxx-xxx-xxx"
                         class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  <p class="text-xs text-gray-500 mt-1">اتركه فارغاً إذا كنت ستضيف روابط مختلفة لكل جلسة لاحقاً</p>
                </div>

                <div>
                  <label for="meeting_password" class="block text-sm font-medium text-gray-700 mb-2">
                    كلمة مرور الاجتماع (اختياري)
                  </label>
                  <input type="text" 
                         id="meeting_password" 
                         name="meeting_password" 
                         value="{{ old('meeting_password') }}"
                         placeholder="كلمة مرور الاجتماع"
                         class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
              </div>

              <!-- Information Notes -->
              <div class="space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                  <div class="flex items-start">
                    <i class="ri-information-line text-blue-600 text-lg ml-2 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                      <h4 class="font-semibold mb-1">معلومات مهمة</h4>
                      <ul class="list-disc list-inside space-y-1">
                        <li>سيتم إنشاء جلسات متكررة حتى انتهاء الاشتراك في {{ $subscription->expires_at->format('Y/m/d') }}</li>
                        <li>يمكنك تعديل أو إلغاء جلسات فردية لاحقاً</li>
                        <li>تأكد من عدم وجود تضارب مع جلسات أخرى</li>
                        <li>سيتم إرسال إشعار للطالب بالجدول الجديد</li>
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                  <div class="flex items-start">
                    <i class="ri-lightbulb-line text-yellow-600 text-lg ml-2 mt-0.5"></i>
                    <div class="text-sm text-yellow-800">
                      <h4 class="font-semibold mb-1">نصيحة</h4>
                      <p>يُنصح بوضع جدول ثابت أسبوعياً لتسهيل التزام الطالب. يمكنك دائماً تعديل الجلسات الفردية عند الحاجة.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => $academy->subdomain]) }}" 
                   class="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-200 transition-colors">
                  <i class="ri-close-line ml-2"></i>
                  إلغاء
                </a>
                
                <button type="submit" 
                        class="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-secondary transition-colors">
                  <i class="ri-calendar-check-line ml-2"></i>
                  إعداد الجدول
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </main>

  <script>
    let sessionCount = 0;

    document.addEventListener('DOMContentLoaded', function() {
      const addSessionBtn = document.getElementById('add-session');
      const weeklySessionsContainer = document.getElementById('weekly-sessions');
      const sessionTemplate = document.querySelector('.session-template');

      // Add first session by default
      addSession();

      addSessionBtn.addEventListener('click', addSession);

      function addSession() {
        const newSession = sessionTemplate.cloneNode(true);
        newSession.classList.remove('session-template', 'hidden');
        
        // Update session number
        const sessionNumber = newSession.querySelector('.session-number');
        sessionNumber.textContent = sessionCount + 1;
        
        // Update input names with correct array index
        const inputs = newSession.querySelectorAll('input, select');
        inputs.forEach(input => {
          const name = input.getAttribute('name');
          if (name) {
            input.setAttribute('name', name.replace('[0]', `[${sessionCount}]`));
          }
        });

        // Add remove functionality
        const removeBtn = newSession.querySelector('.remove-session');
        removeBtn.addEventListener('click', function() {
          if (weeklySessionsContainer.children.length > 2) { // Keep at least one session + template
            newSession.remove();
            updateSessionNumbers();
          }
        });

        weeklySessionsContainer.appendChild(newSession);
        sessionCount++;
      }

      function updateSessionNumbers() {
        const sessions = weeklySessionsContainer.querySelectorAll('.session-template:not(.hidden)');
        sessions.forEach((session, index) => {
          const sessionNumber = session.querySelector('.session-number');
          sessionNumber.textContent = index + 1;
          
          // Update input names
          const inputs = session.querySelectorAll('input, select');
          inputs.forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
              const newName = name.replace(/\[\d+\]/, `[${index}]`);
              input.setAttribute('name', newName);
            }
          });
        });
      }
    });
  </script>

</body>
</html>