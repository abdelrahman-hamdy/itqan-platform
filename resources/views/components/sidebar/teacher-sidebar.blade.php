<!-- Teacher Sidebar Component -->
<aside class="fixed right-0 top-20 h-screen w-80 bg-white shadow-lg border-l border-gray-200 overflow-y-auto z-40 transform translate-x-full md:translate-x-0 transition-transform duration-300" 
       role="complementary" 
       aria-label="قائمة المعلم الجانبية">
  
  <!-- Profile Section -->
  <div class="p-6 border-b border-gray-200">
    <div class="flex items-center space-x-4 space-x-reverse">
      <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center">
        <span class="text-white text-xl font-bold">
          @if(auth()->user()->isQuranTeacher())
            {{ substr(auth()->user()->quranTeacherProfile->first_name ?? auth()->user()->name, 0, 1) }}
          @else
            {{ substr(auth()->user()->academicTeacherProfile->first_name ?? auth()->user()->name, 0, 1) }}
          @endif
        </span>
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-gray-900">
          @if(auth()->user()->isQuranTeacher())
            {{ auth()->user()->quranTeacherProfile->first_name ?? auth()->user()->name }}
          @else
            {{ auth()->user()->academicTeacherProfile->first_name ?? auth()->user()->name }}
          @endif
        </h3>
        <p class="text-sm text-gray-500">
          @if(auth()->user()->isQuranTeacher())
            {{ auth()->user()->quranTeacherProfile->teacher_code ?? 'معلم قرآن' }}
          @else
            {{ auth()->user()->academicTeacherProfile->teacher_code ?? 'معلم أكاديمي' }}
          @endif
        </p>
        <p class="text-xs text-gray-400">
          {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}
        </p>
      </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="mt-4 grid grid-cols-3 gap-4">
      <div class="text-center">
        <div class="text-lg font-bold text-primary">{{ $totalStudents ?? 15 }}</div>
        <div class="text-xs text-gray-500">طالب</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-bold text-green-600">{{ $thisMonthSessions ?? 42 }}</div>
        <div class="text-xs text-gray-500">جلسة هذا الشهر</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-bold text-yellow-600">{{ number_format($monthlyEarnings ?? 4200, 0) }}</div>
        <div class="text-xs text-gray-500">ريال شهرياً</div>
      </div>
    </div>
  </div>

  <!-- Navigation Menu -->
  <nav class="p-4" role="navigation" aria-label="قائمة التنقل الشخصية">
    <div class="space-y-2">
      
      <!-- Dashboard Access -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">لوحة التحكم</h4>
        <div class="space-y-1">
          <a href="/teacher-panel" target="_blank"
             class="flex items-center px-3 py-2 text-sm text-white bg-primary rounded-lg hover:bg-secondary transition-colors">
            <i class="ri-dashboard-3-line ml-3"></i>
            لوحة التحكم المتقدمة
            <i class="ri-external-link-line mr-auto text-sm"></i>
          </a>
        </div>
      </div>

      <!-- Profile Management -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">إدارة الملف الشخصي</h4>
        <div class="space-y-1">
          <a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.profile') ? 'bg-primary text-white' : '' }}">
            <i class="ri-user-line ml-3"></i>
            الملف الشخصي
          </a>
          <a href="{{ route('teacher.profile.edit', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-edit-line ml-3"></i>
            تعديل الملف الشخصي
          </a>
          <a href="{{ route('teacher.settings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-settings-3-line ml-3"></i>
            الإعدادات
          </a>
        </div>
      </div>

      <!-- Teaching Management -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">إدارة التدريس</h4>
        <div class="space-y-1">
          <a href="{{ route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.students') ? 'bg-primary text-white' : '' }}">
            <i class="ri-group-line ml-3"></i>
            @if(auth()->user()->isQuranTeacher())
              طلاب الدوائر
            @else
              طلاب الدورات
            @endif
          </a>
          <a href="{{ route('teacher.schedule', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.schedule') ? 'bg-primary text-white' : '' }}">
            <i class="ri-calendar-line ml-3"></i>
            جدول المواعيد
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-time-line ml-3"></i>
            @if(auth()->user()->isQuranTeacher())
              جلسات القرآن
            @else
              الجلسات والدروس
            @endif
          </a>
          @if(auth()->user()->isAcademicTeacher())
            <a href="#" 
               class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
              <i class="ri-file-list-3-line ml-3"></i>
              الواجبات والاختبارات
            </a>
          @endif
        </div>
      </div>

      <!-- Financial Management -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">الإدارة المالية</h4>
        <div class="space-y-1">
          <a href="{{ route('teacher.earnings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.earnings') ? 'bg-primary text-white' : '' }}">
            <i class="ri-money-dollar-circle-line ml-3"></i>
            الأرباح الشهرية
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-file-chart-line ml-3"></i>
            تقارير مالية
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-bank-card-line ml-3"></i>
            بيانات الحساب البنكي
          </a>
        </div>
      </div>

      <!-- Communication -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">التواصل</h4>
        <div class="space-y-1">
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-message-3-line ml-3"></i>
            الرسائل
            <span class="mr-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">2</span>
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-notification-3-line ml-3"></i>
            الإشعارات
            <span class="mr-auto bg-blue-500 text-white text-xs rounded-full px-2 py-1">5</span>
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-customer-service-2-line ml-3"></i>
            الدعم الفني
          </a>
        </div>
      </div>

      <!-- Reports & Analytics -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">التقارير والتحليلات</h4>
        <div class="space-y-1">
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-bar-chart-line ml-3"></i>
            تقرير الأداء
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-line-chart-line ml-3"></i>
            @if(auth()->user()->isQuranTeacher())
              تقدم الطلاب في الحفظ
            @else
              تقدم الطلاب الأكاديمي
            @endif
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-star-line ml-3"></i>
            التقييمات والمراجعات
          </a>
        </div>
      </div>

      <!-- Help & Support -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">المساعدة والدعم</h4>
        <div class="space-y-1">
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-question-line ml-3"></i>
            الأسئلة الشائعة
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-book-open-line ml-3"></i>
            دليل المعلم
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-feedback-line ml-3"></i>
            إرسال ملاحظة
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Quick Actions -->
  <div class="p-4 border-t border-gray-200">
    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">إجراءات سريعة</h4>
    <div class="space-y-2">
      @if(auth()->user()->isAcademicTeacher())
        <button class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
          <i class="ri-add-line ml-2"></i>
          إنشاء دورة جديدة
        </button>
        <button class="w-full bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
          <i class="ri-file-add-line ml-2"></i>
          إضافة واجب
        </button>
      @else
        <button class="w-full bg-purple-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-purple-700 transition-colors">
          <i class="ri-calendar-check-line ml-2"></i>
          جدولة جلسة قرآن
        </button>
        <button class="w-full bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
          <i class="ri-user-add-line ml-2"></i>
          إضافة طالب للدائرة
        </button>
      @endif
    </div>
  </div>
</aside>

<!-- Mobile Sidebar Overlay -->
<div class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden" id="sidebar-overlay"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mobile sidebar toggle
  const sidebarToggle = document.getElementById('sidebar-toggle');
  const sidebar = document.querySelector('aside');
  const overlay = document.getElementById('sidebar-overlay');
  
  sidebarToggle?.addEventListener('click', function() {
    sidebar.classList.toggle('translate-x-full');
    overlay.classList.toggle('hidden');
  });
  
  overlay?.addEventListener('click', function() {
    sidebar.classList.add('translate-x-full');
    overlay.classList.add('hidden');
  });
});
</script>