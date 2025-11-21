<!-- Teacher Sidebar Component -->
<aside class="fixed right-0 top-20 h-screen w-80 bg-white shadow-lg border-l border-t border-gray-200 overflow-y-auto z-50 transform translate-x-full md:translate-x-0 transition-transform duration-300" 
       role="complementary" 
       aria-label="قائمة المعلم الجانبية">
  
  <!-- Profile Section -->
  <div class="p-6 border-b border-gray-200 bg-gradient-to-br from-gray-50 to-gray-100/50">
    <div class="flex flex-col items-center text-center mb-4">
      @php
        $teacher = auth()->user()->isQuranTeacher()
                  ? auth()->user()->quranTeacherProfile
                  : auth()->user()->academicTeacherProfile;
        $teacherType = auth()->user()->isQuranTeacher() ? 'quran_teacher' : 'academic_teacher';
        $teacherGender = $teacher?->gender ?? auth()->user()?->gender ?? 'male';
      @endphp

      <x-avatar
        :user="auth()->user()"
        size="md"
        :userType="$teacherType"
        :gender="$teacherGender"
        class="mb-3" />
      
      <div>
        <h3 class="text-lg font-semibold text-gray-900">
          @if(auth()->user()->isQuranTeacher())
            {{ auth()->user()->quranTeacherProfile->full_name ?? auth()->user()->name }}
          @else
            {{ auth()->user()->academicTeacherProfile->full_name ?? auth()->user()->name }}
          @endif
        </h3>
        <p class="text-xs text-gray-400">
          @if(auth()->user()->isQuranTeacher())
            {{ auth()->user()->quranTeacherProfile->teacher_code ?? 'معلم قرآن' }}
          @else
            {{ auth()->user()->academicTeacherProfile->teacher_code ?? 'معلم أكاديمي' }}
          @endif
        </p>
      </div>
    </div>
    
    <!-- Teacher Info -->
    <div class="space-y-2 text-sm">
      <div class="flex items-center justify-center text-gray-600">
        <i class="ri-phone-line ml-2 text-gray-400"></i>
        <span>{{ $teacher->phone ?? 'غير محدد' }}</span>
      </div>
      <div class="flex items-center justify-center text-gray-600">
        <i class="ri-mail-line ml-2 text-gray-400"></i>
        <span class="truncate">{{ auth()->user()->email }}</span>
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
          <a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.profile') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-user-line ml-3"></i>
            الملف الشخصي
          </a>
          <a href="{{ route('teacher.profile.edit', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
            <i class="ri-edit-line ml-3"></i>
            تعديل الملف الشخصي
          </a>
        </div>
      </div>

      <!-- Teaching Management -->
      <div class="mb-6">
        <h4 class="text-xs font-medium text-gray-400 mb-3">إدارة التدريس</h4>
        <div class="space-y-1">
          <a href="{{ route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.students') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-group-line ml-3"></i>
            @if(auth()->user()->isQuranTeacher())
              طلاب الحلقات
            @else
              طلاب الدورات
            @endif
          </a>
          {{-- Frontend calendar removed - use Filament dashboard calendar instead --}}
          <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}"
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.schedule.*') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-calendar-schedule-line ml-3"></i>
            @if(auth()->user()->isQuranTeacher())
              الجدول والمواعيد
            @else
              جدول المواعيد
            @endif
          </a>
          @if(auth()->user()->isQuranTeacher())
            <a href="/teacher-panel/quran-trial-requests" target="_blank"
               class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
              <i class="ri-user-add-line ml-3"></i>
              طلبات الجلسات التجريبية
              <i class="ri-external-link-line mr-auto text-xs"></i>
            </a>
            <a href="/teacher-panel/quran-subscriptions" target="_blank"
               class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
              <i class="ri-book-open-line ml-3"></i>
              اشتراكات طلابي
              <i class="ri-external-link-line mr-auto text-xs"></i>
            </a>
            <a href="/teacher-panel/quran-sessions" target="_blank"
               class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
              <i class="ri-time-line ml-3"></i>
              جلسات القرآن
              <i class="ri-external-link-line mr-auto text-xs"></i>
            </a>
          @else
            <a href="#" 
               class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors">
              <i class="ri-time-line ml-3"></i>
              الجلسات والدروس
            </a>
          @endif
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
        <h4 class="text-xs font-medium text-gray-400 mb-3">الإدارة المالية</h4>
        <div class="space-y-1">
          <a href="{{ route('teacher.earnings', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->routeIs('teacher.earnings') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-money-dollar-circle-line ml-3"></i>
            الأرباح الشهرية
          </a>
        </div>
      </div>

      <!-- Reports & Analytics -->
      <div class="mb-6">
        <h4 class="text-xs font-medium text-gray-400 mb-3">التقارير والتحليلات</h4>
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

      <!-- Communication -->
      <div class="mb-6">
        <h4 class="text-xs font-medium text-gray-400 mb-3">التواصل</h4>
        <div class="space-y-1">
          <a href="/chat"
             class="flex items-center px-3 py-2 text-sm text-gray-700 rounded-lg hover:bg-gray-50 hover:text-primary transition-colors {{ request()->is('chat*') ? 'bg-gray-100 text-primary' : '' }}">
            <i class="ri-message-3-line ml-3"></i>
            الرسائل والمحادثات
          </a>
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