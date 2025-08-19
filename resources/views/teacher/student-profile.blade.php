<x-layouts.teacher 
    :title="'ملف الطالب - ' . $student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'ملف الطالب: ' . $student->name">

<div class="p-6">
    <!-- Breadcrumbs -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 space-x-reverse">
            <li class="inline-flex items-center">
                <a href="{{ route('teacher.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                    <i class="ri-home-line ml-2"></i>
                    الرئيسية
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="ri-arrow-left-s-line text-gray-400"></i>
                    <a href="{{ route('teacher.students', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="text-sm font-medium text-gray-700 hover:text-blue-600">الطلاب</a>
                </div>
            </li>
            <li aria-current="page">
                <div class="flex items-center">
                    <i class="ri-arrow-left-s-line text-gray-400"></i>
                    <span class="text-sm font-medium text-gray-500">{{ $student->name }}</span>
                </div>
            </li>
        </ol>
    </nav>
    <!-- Student Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6 space-x-reverse">
                <x-student-avatar :student="$student" size="xl" />
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $student->name }}</h1>
                    @if($student->studentProfile && $student->studentProfile->birth_date)
                        <div class="mt-2 space-y-1">
                            <p class="text-sm text-gray-500">
                                <i class="ri-calendar-line ml-1"></i>
                                تاريخ الميلاد: {{ $student->studentProfile->birth_date->format('Y-m-d') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Message Button -->
            <div>
                <button class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="ri-message-line ml-2"></i>
                    إرسال رسالة
                </button>
            </div>
        </div>
    </div>



    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Circles & Progress -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Circles -->
            @if(count($progressData['circles']) > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">الحلقات المُسجل بها</h3>
                    <div class="space-y-4">
                        @foreach($progressData['circles'] as $circle)
                            @php
                                $circleRoute = $circle['type'] === 'individual' 
                                    ? route('teacher.individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle['id']]) 
                                    : route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle['id']]);
                            @endphp
                            <a href="{{ $circleRoute }}" class="block border border-gray-200 rounded-lg p-4 hover:border-primary-300 hover:bg-gray-50 transition-colors">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-3 space-x-reverse">
                                        <div class="w-3 h-3 rounded-full {{ $circle['type'] === 'individual' ? 'bg-blue-500' : 'bg-green-500' }}"></div>
                                        <h4 class="font-medium text-gray-900">{{ $circle['name'] }}</h4>
                                        <span class="text-sm px-2 py-1 rounded-full {{ $circle['type'] === 'individual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                                            {{ $circle['type'] === 'individual' ? 'فردية' : 'جماعية' }}
                                        </span>
                                        <i class="ri-external-link-line text-gray-400 text-sm"></i>
                                    </div>
                                    
                                    @if(isset($circle['progress_percentage']))
                                        <span class="text-sm font-medium text-gray-600">{{ $circle['progress_percentage'] }}%</span>
                                    @endif
                                </div>
                                
                                @if(isset($circle['progress_percentage']))
                                    <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $circle['progress_percentage'] }}%"></div>
                                    </div>
                                @endif
                                
                                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                    @if(isset($circle['sessions_completed']))
                                        <div>الجلسات المكتملة: {{ $circle['sessions_completed'] }}/{{ $circle['total_sessions'] ?? 0 }}</div>
                                    @endif
                                    @if(isset($circle['current_surah']))
                                        <div>السورة الحالية: {{ $circle['current_surah'] }}</div>
                                    @endif
                                    @if(isset($circle['verses_memorized']))
                                        <div>الآيات المحفوظة: {{ $circle['verses_memorized'] }}</div>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Student Information -->
            @if($student->studentProfile)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات الطالب</h3>
                    <div class="space-y-3">
                        @if($student->studentProfile->grade_level_id)
                            <div class="flex justify-between">
                                <span class="text-gray-600">المستوى الدراسي:</span>
                                <span class="font-medium">{{ $student->studentProfile->gradeLevel->name ?? 'غير محدد' }}</span>
                            </div>
                        @endif
                        @if($student->studentProfile->gender)
                            <div class="flex justify-between">
                                <span class="text-gray-600">الجنس:</span>
                                <span class="font-medium">{{ $student->studentProfile->gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
                            </div>
                        @endif
                        @if($student->studentProfile->nationality)
                            <div class="flex justify-between">
                                <span class="text-gray-600">الجنسية:</span>
                                <span class="font-medium">{{ $student->studentProfile->nationality }}</span>
                            </div>
                        @endif
                        @if($student->studentProfile->enrollment_date)
                            <div class="flex justify-between">
                                <span class="text-gray-600">تاريخ التسجيل:</span>
                                <span class="font-medium">{{ $student->studentProfile->enrollment_date->format('Y-m-d') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Recent Activity -->
            @if(count($progressData['recentActivity']) > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">النشاط الأخير</h3>
                    <div class="space-y-3">
                        @foreach(array_slice($progressData['recentActivity'], 0, 5) as $activity)
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">{{ $activity['title'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $activity['circle_name'] }}</p>
                                    <p class="text-xs text-gray-400">{{ $activity['date']->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif


        </div>
    </div>
</div>

</x-layouts.teacher>
