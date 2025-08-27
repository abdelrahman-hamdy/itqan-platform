<!-- Student Sidebar Component -->
<aside class="fixed right-0 top-20 h-screen w-80 bg-white shadow-lg border-l border-t border-gray-200 overflow-y-auto z-50 transform translate-x-full md:translate-x-0 transition-transform duration-300" 
       role="complementary" 
       aria-label="قائمة الطالب الجانبية">
  
  <!-- Profile Section -->
  <div class="p-6 border-b border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100/50">
    <div class="flex flex-col items-center text-center mb-4">
      @php
        $user = auth()->user();
        $student = $user ? $user->studentProfile : null;
        $fullName = $student ? 
                   ($student->first_name && $student->last_name ? $student->first_name . ' ' . $student->last_name : $student->first_name) :
                   ($user ? $user->name : 'زائر');
      @endphp
      
      <x-student-avatar :student="$student" size="md" class="mb-3" />
      
      <div>
        <h3 class="text-lg font-semibold text-gray-900">
          {{ $fullName }}
        </h3>
        <p class="text-xs text-gray-400">
          {{ $student->gradeLevel?->name ? 'المرحلة الدراسية: ' . $student->gradeLevel->name : ($student->student_code ?? 'طالب') }}
        </p>
      </div>
    </div>
    
    <!-- Student Info -->
    <div class="space-y-2 text-sm">
      <div class="flex items-center justify-center text-gray-600">
        <i class="ri-mail-line ml-2 text-gray-400"></i>
        <span class="truncate">{{ $user ? $user->email : 'غير مسجل الدخول' }}</span>
      </div>
    </div>
  </div>

  <!-- Navigation Menu -->
  <nav class="p-4" role="navigation" aria-label="قائمة التنقل الشخصية">
    <div class="space-y-2">
      
      <!-- Profile Management -->
      <div class="mb-6">
        <h4 class="text-xs font-medium text-gray-400 mb-3">إدارة الملف الشخصي</h4>
        <div class="space-y-1">
          <a href="{{ $user ? route('student.profile', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy']) : '#' }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.profile') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-user-line ml-3"></i>
            الملف الشخصي
          </a>
          <a href="{{ route('student.profile.edit', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-edit-line ml-3"></i>
            تعديل الملف الشخصي
          </a>

        </div>
      </div>

      <!-- Learning Progress -->
      <div class="mb-6">
        <h4 class="text-xs font-medium text-gray-400 mb-3">التقدم الدراسي</h4>
        <div class="space-y-1">
          <a href="{{ route('student.quran', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.quran') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-book-mark-line ml-3"></i>
            ملف القرآن الكريم
          </a>
          <a href="{{ route('courses.index', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('courses.index') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-video-line ml-3"></i>
            الكورسات المسجلة
          </a>
          <a href="{{ route('student.progress', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-bar-chart-line ml-3"></i>
            تقرير التقدم
          </a>
          <a href="{{ route('student.certificates', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-medal-line ml-3"></i>
            الشهادات
          </a>
          <a href="{{ route('student.calendar', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('student.calendar') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-calendar-line ml-3"></i>
            التقويم والجلسات
          </a>
        </div>
      </div>

      <!-- Subscriptions & Payments -->
      <div class="mb-6">
        <h4 class="text-xs font-medium text-gray-400 mb-3">الاشتراكات والمدفوعات</h4>
        <div class="space-y-1">
          <a href="{{ route('student.subscriptions', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-wallet-3-line ml-3"></i>
            الاشتراكات النشطة
          </a>
          <a href="{{ route('student.payments', ['subdomain' => ($user && $user->academy) ? $user->academy->subdomain : 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-bill-line ml-3"></i>
            سجل المدفوعات
          </a>
        </div>
      </div>

      <!-- Communication -->
      <div class="mb-6">
        <h4 class="text-xs font-medium text-gray-400 mb-3">التواصل</h4>
        <div class="space-y-1">
          <a href="#" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-customer-service-2-line ml-3"></i>
            الدعم الفني
          </a>
        </div>
      </div>

    </div>
  </nav>


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