@props([
    'circle',
    'viewType' => 'student', // 'student', 'teacher'
    'context' => 'individual', // 'individual', 'group'
    'showActions' => true,
    'showDetails' => true,
    'variant' => 'default' // 'default', 'compact', 'banner'
])

@php
    $isGroupCircle = $context === 'group';
    $student = $isGroupCircle ? null : ($circle->student ?? null);
    $teacher = $circle->quranTeacher ?? null;
@endphp

@if($variant === 'banner')
    <!-- Banner Style for Group Circles -->
    <div class="mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="w-16 h-16 rounded-xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center">
                        <i class="ri-{{ $isGroupCircle ? 'group' : 'user-star' }}-line text-3xl text-white"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">
                            {{ $isGroupCircle ? $circle->name : 'الحلقة الفردية' }}
                        </h2>
                        <p class="text-gray-600 mt-1">
                            @if($isGroupCircle)
                                {{ $circle->description ?? 'حلقة قرآنية جماعية' }}
                            @else
                                مع {{ $teacher->user->name ?? 'معلم القرآن' }}
                            @endif
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
                        {{ $isGroupCircle ? $circle->students->count() : $circle->total_sessions }}
                    </div>
                    <div class="text-sm text-gray-600">
                        {{ $isGroupCircle ? 'طالب مسجل' : 'جلسة إجمالية' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <!-- Default Card Style -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 {{ $variant === 'compact' ? 'p-4' : '' }}">
        @if(!$isGroupCircle && $viewType === 'teacher' && $student)
            <!-- Student Info for Individual Circles -->
            <div class="flex items-center justify-between mb-6">
                <a href="{{ route('teacher.students.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $student->id]) }}" 
                   class="flex items-center space-x-4 space-x-reverse hover:bg-gray-50 p-3 rounded-lg transition-colors group flex-1">
                    <x-student-avatar :student="$student" size="lg" />
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                            {{ $student->name ?? 'طالب' }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ $circle->subscription->package->name ?? 'اشتراك مخصص' }}</p>
                        <div class="flex items-center space-x-2 space-x-reverse mt-1">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $circle->status === 'active' ? 'bg-green-100 text-green-800' : 
                                   ($circle->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $circle->status === 'active' ? 'نشط' : 
                                   ($circle->status === 'pending' ? 'في الانتظار' : 
                                   ($circle->status === 'completed' ? 'مكتمل' : $circle->status)) }}
                            </span>
                            @if($student->email)
                                <span class="text-xs text-gray-400">{{ $student->email }}</span>
                            @endif
                        </div>
                    </div>
                </a>
                
                @if($showActions)
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <!-- Schedule functionality removed - now handled in Filament dashboard -->
                        
                        <a href="{{ route('teacher.individual-circles.progress', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="ri-line-chart-line ml-2"></i>
                            تقرير التقدم
                        </a>
                    </div>
                @endif
            </div>
        @endif

        @if($showDetails)
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-900 flex items-center">
                    <i class="ri-information-line text-primary-600 ml-2"></i>
                    معلومات {{ $isGroupCircle ? 'الحلقة' : 'الاشتراك' }}
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if(!$isGroupCircle)
                        <!-- Individual Circle Details -->
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
                            <p class="font-medium text-gray-900">{{ $circle->default_duration_minutes ?? 60 }} دقيقة</p>
                        </div>
                        
                        @if($circle->subscription)
                            <div>
                                <span class="text-sm text-gray-600">نوع الاشتراك:</span>
                                <p class="font-medium text-gray-900">{{ $circle->subscription->package->name ?? 'غير محدد' }}</p>
                            </div>
                            
                            @if($circle->subscription->expires_at)
                                <div>
                                    <span class="text-sm text-gray-600">تاريخ انتهاء الاشتراك:</span>
                                    <p class="font-medium text-gray-900">{{ $circle->subscription->expires_at->format('Y-m-d') }}</p>
                                </div>
                            @endif
                        @endif
                    @else
                        <!-- Group Circle Details -->
                        <div>
                            <span class="text-sm text-gray-600">المعلم:</span>
                            <p class="font-medium text-gray-900">{{ $teacher->user->name ?? 'غير محدد' }}</p>
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
                    @endif
                </div>

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

                @if($circle->notes)
                    <div class="pt-4 border-t border-gray-200">
                        <span class="text-sm text-gray-600">ملاحظات:</span>
                        <p class="mt-1 text-sm text-gray-700">{{ $circle->notes }}</p>
                    </div>
                @endif

                @if($viewType === 'teacher' && $circle->teacher_notes)
                    <div class="pt-4 border-t border-gray-200">
                        <span class="text-sm text-gray-600">ملاحظات المعلم:</span>
                        <p class="mt-1 text-sm text-gray-700">{{ $circle->teacher_notes }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endif
