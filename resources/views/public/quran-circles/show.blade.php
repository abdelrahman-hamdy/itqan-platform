@extends('layouts.academy')

@section('title', $circle->name . ' - ' . $academy->name)

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('public.quran-circles.index', ['subdomain' => $academy->subdomain]) }}" 
               class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200">
                <i class="ri-arrow-right-line ml-2"></i>
                العودة إلى الحلقات
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Circle Header -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $circle->name }}</h1>
                            <p class="text-lg text-gray-600">{{ $circle->description ?? 'حلقة تحفيظ القرآن الكريم مع معلم متخصص' }}</p>
                        </div>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                            {{ $circle->enrollment_status === 'open' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $circle->enrollment_status_text }}
                        </span>
                    </div>

                    <!-- Quick Stats -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-gray-900">{{ $stats['total_students'] }}</div>
                            <div class="text-sm text-gray-600">طالب مسجل</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-green-600">{{ $stats['available_spots'] }}</div>
                            <div class="text-sm text-gray-600">مقعد متاح</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $stats['sessions_completed'] }}</div>
                            <div class="text-sm text-gray-600">جلسة مكتملة</div>
                        </div>
                        <div class="text-center">
                            @if($stats['rating'] > 0)
                                <div class="text-2xl font-bold text-yellow-600">{{ number_format($stats['rating'], 1) }}</div>
                                <div class="text-sm text-gray-600">تقييم</div>
                            @else
                                <div class="text-2xl font-bold text-gray-400">جديد</div>
                                <div class="text-sm text-gray-600">لا توجد تقييمات</div>
                            @endif
                        </div>
                    </div>

                    <!-- Circle Details -->
                    <div class="border-t pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">تفاصيل الحلقة</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="flex items-center text-gray-600">
                                <i class="ri-book-open-line w-5 h-5 ml-3 text-primary"></i>
                                <div>
                                    <div class="font-medium">التخصص</div>
                                    <div class="text-sm">{{ $circle->specialization_text }}</div>
                                </div>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="ri-user-3-line w-5 h-5 ml-3 text-primary"></i>
                                <div>
                                    <div class="font-medium">الفئة العمرية</div>
                                    <div class="text-sm">{{ $circle->age_group ? $circle->age_groups[$circle->age_group] : 'جميع الأعمار' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="ri-time-line w-5 h-5 ml-3 text-primary"></i>
                                <div>
                                    <div class="font-medium">المواعيد</div>
                                    <div class="text-sm">{{ $circle->schedule_text ?? 'سيتم تحديدها لاحقاً' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="ri-calendar-line w-5 h-5 ml-3 text-primary"></i>
                                <div>
                                    <div class="font-medium">مدة الجلسة</div>
                                    <div class="text-sm">{{ $circle->session_duration_minutes ?? 60 }} دقيقة</div>
                                </div>
                            </div>
                            <div class="flex items-center text-gray-600">
                                <i class="ri-group-line w-5 h-5 ml-3 text-primary"></i>
                                <div>
                                    <div class="font-medium">العدد الأقصى</div>
                                    <div class="text-sm">{{ $circle->max_students }} طالب</div>
                                </div>
                            </div>
                            @if($circle->monthly_fee > 0)
                                <div class="flex items-center text-gray-600">
                                    <i class="ri-money-dollar-circle-line w-5 h-5 ml-3 text-primary"></i>
                                    <div>
                                        <div class="font-medium">الرسوم الشهرية</div>
                                        <div class="text-sm">{{ $circle->formatted_monthly_fee }}</div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center text-green-600">
                                    <i class="ri-gift-line w-5 h-5 ml-3"></i>
                                    <div>
                                        <div class="font-medium">مجاني</div>
                                        <div class="text-sm">لا توجد رسوم</div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Learning Objectives -->
                    @if($circle->learning_objectives && count($circle->learning_objectives) > 0)
                        <div class="border-t pt-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">أهداف الحلقة</h3>
                            <ul class="space-y-2">
                                @foreach($circle->learning_objectives as $objective)
                                    <li class="flex items-start">
                                        <i class="ri-target-line text-purple-500 mt-1 ml-2"></i>
                                        <span class="text-gray-700">{{ $objective }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Requirements -->
                    @if($circle->requirements && count($circle->requirements) > 0)
                        <div class="border-t pt-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">المتطلبات</h3>
                            <ul class="space-y-2">
                                @foreach($circle->requirements as $requirement)
                                    <li class="flex items-start">
                                        <i class="ri-arrow-left-s-line text-primary mt-1 ml-2"></i>
                                        <span class="text-gray-700">{{ $requirement }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Admin Notes (Only for Teachers, Admins, and Super Admins) -->
                    @auth
                        @if($circle->admin_notes && (auth()->user()->hasRole(['teacher', 'admin', 'super_admin']) || auth()->user()->isQuranTeacher()))
                            <div class="border-t pt-6 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-semibold text-orange-800 flex items-center">
                                        <i class="ri-information-line text-orange-600 ml-2"></i>
                                        ملاحظات الإدارة
                                    </h3>
                                    <span class="text-xs text-orange-400 italic">مرئية للإدارة والمعلمين والمشرفين فقط</span>
                                </div>
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $circle->admin_notes }}</p>
                                </div>
                            </div>
                        @endif
                    @endauth

                    <!-- Enrollment CTA -->
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Teacher Card -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">المعلم</h3>
                    @if($circle->quranTeacher)
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center ml-3">
                                @if($circle->quranTeacher->user->avatar)
                                    <img src="{{ $circle->quranTeacher->user->avatar }}" alt="{{ $circle->quranTeacher->user->name }}" class="w-12 h-12 rounded-full object-cover">
                                @else
                                    <i class="ri-user-smile-line text-2xl text-gray-400"></i>
                                @endif
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">{{ $circle->quranTeacher->user->name }}</div>
                                <div class="text-sm text-gray-600">{{ $circle->quranTeacher->qualification ?? 'إجازة في القراءات' }}</div>
                            </div>
                        </div>
                        
                        @if($circle->quranTeacher->bio)
                            <p class="text-sm text-gray-600 mb-4">{{ Str::limit($circle->quranTeacher->bio, 150) }}</p>
                        @endif
                        
                        <a href="{{ route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacher' => $circle->quranTeacher->id]) }}" 
                           class="text-primary hover:text-primary-600 text-sm font-medium">
                            عرض ملف المعلم الكامل →
                        </a>
                    @else
                        <p class="text-gray-600">سيتم تعيين المعلم قريباً</p>
                    @endif
                </div>

                <!-- Enrollment Card -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">التسجيل في الحلقة</h3>
                    
                    @if($circle->enrollment_status === 'open' && !$circle->is_full)
                        @if($isEnrolled)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <i class="ri-check-circle-line text-green-500 ml-2"></i>
                                    <span class="text-green-800 font-medium">أنت مسجل في هذه الحلقة</span>
                                </div>
                            </div>
                        @else
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-600">المقاعد المتاحة</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $stats['available_spots'] }} من {{ $circle->max_students }}</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($stats['total_students'] / $circle->max_students) * 100 }}%"></div>
                                </div>
                            </div>

                            @auth
                                @if(auth()->user()->user_type === 'student')
                                    <a href="{{ route('public.quran-circles.enroll', ['subdomain' => $academy->subdomain, 'circle' => $circle->id]) }}" 
                                       class="w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-primary-600 transition-colors duration-200 text-center block">
                                        انضم للحلقة
                                    </a>
                                @else
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                        <p class="text-yellow-800 text-sm">التسجيل متاح للطلاب فقط</p>
                                    </div>
                                @endif
                            @else
                                <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
                                   class="w-full bg-primary text-white py-3 px-4 rounded-lg font-semibold hover:bg-primary-600 transition-colors duration-200 text-center block">
                                    تسجيل الدخول للانضمام
                                </a>
                                <p class="text-xs text-gray-500 text-center mt-2">يجب تسجيل الدخول كطالب للانضمام</p>
                            @endauth
                        @endif
                    @elseif($circle->is_full)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="ri-error-warning-line text-red-500 ml-2"></i>
                                <span class="text-red-800 font-medium">الحلقة مكتملة العدد</span>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="ri-lock-line text-gray-500 ml-2"></i>
                                <span class="text-gray-700 font-medium">التسجيل مغلق حالياً</span>
                            </div>
                        </div>
                    @endif

                    <!-- Contact Info -->
                    <div class="border-t pt-4 mt-4">
                        <p class="text-sm text-gray-600 mb-2">هل تحتاج مساعدة؟</p>
                        <a href="mailto:{{ $academy->email }}" class="text-sm text-primary hover:text-primary-600">
                            تواصل معنا
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection