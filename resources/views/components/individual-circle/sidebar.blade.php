@props([
    'circle',
    'viewType' => 'student' // 'student' or 'teacher'
])

<div class="lg:col-span-1">
    <!-- Circle Info -->
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6 sticky top-4">
        <h3 class="font-bold text-gray-900 mb-4">معلومات الحلقة</h3>
        
        <div class="space-y-4">
            <div>
                <span class="text-sm text-gray-600">نوع التخصص:</span>
                <p class="font-medium text-gray-900">
                    {{ $circle->specialization === 'memorization' ? 'حفظ القرآن' : 
                       ($circle->specialization === 'recitation' ? 'التلاوة' : 
                       ($circle->specialization === 'interpretation' ? 'التفسير' : 
                       ($circle->specialization === 'arabic_language' ? 'اللغة العربية' : 
                       ($circle->specialization === 'complete' ? 'متكامل' : $circle->specialization)))) }}
                </p>
            </div>
            
            <div>
                <span class="text-sm text-gray-600">المستوى:</span>
                <p class="font-medium text-gray-900">
                    {{ $circle->memorization_level === 'beginner' ? 'مبتدئ' : 
                       ($circle->memorization_level === 'elementary' ? 'ابتدائي' : 
                       ($circle->memorization_level === 'intermediate' ? 'متوسط' : 
                       ($circle->memorization_level === 'advanced' ? 'متقدم' : 
                       ($circle->memorization_level === 'expert' ? 'خبير' : $circle->memorization_level)))) }}
                </p>
            </div>
            
            <div>
                <span class="text-sm text-gray-600">مدة الجلسة الافتراضية:</span>
                <p class="font-medium text-gray-900">{{ $circle->default_duration_minutes }} دقيقة</p>
            </div>
            
            @if($circle->subscription)
                <div>
                    <span class="text-sm text-gray-600">نوع الاشتراك:</span>
                    <p class="font-medium text-gray-900">{{ $circle->subscription->package->name ?? 'غير محدد' }}</p>
                </div>
                
                <div>
                    <span class="text-sm text-gray-600">حالة الاشتراك:</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $circle->subscription->subscription_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $circle->subscription->subscription_status === 'active' ? 'نشط' : $circle->subscription->subscription_status }}
                    </span>
                </div>

                @if($circle->subscription->expires_at)
                    <div>
                        <span class="text-sm text-gray-600">تاريخ انتهاء الاشتراك:</span>
                        <p class="font-medium text-gray-900">{{ $circle->subscription->expires_at->format('Y-m-d') }}</p>
                    </div>
                @endif
            @endif

            @if($circle->preferred_times && count($circle->preferred_times) > 0)
                <div>
                    <span class="text-sm text-gray-600">الأوقات المفضلة:</span>
                    <div class="mt-1 flex flex-wrap gap-1">
                        @foreach($circle->preferred_times as $time)
                            <span class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">
                                {{ $time }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        
        @if($circle->notes)
            <div class="mt-6 pt-4 border-t border-gray-200">
                <span class="text-sm text-gray-600">ملاحظات:</span>
                <p class="mt-1 text-sm text-gray-700">{{ $circle->notes }}</p>
            </div>
        @endif

        @if($viewType === 'teacher' && $circle->teacher_notes)
            <div class="mt-6 pt-4 border-t border-gray-200">
                <span class="text-sm text-gray-600">ملاحظات المعلم:</span>
                <p class="mt-1 text-sm text-gray-700">{{ $circle->teacher_notes }}</p>
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
        
        <div class="space-y-3">
            @if($viewType === 'teacher')
                <!-- Teacher Actions -->
                @if($circle->canScheduleSession())
                    <button type="button" onclick="openScheduleModal()" 
                        class="w-full flex items-center justify-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="ri-calendar-add-line ml-2"></i>
                        جدولة جلسة جديدة
                    </button>
                @endif
                
                <a href="mailto:{{ $circle->student->email }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-mail-line ml-2"></i>
                    إرسال رسالة للطالب
                </a>
                
                <a href="{{ route('teacher.individual-circles.progress', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-line-chart-line ml-2"></i>
                    عرض التقرير التفصيلي
                </a>

                <button type="button" onclick="updateCircleSettings()" 
                    class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-settings-line ml-2"></i>
                    إعدادات الحلقة
                </button>
            @else
                <!-- Student Actions -->
                @php
                    $nextSession = $circle->sessions()
                        ->where('scheduled_at', '>', now())
                        ->where('status', 'scheduled')
                        ->orderBy('scheduled_at')
                        ->first();
                @endphp
                
                @if($nextSession && $nextSession->scheduled_at->diffInMinutes(now()) <= 30)
                    <a href="{{ route('meetings.join', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $nextSession->id]) }}"
                       class="w-full flex items-center justify-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                        <i class="ri-video-line ml-2"></i>
                        انضمام للجلسة القادمة
                    </a>
                @endif

                @if($circle->quranTeacher)
                    <a href="mailto:{{ $circle->quranTeacher->email }}" 
                       class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-mail-line ml-2"></i>
                        إرسال رسالة للمعلم
                    </a>
                    
                    <a href="{{ route('public.quran-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $circle->quranTeacher->id]) }}" 
                       class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="ri-user-line ml-2"></i>
                        ملف المعلم
                    </a>
                @endif

                <button type="button" onclick="requestReschedule()" 
                    class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-calendar-event-line ml-2"></i>
                    طلب إعادة جدولة
                </button>
            @endif
        </div>
    </div>

    <!-- Recent Activity (if any) -->
    @if($circle->sessions()->where('status', 'completed')->exists())
        <div class="bg-white rounded-xl shadow-sm p-6 mt-6">
            <h3 class="font-bold text-gray-900 mb-4">النشاط الأخير</h3>
            
            @php
                $recentSessions = $circle->sessions()
                    ->where('status', 'completed')
                    ->orderBy('ended_at', 'desc')
                    ->limit(3)
                    ->get();
            @endphp
            
            <div class="space-y-3">
                @foreach($recentSessions as $session)
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-2 h-2 bg-green-400 rounded-full"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $session->title }}</p>
                            <p class="text-xs text-gray-500">{{ $session->ended_at->diffForHumans() }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

@if($viewType === 'teacher')
<script>
    function updateCircleSettings() {
        // This will be implemented when we create the settings functionality
        alert('سيتم تنفيذ إعدادات الحلقة قريباً');
    }
</script>
@else
<script>
    function requestReschedule() {
        // This will be implemented when we create the reschedule functionality
        alert('سيتم تنفيذ طلب إعادة الجدولة قريباً');
    }
</script>
@endif
