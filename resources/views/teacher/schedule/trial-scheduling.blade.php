<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $academy->name ?? 'أكاديمية إتقان' }} - جدولة جلسة تجريبية</title>
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
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center mb-4">
          <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => $academy->subdomain]) }}" 
             class="text-gray-600 hover:text-primary transition-colors ml-3">
            <i class="ri-arrow-right-line text-xl"></i>
          </a>
          <h1 class="text-3xl font-bold text-gray-900">
            <i class="ri-calendar-event-line text-primary ml-2"></i>
            جدولة جلسة تجريبية
          </h1>
        </div>
        <p class="text-gray-600">تحديد موعد الجلسة التجريبية مع الطالب</p>
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
        
        <!-- Student Information -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              <i class="ri-user-line text-primary ml-2"></i>
              معلومات الطالب
            </h3>
            
            <div class="space-y-4">
              <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-sm font-bold ml-3">
                  {{ substr($trialRequest->student_name, 0, 1) }}{{ substr($trialRequest->student_name, strpos($trialRequest->student_name, ' ') !== false ? strpos($trialRequest->student_name, ' ') + 1 : 1, 1) }}
                </div>
                <div>
                  <div class="font-medium text-gray-900">{{ $trialRequest->student_name }}</div>
                  @if($trialRequest->student_age)
                    <div class="text-sm text-gray-600">{{ $trialRequest->student_age }} سنة</div>
                  @endif
                </div>
              </div>

              <div class="space-y-3">
                <div class="flex items-center">
                  <i class="ri-phone-line text-gray-500 ml-2"></i>
                  <span class="text-sm">{{ $trialRequest->phone }}</span>
                </div>
                
                @if($trialRequest->email)
                  <div class="flex items-center">
                    <i class="ri-mail-line text-gray-500 ml-2"></i>
                    <span class="text-sm">{{ $trialRequest->email }}</span>
                  </div>
                @endif
                
                <div class="flex items-center">
                  <i class="ri-book-line text-gray-500 ml-2"></i>
                  <span class="text-sm">{{ $trialRequest->level_label }}</span>
                </div>
                
                @if($trialRequest->preferred_time)
                  <div class="flex items-center">
                    <i class="ri-time-line text-gray-500 ml-2"></i>
                    <span class="text-sm">{{ $trialRequest->time_label }}</span>
                  </div>
                @endif
              </div>

              @if($trialRequest->learning_goals)
                <div>
                  <h4 class="font-medium text-gray-900 mb-2">أهداف التعلم:</h4>
                  <div class="space-y-1">
                    @foreach($trialRequest->learning_goals as $goal)
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

              @if($trialRequest->notes)
                <div>
                  <h4 class="font-medium text-gray-900 mb-2">ملاحظات الطالب:</h4>
                  <div class="p-3 bg-gray-50 rounded-lg text-sm text-gray-700">
                    {{ $trialRequest->notes }}
                  </div>
                </div>
              @endif

              <div class="text-xs text-gray-500 pt-3 border-t border-gray-200">
                <div>رقم الطلب: {{ $trialRequest->request_code }}</div>
                <div>تاريخ الطلب: {{ $trialRequest->created_at->format('Y/m/d H:i') }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Scheduling Form -->
        <div class="lg:col-span-2">
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-6">
              <i class="ri-calendar-schedule-line text-primary ml-2"></i>
              تحديد موعد الجلسة التجريبية
            </h3>

            <form action="{{ route('teacher.schedule.trial.schedule', ['subdomain' => $academy->subdomain, 'trialRequest' => $trialRequest->id]) }}" 
                  method="POST" class="space-y-6">
              @csrf
              
              <!-- Date and Time -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-calendar-line ml-1"></i>
                    تاريخ الجلسة *
                  </label>
                  <input type="date" 
                         id="scheduled_date" 
                         name="scheduled_date" 
                         min="{{ now()->format('Y-m-d') }}"
                         value="{{ old('scheduled_date') }}"
                         required
                         class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>

                <div>
                  <label for="start_time" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="ri-time-line ml-1"></i>
                    وقت بداية الجلسة *
                  </label>
                  <input type="time" 
                         id="start_time" 
                         name="start_time" 
                         value="{{ old('start_time', '10:00') }}"
                         required
                         class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                </div>
              </div>

              <!-- Duration -->
              <div>
                <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="ri-timer-line ml-1"></i>
                  مدة الجلسة (بالدقائق) *
                </label>
                <select id="duration" 
                        name="duration" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                  <option value="">اختر مدة الجلسة</option>
                  <option value="30" {{ old('duration') == '30' ? 'selected' : '' }}>30 دقيقة</option>
                  <option value="60" {{ old('duration') == '60' ? 'selected' : '' }}>60 دقيقة</option>
                </select>
              </div>

              <!-- Meeting Details -->
              <div class="space-y-4">
                <h4 class="font-medium text-gray-900">
                  <i class="ri-video-line text-primary ml-2"></i>
                  تفاصيل الاجتماع
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
                  <p class="text-xs text-gray-500 mt-1">اتركه فارغاً إذا كنت ستضيف الرابط لاحقاً</p>
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

              <!-- Notes -->
              <div>
                <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                  <i class="ri-sticky-note-line ml-1"></i>
                  ملاحظات إضافية
                </label>
                <textarea id="notes" 
                          name="notes" 
                          rows="3"
                          placeholder="أي ملاحظات أو تعليمات خاصة للطالب..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">{{ old('notes') }}</textarea>
              </div>

              <!-- Information Note -->
              <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start">
                  <i class="ri-information-line text-blue-600 text-lg ml-2 mt-0.5"></i>
                  <div class="text-sm text-blue-800">
                    <h4 class="font-semibold mb-1">ملاحظة مهمة</h4>
                    <ul class="list-disc list-inside space-y-1">
                      <li>سيتم إرسال إشعار للطالب بموعد الجلسة تلقائياً</li>
                      <li>يمكنك تعديل موعد الجلسة لاحقاً إذا لزم الأمر</li>
                      <li>تأكد من عدم وجود تضارب مع جلسات أخرى</li>
                    </ul>
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
                  جدولة الجلسة التجريبية
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </main>

  <script>
    // Auto-calculate end time based on duration
    document.getElementById('start_time').addEventListener('change', updateEndTime);
    document.getElementById('duration').addEventListener('change', updateEndTime);

    function updateEndTime() {
      const startTime = document.getElementById('start_time').value;
      const duration = document.getElementById('duration').value;
      
      if (startTime && duration) {
        const [hours, minutes] = startTime.split(':').map(Number);
        const startDate = new Date();
        startDate.setHours(hours, minutes, 0, 0);
        
        const endDate = new Date(startDate.getTime() + (duration * 60000));
        const endTime = endDate.toTimeString().slice(0, 5);
        
        // Show end time hint
        const durationSelect = document.getElementById('duration');
        const selectedOption = durationSelect.options[durationSelect.selectedIndex];
        selectedOption.text = selectedOption.text.split(' (')[0] + ` (ينتهي ${endTime})`;
      }
    }
  </script>

</body>
</html>