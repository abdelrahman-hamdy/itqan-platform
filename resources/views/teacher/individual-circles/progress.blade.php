<x-layouts.teacher 
    :title="'تقرير التقدم - ' . $circle->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تقرير التقدم للطالب: ' . $circle->student->name">

<div class="p-6">
    <!-- Header with Student Info -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <a href="{{ route('teacher.individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="text-primary-600 hover:text-primary-800">
                    <i class="ri-arrow-right-line text-xl"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">تقرير تقدم الطالب</h1>
                    <p class="text-gray-600">{{ $circle->student->name }} - {{ $circle->subscription->package->name ?? 'اشتراك مخصص' }}</p>
                </div>
            </div>
            
            <div class="flex items-center space-x-2 space-x-reverse">
                <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="ri-printer-line ml-2"></i>
                    طباعة التقرير
                </button>
                <a href="{{ route('teacher.students.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $circle->student->id]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="ri-user-line ml-2"></i>
                    ملف الطالب
                </a>
            </div>
        </div>
    </div>

    <!-- Progress Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
        <div class="bg-blue-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600">إجمالي الجلسات</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $stats['total_sessions'] }}</p>
                </div>
                <i class="ri-book-line text-2xl text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-green-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600">المكتملة</p>
                    <p class="text-2xl font-bold text-green-900">{{ $stats['completed_sessions'] }}</p>
                </div>
                <i class="ri-checkbox-circle-line text-2xl text-green-500"></i>
            </div>
        </div>
        
        <div class="bg-orange-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-600">المجدولة</p>
                    <p class="text-2xl font-bold text-orange-900">{{ $stats['scheduled_sessions'] }}</p>
                </div>
                <i class="ri-calendar-check-line text-2xl text-orange-500"></i>
            </div>
        </div>
        
        <div class="bg-purple-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600">المتبقية</p>
                    <p class="text-2xl font-bold text-purple-900">{{ $stats['remaining_sessions'] }}</p>
                </div>
                <i class="ri-time-line text-2xl text-purple-500"></i>
            </div>
        </div>
        
        <div class="bg-red-50 rounded-lg p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-red-600">معدل الحضور</p>
                    <p class="text-2xl font-bold text-red-900">{{ number_format($stats['attendance_rate'], 1) }}%</p>
                </div>
                <i class="ri-user-check-line text-2xl text-red-500"></i>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">نسبة الإنجاز الإجمالية</h3>
            <span class="text-2xl font-bold text-primary-600">{{ number_format($stats['progress_percentage'], 1) }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-4">
            <div class="bg-primary-600 h-4 rounded-full transition-all duration-300" 
                 style="width: {{ $stats['progress_percentage'] }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Learning Progress -->
        <div class="lg:col-span-2 space-y-6">
            @if($circle->current_surah || $circle->verses_memorized)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">التقدم في الحفظ</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @if($circle->current_surah)
                            <div class="bg-blue-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-blue-600 mb-1">السورة الحالية</p>
                                <p class="text-xl font-bold text-blue-900">سورة رقم {{ $circle->current_surah }}</p>
                            </div>
                        @endif
                        @if($circle->current_verse)
                            <div class="bg-green-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-green-600 mb-1">الآية الحالية</p>
                                <p class="text-xl font-bold text-green-900">آية {{ $circle->current_verse }}</p>
                            </div>
                        @endif
                        @if($circle->verses_memorized)
                            <div class="bg-purple-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-purple-600 mb-1">إجمالي الآيات المحفوظة</p>
                                <p class="text-xl font-bold text-purple-900">{{ $circle->verses_memorized }} آية</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Session History -->
            @if($circle->sessions->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">سجل الجلسات</h3>
                    <div class="space-y-3">
                        @foreach($circle->sessions->take(10) as $session)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center space-x-3 space-x-reverse">
                                    <div class="w-3 h-3 rounded-full 
                                        {{ $session->status === 'completed' ? 'bg-green-500' : 
                                           ($session->status === 'scheduled' ? 'bg-blue-500' : 'bg-gray-400') }}">
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $session->title ?? 'جلسة قرآنية' }}</p>
                                        <p class="text-sm text-gray-500">
                                            {{ $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i') : 'غير مجدولة' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-left">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                        {{ $session->status === 'completed' ? 'مكتملة' : 
                                           ($session->status === 'scheduled' ? 'مجدولة' : 'غير مجدولة') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Student Details -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">تفاصيل الطالب</h3>
                <div class="flex items-center space-x-3 space-x-reverse mb-4">
                    <x-student-avatar :student="$circle->student" size="md" />
                    <div>
                        <p class="font-medium text-gray-900">{{ $circle->student->name }}</p>
                        <p class="text-sm text-gray-500">{{ $circle->student->email }}</p>
                    </div>
                </div>
                
                @if($circle->student->studentProfile)
                    <div class="space-y-2 text-sm">
                        @if($circle->student->studentProfile->student_code)
                            <div class="flex justify-between">
                                <span class="text-gray-600">كود الطالب:</span>
                                <span class="font-medium">{{ $circle->student->studentProfile->student_code }}</span>
                            </div>
                        @endif
                        @if($circle->student->studentProfile->phone)
                            <div class="flex justify-between">
                                <span class="text-gray-600">الهاتف:</span>
                                <span class="font-medium">{{ $circle->student->studentProfile->phone }}</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Circle Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات الحلقة</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">الحالة:</span>
                        <span class="font-medium 
                            {{ $circle->status === 'active' ? 'text-green-600' : 
                               ($circle->status === 'pending' ? 'text-yellow-600' : 'text-gray-600') }}">
                            {{ $circle->status === 'active' ? 'نشط' : 
                               ($circle->status === 'pending' ? 'في الانتظار' : 
                               ($circle->status === 'completed' ? 'مكتمل' : $circle->status)) }}
                        </span>
                    </div>
                    @if($circle->started_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600">بدأت في:</span>
                            <span class="font-medium">{{ $circle->started_at->format('Y-m-d') }}</span>
                        </div>
                    @endif
                    @if($circle->default_duration_minutes)
                        <div class="flex justify-between">
                            <span class="text-gray-600">مدة الجلسة:</span>
                            <span class="font-medium">{{ $circle->default_duration_minutes }} دقيقة</span>
                        </div>
                    @endif
                    @if($circle->last_session_at)
                        <div class="flex justify-between">
                            <span class="text-gray-600">آخر جلسة:</span>
                            <span class="font-medium">{{ $circle->last_session_at->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">إجراءات سريعة</h3>
                <div class="space-y-3">
                    <a href="{{ route('teacher.individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                       class="block w-full text-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="ri-eye-line ml-1"></i>
                        عرض الحلقة
                    </a>
                    
                    @if($circle->canScheduleSession())
                        <button class="w-full px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                            <i class="ri-calendar-line ml-1"></i>
                            جدولة جلسة جديدة
                        </button>
                    @endif
                    
                    <button class="w-full px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        <i class="ri-download-line ml-1"></i>
                        تصدير التقرير
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.teacher>
