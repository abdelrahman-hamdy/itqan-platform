<x-layouts.student 
    :title="'الاشتراكات - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')"
    :description="'إدارة جميع اشتراكاتك والطلبات التجريبية والدورات المسجلة - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')">

<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">الاشتراكات</h1>
            <p class="text-gray-600">إدارة جميع اشتراكاتك والطلبات التجريبية والدورات المسجلة</p>
        </div>

        <!-- Quran Subscriptions -->
        @if($quranSubscriptions->count() > 0)
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">اشتراكات القرآن الكريم</h2>
                <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                    {{ $quranSubscriptions->count() }} اشتراك نشط
                </span>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($quranSubscriptions as $subscription)
                    <x-cards.subscription-card 
                        :subscription="$subscription" 
                        view-type="student" 
                        :show-progress="true" 
                        :show-actions="true" />
                @endforeach
            </div>
        </div>
        @endif

        <!-- Quran Trial Requests -->
        @if($quranTrialRequests->count() > 0)
        <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">طلبات الجلسات التجريبية</h2>
                <span class="bg-orange-100 text-orange-800 text-sm font-medium px-3 py-1 rounded-full">
                    {{ $quranTrialRequests->count() }} طلب
                </span>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($quranTrialRequests as $trial)
                <div class="border border-gray-200 rounded-lg p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900">
                                {{ $trial->teacher?->full_name ?? 'معلم غير محدد' }}
                            </h4>
                            <p class="text-sm text-gray-600">جلسة تجريبية - {{ $trial->request_code ?? '' }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                          {{ $trial->status === 'approved' ? 'bg-green-100 text-green-800' : 
                             ($trial->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                             ($trial->status === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                            @switch($trial->status)
                                @case('approved')
                                    موافق عليه
                                    @break
                                @case('pending')
                                    قيد المراجعة
                                    @break
                                @case('completed')
                                    مكتمل
                                    @break
                                @case('rejected')
                                    مرفوض
                                    @break
                                @default
                                    {{ ucfirst($trial->status) }}
                            @endswitch
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        @if($trial->current_level)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">المستوى الحالي:</span>
                            <span class="font-medium">
                                @switch($trial->current_level)
                                    @case('beginner')
                                        مبتدئ
                                        @break
                                    @case('intermediate')
                                        متوسط
                                        @break
                                    @case('advanced')
                                        متقدم
                                        @break
                                    @case('memorizer')
                                        حافظ
                                        @break
                                    @default
                                        {{ $trial->current_level }}
                                @endswitch
                            </span>
                        </div>
                        @endif
                        
                        @if($trial->preferred_time)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">الوقت المفضل:</span>
                            <span class="font-medium">{{ $trial->preferred_time }}</span>
                        </div>
                        @endif
                        
                        @if($trial->scheduled_at)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">موعد الجلسة:</span>
                            <span class="font-medium text-green-600">{{ $trial->scheduled_at->format('Y-m-d H:i') }}</span>
                        </div>
                        @endif
                        
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">تاريخ الطلب:</span>
                            <span class="font-medium">{{ $trial->created_at->format('Y-m-d') }}</span>
                        </div>
                        
                        @if($trial->learning_goals && is_array($trial->learning_goals) && count($trial->learning_goals) > 0)
                        <div class="pt-2">
                            <span class="text-sm text-gray-600">الأهداف التعليمية:</span>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($trial->learning_goals as $goal)
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ $goal }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                        
                        @if($trial->teacher_response)
                        <div class="pt-2">
                            <span class="text-sm text-gray-600">رد المعلم:</span>
                            <p class="text-sm text-gray-700 mt-1 bg-gray-50 p-2 rounded">{{ $trial->teacher_response }}</p>
                        </div>
                        @endif
                    </div>
                    
                    <div class="flex gap-2">
                        @if($trial->status === 'approved' && $trial->meeting_link)
                        <a href="{{ $trial->meeting_link }}" target="_blank"
                           class="flex-1 bg-green-500 text-white text-center py-2 px-4 rounded-lg text-sm font-medium hover:bg-green-600 transition-colors">
                            دخول الجلسة
                        </a>
                        @endif
                        @if($trial->status === 'completed')
                        <a href="{{ route('public.quran-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $trial->teacher?->id]) }}" 
                           class="flex-1 bg-primary text-white text-center py-2 px-4 rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors">
                            اشترك الآن
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Course Enrollments -->
        @if($courseEnrollments->count() > 0)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900">الدورات المسجلة</h2>
                <span class="bg-purple-100 text-purple-800 text-sm font-medium px-3 py-1 rounded-full">
                    {{ $courseEnrollments->count() }} دورة
                </span>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @foreach($courseEnrollments as $enrollment)
                <div class="border border-gray-200 rounded-lg p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ $enrollment->course->title }}</h4>
                            <p class="text-sm text-gray-600">{{ $enrollment->course->instructor_name }}</p>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full
                          {{ $enrollment->status === 'active' ? 'bg-green-100 text-green-800' : 
                             ($enrollment->status === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst($enrollment->status) }}
                        </span>
                    </div>
                    
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">التقدم:</span>
                            <span class="font-medium">{{ $enrollment->progress_percentage ?? 0 }}%</span>
                        </div>
                        @if($enrollment->completed_at)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">تاريخ الإكمال:</span>
                            <span class="font-medium">{{ $enrollment->completed_at->format('Y-m-d') }}</span>
                        </div>
                        @endif
                    </div>
                    
                    <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                        <div class="bg-primary h-2 rounded-full" style="width: {{ $enrollment->progress_percentage ?? 0 }}%"></div>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="{{ route('courses.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'id' => $enrollment->recorded_course_id]) }}" 
                           class="flex-1 bg-primary text-white text-center py-2 px-4 rounded-lg text-sm font-medium hover:bg-primary-dark transition-colors">
                            متابعة الدورة
                        </a>
                        @if($enrollment->status === 'completed')
                        <a href="{{ route('student.certificates.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'enrollment' => $enrollment->id]) }}" 
                           class="flex-1 bg-green-500 text-white text-center py-2 px-4 rounded-lg text-sm font-medium hover:bg-green-600 transition-colors">
                            تحميل الشهادة
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Empty State -->
        @if($quranSubscriptions->count() == 0 && $quranTrialRequests->count() == 0 && $courseEnrollments->count() == 0)
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">لا توجد اشتراكات حالياً</h3>
            <p class="text-gray-600 mb-6">ابدأ رحلة التعلم معنا من خلال الاشتراك في أحد برامجنا التعليمية</p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="{{ route('public.quran-teachers.index') }}" 
                   class="bg-primary text-white px-6 py-3 rounded-lg font-medium hover:bg-primary-dark transition-colors">
                    معلمو القرآن
                </a>
                                    <a href="{{ route('student.quran-circles', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                   class="bg-green-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-green-600 transition-colors">
                    حلقات القرآن
                </a>
                <a href="{{ route('courses.index') }}" 
                   class="bg-purple-500 text-white px-6 py-3 rounded-lg font-medium hover:bg-purple-600 transition-colors">
                    الدورات المسجلة
                </a>
            </div>
        </div>
        @endif
    </div>
</div>

</x-layouts.student>