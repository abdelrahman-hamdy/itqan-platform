<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - {{ $course->title }}</title>
  <meta name="description" content="{{ $course->description }} - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ auth()->user()->academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ auth()->user()->academy->secondary_color ?? '#6495ED' }}",
          },
          borderRadius: {
            none: "0px",
            sm: "4px",
            DEFAULT: "8px",
            md: "12px",
            lg: "16px",
            xl: "20px",
            "2xl": "24px",
            "3xl": "32px",
            full: "9999px",
            button: "8px",
          },
        },
      },
    };
  </script>
</head>

<body class="bg-gray-50 text-gray-900">
  <!-- Navigation -->
  @include('components.navigation.student-nav')
  
  <!-- Sidebar -->
  @include('components.sidebar.student-sidebar')

  <!-- Main Content -->
  <main class="transition-all duration-300 pt-20 min-h-screen" id="main-content" style="margin-right: 320px;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      
      <!-- Back Button -->
      <div class="mb-6">
        <a href="{{ route('student.interactive-courses', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
           class="inline-flex items-center text-gray-600 hover:text-primary transition-colors">
          <i class="ri-arrow-right-line ml-2"></i>
          العودة للكورسات التفاعلية
        </a>
      </div>

      <!-- Course Header -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8">
        <div class="flex items-start justify-between mb-6">
          <div class="flex-1">
            <div class="flex items-center mb-4">
              <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center ml-4">
                <i class="ri-book-open-line text-purple-600 text-2xl"></i>
              </div>
              <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $course->title }}</h1>
                <p class="text-lg text-gray-600">{{ $course->description }}</p>
              </div>
            </div>
          </div>
          <div class="text-right">
            @if($isEnrolled)
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-green-100 text-green-800">
              <i class="ri-check-line ml-1"></i>
              مسجل في الكورس
            </span>
            @else
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium 
              {{ $course->status === 'published' && $course->is_published ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
              {{ $course->status === 'published' && $course->is_published ? 'متاح للتسجيل' : 'غير متاح' }}
            </span>
            @endif
          </div>
        </div>

        <!-- Course Info Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          @if($course->assignedTeacher)
          <div class="flex items-center">
            <i class="ri-user-star-line text-primary text-xl ml-3"></i>
            <div>
              <p class="text-sm text-gray-600">المعلم</p>
              <p class="font-semibold">{{ $course->assignedTeacher->full_name }}</p>
            </div>
          </div>
          @endif
          
          @if($course->subject)
          <div class="flex items-center">
            <i class="ri-bookmark-line text-primary text-xl ml-3"></i>
            <div>
              <p class="text-sm text-gray-600">المادة</p>
              <p class="font-semibold">{{ $course->subject->name }}</p>
            </div>
          </div>
          @endif
          
          @if($course->gradeLevel)
          <div class="flex items-center">
            <i class="ri-graduation-cap-line text-primary text-xl ml-3"></i>
            <div>
              <p class="text-sm text-gray-600">المستوى</p>
              <p class="font-semibold">{{ $course->gradeLevel->name }}</p>
            </div>
          </div>
          @endif
          
          @if($course->student_price)
          <div class="flex items-center">
            <i class="ri-money-dollar-circle-line text-primary text-xl ml-3"></i>
            <div>
              <p class="text-sm text-gray-600">السعر</p>
              <p class="font-semibold">{{ $course->student_price }} ر.س</p>
            </div>
          </div>
          @endif
        </div>
      </div>

      <!-- Course Details Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column - Course Info -->
        <div class="lg:col-span-2 space-y-8">
          
          <!-- Course Overview -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">نظرة عامة على الكورس</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="text-center p-4 bg-gray-50 rounded-lg">
                <i class="ri-calendar-line text-2xl text-primary mb-2"></i>
                <p class="text-sm text-gray-600">مدة الكورس</p>
                <p class="font-semibold">{{ $course->duration_weeks }} أسبوع</p>
              </div>
              <div class="text-center p-4 bg-gray-50 rounded-lg">
                <i class="ri-time-line text-2xl text-primary mb-2"></i>
                <p class="text-sm text-gray-600">الجلسات</p>
                <p class="font-semibold">{{ $course->total_sessions }} جلسة</p>
              </div>
              <div class="text-center p-4 bg-gray-50 rounded-lg">
                <i class="ri-group-line text-2xl text-primary mb-2"></i>
                <p class="text-sm text-gray-600">عدد الطلاب</p>
                <p class="font-semibold">{{ $enrollmentStats['total_enrolled'] }}/{{ $course->max_students }}</p>
              </div>
            </div>
          </div>

          <!-- Course Schedule -->
          @if($course->schedule && is_array($course->schedule))
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">جدول الكورس</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              @foreach($course->schedule as $day => $time)
              <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                <span class="font-medium text-gray-900">{{ $day }}</span>
                <span class="text-blue-600 font-semibold">{{ $time }}</span>
              </div>
              @endforeach
            </div>
          </div>
          @endif

          {{-- Course Sessions Section --}}
          @if($isEnrolled && ($upcomingSessions->count() > 0 || $pastSessions->count() > 0))
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
              <i class="ri-calendar-event-line text-primary ml-2"></i>
              جلسات الكورس
            </h2>

            {{-- Alpine.js Tabs Component --}}
            <div x-data="{ activeTab: 'upcoming' }" class="w-full">
              {{-- Tab Buttons --}}
              <div class="flex border-b border-gray-200 mb-6">
                <button
                  @click="activeTab = 'upcoming'"
                  :class="activeTab === 'upcoming' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                  class="px-6 py-3 border-b-2 font-medium transition-colors">
                  <i class="ri-calendar-check-line ml-1"></i>
                  الجلسات القادمة ({{ $upcomingSessions->count() }})
                </button>
                <button
                  @click="activeTab = 'past'"
                  :class="activeTab === 'past' ? 'border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700'"
                  class="px-6 py-3 border-b-2 font-medium transition-colors">
                  <i class="ri-history-line ml-1"></i>
                  الجلسات السابقة ({{ $pastSessions->count() }})
                </button>
              </div>

              {{-- Upcoming Sessions Tab Content --}}
              <div x-show="activeTab === 'upcoming'" class="space-y-4">
                @forelse($upcomingSessions as $session)
                  @php
                    $attendance = $session->attendances->where('student_id', $student->id ?? null)->first();
                  @endphp
                  <x-interactive.session-card :session="$session" :attendance="$attendance" />
                @empty
                  <div class="text-center py-12 text-gray-500">
                    <i class="ri-calendar-line text-5xl mb-4"></i>
                    <p class="text-lg font-medium">لا توجد جلسات قادمة</p>
                    <p class="text-sm mt-2">سيتم إضافة الجلسات القادمة قريباً</p>
                  </div>
                @endforelse
              </div>

              {{-- Past Sessions Tab Content --}}
              <div x-show="activeTab === 'past'" class="space-y-4">
                @forelse($pastSessions as $session)
                  @php
                    $attendance = $session->attendances->where('student_id', $student->id ?? null)->first();
                  @endphp
                  <x-interactive.session-card :session="$session" :attendance="$attendance" />
                @empty
                  <div class="text-center py-12 text-gray-500">
                    <i class="ri-time-line text-5xl mb-4"></i>
                    <p class="text-lg font-medium">لا توجد جلسات سابقة بعد</p>
                    <p class="text-sm mt-2">ابدأ بحضور الجلسات القادمة</p>
                  </div>
                @endforelse
              </div>
            </div>
          </div>
          @endif

        </div>

        <!-- Right Column - Enrollment Info -->
        <div class="space-y-6">
          
          <!-- Enrollment Card -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">معلومات التسجيل</h3>
            
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">المقاعد المتاحة</span>
                <span class="font-semibold text-green-600">{{ $enrollmentStats['available_spots'] }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-gray-600">آخر موعد للتسجيل</span>
                <span class="font-semibold">{{ $enrollmentStats['enrollment_deadline']->format('d/m/Y') }}</span>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-gray-600">تاريخ البدء</span>
                <span class="font-semibold">{{ $course->start_date->format('d/m/Y') }}</span>
              </div>
              
              @if($course->end_date)
              <div class="flex items-center justify-between">
                <span class="text-gray-600">تاريخ الانتهاء</span>
                <span class="font-semibold">{{ $course->end_date->format('d/m/Y') }}</span>
              </div>
              @endif
            </div>

            <!-- Enrollment Button -->
            <div class="mt-6">
              @if($isEnrolled)
              <button class="w-full bg-green-600 text-white px-4 py-3 rounded-lg font-medium">
                <i class="ri-check-line ml-1"></i>
                مسجل بالفعل
              </button>
              @else
                @if($course->status === 'published' && $course->is_published && $course->enrollment_deadline >= now()->toDateString() && $enrollmentStats['available_spots'] > 0)
                <button class="w-full bg-primary text-white px-4 py-3 rounded-lg font-medium hover:bg-secondary transition-colors">
                  <i class="ri-add-line ml-1"></i>
                  التسجيل في الكورس
                </button>
                @else
                <button class="w-full bg-gray-300 text-gray-500 px-4 py-3 rounded-lg font-medium cursor-not-allowed">
                  غير متاح للتسجيل
                </button>
                @endif
              @endif
            </div>
          </div>

          {{-- Progress Summary (only for enrolled students) --}}
          @if($isEnrolled && $student)
            <x-interactive.progress-summary
              :courseId="$course->id"
              :studentId="$student->id"
            />
          @endif

        </div>

      </div>

    </div>
  </main>

  <!-- Mobile Sidebar Toggle -->
  <button id="sidebar-toggle" class="fixed bottom-6 right-6 md:hidden bg-primary text-white p-3 rounded-full shadow-lg z-50">
    <i class="ri-menu-line text-xl"></i>
  </button>

</body>
</html>
