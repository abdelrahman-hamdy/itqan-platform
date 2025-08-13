<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $circle->name }} - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
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
  @if($userRole === 'teacher')
    @include('components.navigation.teacher-nav')
    @include('components.sidebar.teacher-sidebar')
  @else
    @include('components.navigation.student-nav')
  @endif

  <!-- Main Content -->
  <main class="{{ $userRole === 'teacher' ? 'mr-80 pt-20' : 'pt-20' }} min-h-screen" id="main-content">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Enhanced Header with Circle Info -->
      <div class="mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4 space-x-reverse">
              <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                <i class="ri-group-line text-3xl text-white"></i>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 flex items-center">
                  <i class="ri-group-line text-primary-600 ml-3"></i>
                  {{ $circle->name }}
                </h1>
                <p class="text-gray-600 mt-1">
                  {{ $circle->description ?? 'حلقة قرآنية جماعية' }}
                </p>
                <div class="flex items-center space-x-3 space-x-reverse mt-2">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                      {{ $circle->status === 'active' ? 'bg-green-100 text-green-800' : 
                         ($circle->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                      {{ $circle->status === 'active' ? 'نشط' : 
                         ($circle->status === 'pending' ? 'في الانتظار' : 
                         ($circle->status === 'completed' ? 'مكتمل' : $circle->status)) }}
                  </span>
                  @if($circle->schedule_days_text)
                      <span class="text-sm text-gray-500">{{ $circle->schedule_days_text }}</span>
                  @endif
                </div>
              </div>
            </div>
            
            <div class="text-center">
              <div class="text-3xl font-bold text-primary-600">
                {{ $circle->students->count() }}
              </div>
              <div class="text-sm text-gray-600">
                طالب مسجل
              </div>
            </div>
          </div>
          
          <!-- Circle Details Section -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 pt-6 pb-4 border-t border-gray-200">
            <div>
              <span class="text-sm text-gray-600">المعلم:</span>
              <p class="font-medium text-gray-900">{{ $circle->quranTeacher->user->name ?? 'غير محدد' }}</p>
            </div>
            
            <div>
              <span class="text-sm text-gray-600">أيام الحلقة:</span>
              <p class="font-medium text-gray-900">{{ $circle->schedule_days_text ?? 'لم يتم تحديد الجدول بعد' }}</p>
            </div>
            
            @if($circle->schedule)
              <div>
                <span class="text-sm text-gray-600">وقت الحلقة:</span>
                <p class="font-medium text-gray-900">
                  {{ $circle->schedule->start_time ? \Carbon\Carbon::parse($circle->schedule->start_time)->format('H:i') : 'غير محدد' }}
                  -
                  {{ $circle->schedule->end_time ? \Carbon\Carbon::parse($circle->schedule->end_time)->format('H:i') : 'غير محدد' }}
                </p>
              </div>
            @endif
            
            <div>
              <span class="text-sm text-gray-600">السعة القصوى:</span>
              <p class="font-medium text-gray-900">{{ $circle->max_students ?? 'غير محدد' }} طالب</p>
            </div>
          </div>
          
          @if($userRole === 'teacher')
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
              <div class="flex items-center space-x-2 space-x-reverse">
                @if($circle->preferred_times && count($circle->preferred_times) > 0)
                  <span class="text-sm text-gray-600 ml-2">الأوقات المفضلة:</span>
                  @foreach($circle->preferred_times as $time)
                    <span class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">
                      {{ $time }}
                    </span>
                  @endforeach
                @endif
              </div>
              
              <div class="flex space-x-3 space-x-reverse">
                <a href="{{ route('teacher.group-circles.schedule', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}"
                   class="bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors">
                  <i class="ri-calendar-schedule-line ml-2"></i>
                  إدارة الجدول
                </a>
                <a href="{{ route('teacher.group-circles.progress', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}"
                   class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                  <i class="ri-line-chart-line ml-2"></i>
                  تقرير التقدم
                </a>
              </div>
            </div>
          @endif
          
          @if($circle->notes)
            <div class="pt-4 border-t border-gray-200">
              <span class="text-sm text-gray-600">ملاحظات:</span>
              <p class="mt-1 text-sm text-gray-700">{{ $circle->notes }}</p>
            </div>
          @endif
          
          @if($userRole === 'teacher' && $circle->teacher_notes)
            <div class="pt-4 border-t border-gray-200">
              <span class="text-sm text-gray-600">ملاحظات المعلم:</span>
              <p class="mt-1 text-sm text-gray-700">{{ $circle->teacher_notes }}</p>
            </div>
          @endif
        </div>
      </div>



      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Circle Sessions -->
        @php
          // Combine recent and upcoming sessions for unified display
          $allSessions = collect();
          if($userRole === 'teacher') {
            if(isset($teacherData['recentSessions'])) {
              $allSessions = $allSessions->merge($teacherData['recentSessions']);
            }
            if(isset($teacherData['upcomingSessions'])) {
              $allSessions = $allSessions->merge($teacherData['upcomingSessions']);
            }
          } else {
            // For students, get their specific sessions if available
            $allSessions = $circle->sessions ?? collect();
          }
        @endphp
        
        <div>
          <x-circle.progress-sessions-list 
            :sessions="$allSessions" 
            title="جلسات الحلقة الجماعية"
            subtitle="آخر الجلسات والقادمة"
            view-type="{{ $userRole }}"
            :limit="10"
            :show-all-button="true"
            empty-message="لا توجد جلسات مجدولة بعد" />
        </div>

        <!-- Students List -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
          <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-gray-900">
                <i class="ri-group-line text-primary ml-2"></i>
                الطلاب المسجلون
              </h3>
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                {{ $circle->students->count() }} طالب
              </span>
            </div>
          </div>
          
          <div class="p-6">
            @if($circle->students && $circle->students->count() > 0)
              <div class="space-y-3">
                @foreach($circle->students as $student)
                  <div class="flex items-center p-4 bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl hover:from-blue-50 hover:to-blue-100 transition-all duration-200 group">
                    <x-student-avatar :student="$student" size="md" />
                    <div class="mr-4 flex-1">
                      <h4 class="font-semibold text-gray-900 group-hover:text-blue-700 transition-colors">{{ $student->name }}</h4>
                      <p class="text-sm text-gray-500">{{ $student->email ?? 'طالب' }}</p>
                      @if($student->parent)
                        <p class="text-xs text-gray-400">ولي الأمر: {{ $student->parent->name }}</p>
                      @endif
                    </div>
                    
                    @if($userRole === 'teacher')
                      <div class="flex items-center space-x-2 space-x-reverse">
                        <button class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors" 
                                onclick="viewStudentProgress({{ $student->id }})"
                                title="عرض التقدم">
                          <i class="ri-line-chart-line"></i>
                        </button>
                        <button class="p-2 text-green-600 hover:bg-green-100 rounded-lg transition-colors" 
                                onclick="contactStudent({{ $student->id }})"
                                title="التواصل">
                          <i class="ri-message-line"></i>
                        </button>
                      </div>
                    @endif
                  </div>
                @endforeach
              </div>
              
              @if($circle->max_students && $circle->students->count() < $circle->max_students)
                <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
                  <div class="flex items-center">
                    <i class="ri-information-line text-green-600 ml-2"></i>
                    <span class="text-sm text-green-700">
                      يتوفر {{ $circle->max_students - $circle->students->count() }} مقعد إضافي في هذه الحلقة
                    </span>
                  </div>
                </div>
              @endif
            @else
              <div class="text-center py-12">
                <div class="mx-auto w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-4">
                  <i class="ri-user-add-line text-3xl text-gray-400"></i>
                </div>
                <h4 class="text-lg font-semibold text-gray-900 mb-2">لا يوجد طلاب مسجلون بعد</h4>
                <p class="text-gray-500 mb-4">ابدأ بدعوة الطلاب للانضمام إلى هذه الحلقة</p>
                @if($userRole === 'teacher')
                  <button class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-600 transition-colors">
                    <i class="ri-user-add-line ml-2"></i>
                    دعوة طلاب
                  </button>
                @endif
              </div>
            @endif
          </div>
        </div>



      </div>
    </div>
  </main>

  <script>
    function viewStudentProgress(studentId) {
      window.location.href = '{{ route("teacher.group-circles.student-progress", ["subdomain" => $academy->subdomain, "circle" => $circle->id, "student" => "__STUDENT_ID__"]) }}'.replace('__STUDENT_ID__', studentId);
    }

    function contactStudent(studentId) {
      const subdomain = '{{ $academy->subdomain }}';
      window.location.href = `/${subdomain}/teacher/students/${studentId}/contact`;
    }

    function openScheduleModal() {
      // This would open a scheduling modal for group sessions
      alert('سيتم تنفيذ جدولة الجلسات الجماعية قريباً');
    }

    // Add smooth scroll behavior for better UX
    document.addEventListener('DOMContentLoaded', function() {
      const links = document.querySelectorAll('a[href^="#"]');
      links.forEach(link => {
        link.addEventListener('click', function(e) {
          e.preventDefault();
          const target = document.querySelector(this.getAttribute('href'));
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'start'
            });
          }
        });
      });
    });
  </script>

</body>
</html>
