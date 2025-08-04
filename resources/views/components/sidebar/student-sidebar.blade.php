<!-- Student Sidebar Component -->
<aside class="fixed right-0 top-20 h-screen w-80 bg-white shadow-lg border-l border-gray-200 overflow-y-auto z-40 transform translate-x-full md:translate-x-0 transition-transform duration-300" 
       role="complementary" 
       aria-label="قائمة الطالب الجانبية">
  
  <!-- Profile Section -->
  <div class="p-6 border-b border-gray-200">
    <div class="flex items-center space-x-4 space-x-reverse">
      <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center">
        <span class="text-white text-xl font-bold">
          {{ substr(auth()->user()->studentProfile->first_name ?? auth()->user()->name, 0, 1) }}
        </span>
      </div>
      <div class="flex-1">
        <h3 class="text-lg font-semibold text-gray-900">
          {{ auth()->user()->studentProfile->first_name ?? auth()->user()->name }}
        </h3>
        <p class="text-sm text-gray-500">
          {{ auth()->user()->studentProfile->student_code ?? 'طالب' }}
        </p>
        <p class="text-xs text-gray-400">
          {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}
        </p>
      </div>
    </div>
    
    <!-- Quick Stats -->
    <div class="mt-4 grid grid-cols-3 gap-4">
      <div class="text-center">
        <div class="text-lg font-bold text-primary">{{ $quranCirclesCount ?? 2 }}</div>
        <div class="text-xs text-gray-500">دوائر القرآن</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-bold text-green-600">{{ $activeCoursesCount ?? 3 }}</div>
        <div class="text-xs text-gray-500">الكورسات النشطة</div>
      </div>
      <div class="text-center">
        <div class="text-lg font-bold text-blue-600">{{ $completedLessonsCount ?? 15 }}</div>
        <div class="text-xs text-gray-500">الدروس المكتملة</div>
      </div>
    </div>
  </div>

  <!-- Navigation Menu -->
  <nav class="p-4" role="navigation" aria-label="قائمة التنقل الشخصية">
    <div class="space-y-2">
      <!-- Profile Management -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">إدارة الملف الشخصي</h4>
        <div class="space-y-1">
                     <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
              class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.profile') ? 'bg-primary text-white' : '' }}">
            <i class="ri-user-line ml-3"></i>
            الملف الشخصي
          </a>
                     <a href="{{ route('student.profile.edit', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
              class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
             <i class="ri-edit-line ml-3"></i>
             تعديل الملف الشخصي
           </a>
                     <a href="{{ route('student.settings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
              class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
             <i class="ri-settings-3-line ml-3"></i>
             الإعدادات
           </a>
        </div>
      </div>

      <!-- Learning Progress -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">التقدم الدراسي</h4>
        <div class="space-y-1">
          <a href="{{ route('student.quran', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.quran') ? 'bg-primary text-white' : '' }}">
            <i class="ri-book-mark-line ml-3"></i>
            ملف القرآن الكريم
          </a>
          <a href="{{ route('student.recorded-courses', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.recorded-courses') ? 'bg-primary text-white' : '' }}">
            <i class="ri-video-line ml-3"></i>
            الكورسات المسجلة
          </a>
                     <a href="{{ route('student.progress', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
              class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
             <i class="ri-bar-chart-line ml-3"></i>
             تقرير التقدم
           </a>
                     <a href="{{ route('student.certificates', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
              class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
             <i class="ri-medal-line ml-3"></i>
             الشهادات
           </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-bookmark-line ml-3"></i>
            المفضلة
          </a>
        </div>
      </div>

      <!-- Subscriptions & Payments -->
      <div class="mb-6">
        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">الاشتراكات والمدفوعات</h4>
        <div class="space-y-1">
                     <a href="{{ route('student.subscriptions', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
              class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
             <i class="ri-wallet-3-line ml-3"></i>
             الاشتراكات النشطة
           </a>
                     <a href="{{ route('student.payments', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
              class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
             <i class="ri-bill-line ml-3"></i>
             سجل المدفوعات
           </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-credit-card-line ml-3"></i>
            طرق الدفع
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
            <i class="ri-calendar-line ml-3"></i>
            الجدول الدراسي
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-customer-service-2-line ml-3"></i>
            الدعم الفني
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
            <i class="ri-feedback-line ml-3"></i>
            إرسال ملاحظة
          </a>
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-information-line ml-3"></i>
            حول المنصة
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Quick Actions -->
  <div class="p-4 border-t border-gray-200">
    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">إجراءات سريعة</h4>
    <div class="space-y-2">
      <button class="w-full bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
        <i class="ri-add-line ml-2"></i>
        انضم لدورة جديدة
      </button>
      <button class="w-full bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
        <i class="ri-calendar-check-line ml-2"></i>
        حجز درس خاص
      </button>
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