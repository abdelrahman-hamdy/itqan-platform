<x-layouts.teacher 
  title="إدارة الكورس - {{ $course->title }}" 
  description="إدارة الكورس التفاعلي {{ $course->title }} - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">

  <!-- Breadcrumb -->
  <nav class="mb-8">
    <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
      <li><a href="{{ route('teacher.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">لوحة التحكم</a></li>
      <li>/</li>
      <li><a href="#" class="hover:text-primary">الكورسات التفاعلية</a></li>
      <li>/</li>
      <li class="text-gray-900">{{ $course->title }}</li>
    </ol>
  </nav>

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

  <!-- Course Header -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start gap-8">
      
      <!-- Course Info -->
      <div class="flex-1">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-16 h-16 bg-primary/10 rounded-xl flex items-center justify-center">
            <i class="ri-book-open-line text-2xl text-primary"></i>
          </div>
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $course->title }}</h1>
            <p class="text-lg text-gray-600">{{ $course->description }}</p>
          </div>
        </div>

        <!-- Course Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <!-- Subject -->
          <div class="text-center p-4 bg-blue-50 rounded-lg">
            <i class="ri-book-line text-2xl text-blue-600 mb-2"></i>
            <h3 class="font-medium text-gray-900 mb-1">المادة</h3>
            <p class="text-blue-600 font-medium">{{ $course->subject->name ?? 'غير محدد' }}</p>
          </div>

          <!-- Grade Level -->
          <div class="text-center p-4 bg-green-50 rounded-lg">
            <i class="ri-graduation-cap-line text-2xl text-green-600 mb-2"></i>
            <h3 class="font-medium text-gray-900 mb-1">المرحلة</h3>
            <p class="text-green-600 font-medium">{{ $course->gradeLevel->name ?? 'غير محدد' }}</p>
          </div>

          <!-- Students Count -->
          <div class="text-center p-4 bg-orange-50 rounded-lg">
            <i class="ri-user-3-line text-2xl text-orange-600 mb-2"></i>
            <h3 class="font-medium text-gray-900 mb-1">عدد الطلاب</h3>
            <p class="text-orange-600 font-medium">{{ $teacherData['total_students'] ?? 0 }}/{{ $course->max_students }}</p>
          </div>

          <!-- Sessions Count -->
          <div class="text-center p-4 bg-purple-50 rounded-lg">
            <i class="ri-calendar-line text-2xl text-purple-600 mb-2"></i>
            <h3 class="font-medium text-gray-900 mb-1">عدد الجلسات</h3>
            <p class="text-purple-600 font-medium">{{ $teacherData['total_sessions'] ?? 0 }}</p>
          </div>
        </div>
      </div>

      <!-- Course Status -->
      <div class="lg:w-80">
        <div class="bg-gray-50 rounded-lg p-6">
          <h3 class="font-bold text-gray-900 mb-4">حالة الكورس</h3>
          
          <div class="space-y-3">
            <!-- Course Status Badge -->
            <div class="flex items-center justify-between">
              <span class="text-gray-600">الحالة:</span>
              @if($course->status === 'active')
                <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">نشط</span>
              @elseif($course->status === 'upcoming')
                <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">قادم</span>
              @elseif($course->status === 'completed')
                <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">مكتمل</span>
              @else
                <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-medium">{{ $course->status }}</span>
              @endif
            </div>

            <!-- Progress -->
            <div class="flex items-center justify-between">
              <span class="text-gray-600">التقدم:</span>
              <span class="font-medium text-gray-900">
                {{ $teacherData['completed_sessions'] ?? 0 }}/{{ $teacherData['total_sessions'] ?? 0 }} جلسة
              </span>
            </div>

            <!-- Start Date -->
            @if($course->start_date)
            <div class="flex items-center justify-between">
              <span class="text-gray-600">تاريخ البدء:</span>
              <span class="font-medium text-gray-900">{{ $course->start_date->format('Y/m/d') }}</span>
            </div>
            @endif

            <!-- Duration -->
            @if($course->duration_weeks)
            <div class="flex items-center justify-between">
              <span class="text-gray-600">المدة:</span>
              <span class="font-medium text-gray-900">{{ $course->duration_weeks }} أسبوع</span>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <div class="mb-8">
    <div class="border-b border-gray-200">
      <nav class="-mb-px flex space-x-8 space-x-reverse">
        <button class="tab-button active border-b-2 border-primary text-primary py-4 px-1 text-sm font-medium" data-tab="students">
          الطلاب المسجلين
        </button>
        <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium" data-tab="sessions">
          الجلسات
        </button>
        <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium" data-tab="materials">
          المواد التعليمية
        </button>
        <button class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 py-4 px-1 text-sm font-medium" data-tab="settings">
          إعدادات الكورس
        </button>
      </nav>
    </div>
  </div>

  <!-- Tab Content -->
  
  <!-- Students Tab -->
  <div id="students-tab" class="tab-content">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900">الطلاب المسجلين</h2>
        <div class="flex items-center space-x-4 space-x-reverse">
          <span class="text-sm text-gray-600">{{ $course->enrollments->count() }} من {{ $course->max_students }} طالب</span>
        </div>
      </div>

      @if($course->enrollments->count() > 0)
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الطالب</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">تاريخ التسجيل</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              @foreach($course->enrollments as $enrollment)
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="flex-shrink-0 h-10 w-10">
                        <img class="h-10 w-10 rounded-full object-cover" src="{{ $enrollment->student->user->profile_image ?? 'https://ui-avatars.com/api/?name=' . urlencode($enrollment->student->user->name) . '&background=4169E1&color=fff' }}" alt="{{ $enrollment->student->user->name }}">
                      </div>
                      <div class="mr-4">
                        <div class="text-sm font-medium text-gray-900">{{ $enrollment->student->user->name }}</div>
                        <div class="text-sm text-gray-500">{{ $enrollment->student->user->email }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ $enrollment->created_at->format('Y/m/d') }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                      مسجل
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <button class="text-primary hover:text-primary-dark">عرض الملف</button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="text-center py-8">
          <i class="ri-user-3-line text-4xl text-gray-400 mb-4"></i>
          <h3 class="text-lg font-medium text-gray-900 mb-2">لا يوجد طلاب مسجلين بعد</h3>
          <p class="text-gray-600">سيظهر الطلاب المسجلين في الكورس هنا</p>
        </div>
      @endif
    </div>
  </div>

  <!-- Sessions Tab -->
  <div id="sessions-tab" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900">جلسات الكورس</h2>
        <button class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg">
          <i class="ri-add-line ml-1"></i>
          إضافة جلسة جديدة
        </button>
      </div>

      @if($course->sessions && $course->sessions->count() > 0)
        <div class="space-y-4">
          @foreach($course->sessions as $session)
            <div class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow">
              <div class="flex items-center justify-between">
                <div class="flex-1">
                  <h3 class="font-medium text-gray-900 mb-2">{{ $session->title ?? 'جلسة رقم ' . $loop->iteration }}</h3>
                  <div class="flex items-center space-x-6 space-x-reverse text-sm text-gray-600">
                    <span><i class="ri-calendar-line ml-1"></i>{{ $session->session_date ? $session->session_date->format('Y/m/d') : 'غير مجدولة' }}</span>
                    <span><i class="ri-time-line ml-1"></i>{{ $session->duration_minutes ?? 60 }} دقيقة</span>
                    @if($session->status)
                      <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                        @if($session->status === 'completed') bg-green-100 text-green-800 
                        @elseif($session->status === 'ongoing') bg-blue-100 text-blue-800 
                        @elseif($session->status === 'scheduled') bg-yellow-100 text-yellow-800 
                        @else bg-gray-100 text-gray-800 @endif">
                        {{ $session->status }}
                      </span>
                    @endif
                  </div>
                </div>
                <div class="flex items-center space-x-2 space-x-reverse">
                  @if($session->status === 'scheduled' || $session->status === 'ongoing')
                    <a href="{{ route('interactive-courses.show', ['subdomain' => auth()->user()->academy->subdomain, 'course' => $course->id]) }}#session-{{ $session->id }}" 
                       class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg">
                      <i class="ri-play-line ml-1"></i>
                      بدء الجلسة
                    </a>
                  @endif
                  <button class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg">
                    <i class="ri-edit-line ml-1"></i>
                    تعديل
                  </button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @else
        <div class="text-center py-8">
          <i class="ri-calendar-line text-4xl text-gray-400 mb-4"></i>
          <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد جلسات بعد</h3>
          <p class="text-gray-600 mb-4">قم بإنشاء جلسات للكورس ليتمكن الطلاب من المشاركة</p>
          <button class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg">
            <i class="ri-add-line ml-1"></i>
            إضافة جلسة جديدة
          </button>
        </div>
      @endif
    </div>
  </div>

  <!-- Materials Tab -->
  <div id="materials-tab" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900">المواد التعليمية</h2>
        <button class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg">
          <i class="ri-upload-line ml-1"></i>
          رفع مادة جديدة
        </button>
      </div>

      <div class="text-center py-8">
        <i class="ri-file-text-line text-4xl text-gray-400 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">لا توجد مواد تعليمية بعد</h3>
        <p class="text-gray-600 mb-4">قم برفع المواد التعليمية والملفات المساعدة للطلاب</p>
        <button class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-4 rounded-lg">
          <i class="ri-upload-line ml-1"></i>
          رفع مادة جديدة
        </button>
      </div>
    </div>
  </div>

  <!-- Settings Tab -->
  <div id="settings-tab" class="tab-content hidden">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
      <h2 class="text-xl font-bold text-gray-900 mb-6">إعدادات الكورس</h2>
      
      <form class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          
          <!-- Course Title -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">عنوان الكورس</label>
            <input type="text" value="{{ $course->title }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>

          <!-- Max Students -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">الحد الأقصى للطلاب</label>
            <input type="number" value="{{ $course->max_students }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>

          <!-- Duration -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">مدة الكورس (بالأسابيع)</label>
            <input type="number" value="{{ $course->duration_weeks }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
          </div>

          <!-- Status -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">حالة الكورس</label>
            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
              <option value="upcoming" {{ $course->status === 'upcoming' ? 'selected' : '' }}>قادم</option>
              <option value="active" {{ $course->status === 'active' ? 'selected' : '' }}>نشط</option>
              <option value="completed" {{ $course->status === 'completed' ? 'selected' : '' }}>مكتمل</option>
              <option value="cancelled" {{ $course->status === 'cancelled' ? 'selected' : '' }}>ملغي</option>
            </select>
          </div>
        </div>

        <!-- Course Description -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">وصف الكورس</label>
          <textarea rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">{{ $course->description }}</textarea>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end space-x-4 space-x-reverse pt-6">
          <button type="button" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-lg">
            إلغاء
          </button>
          <button type="submit" class="bg-primary hover:bg-primary-dark text-white font-medium py-2 px-6 rounded-lg">
            حفظ التغييرات
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tab Switching Script -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const tabButtons = document.querySelectorAll('.tab-button');
      const tabContents = document.querySelectorAll('.tab-content');

      tabButtons.forEach(button => {
        button.addEventListener('click', () => {
          const targetTab = button.getAttribute('data-tab');
          
          // Remove active class from all buttons
          tabButtons.forEach(btn => {
            btn.classList.remove('active', 'border-primary', 'text-primary');
            btn.classList.add('border-transparent', 'text-gray-500');
          });
          
          // Add active class to clicked button
          button.classList.add('active', 'border-primary', 'text-primary');
          button.classList.remove('border-transparent', 'text-gray-500');
          
          // Hide all tab contents
          tabContents.forEach(content => {
            content.classList.add('hidden');
          });
          
          // Show target tab content
          document.getElementById(targetTab + '-tab').classList.remove('hidden');
        });
      });
    });
  </script>

</x-layouts.teacher>
