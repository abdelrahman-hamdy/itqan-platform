@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="تفاصيل الجلسة">
    <div class="space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ url()->previous() }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-bold">
                <i class="ri-arrow-right-line ml-2"></i>
                العودة
            </a>
        </div>

        <!-- Session Header -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4 space-x-reverse">
                    @if($session instanceof \App\Models\QuranSession)
                        <div class="bg-green-100 rounded-lg p-4">
                            <i class="ri-book-read-line text-3xl text-green-600"></i>
                        </div>
                    @else
                        <div class="bg-blue-100 rounded-lg p-4">
                            <i class="ri-book-2-line text-3xl text-blue-600"></i>
                        </div>
                    @endif
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            @if($session instanceof \App\Models\QuranSession)
                                جلسة قرآن - {{ $session->subscription_type === 'individual' ? 'فردي' : 'حلقة جماعية' }}
                            @else
                                حصة دراسية - {{ $session->subject_name ?? 'مادة' }}
                            @endif
                        </h1>
                        @if(!($session instanceof \App\Models\QuranSession))
                            <p class="text-gray-600 mt-1">{{ $session->grade_level_name ?? 'مستوى' }}</p>
                        @endif
                    </div>
                </div>
                <span class="px-4 py-2 text-sm font-bold rounded-full
                    {{ $session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $session->status === 'live' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $session->status === 'completed' ? 'bg-gray-100 text-gray-800' : '' }}
                    {{ $session->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                    {{ $session->status === 'scheduled' ? 'مجدولة' : ($session->status === 'live' ? 'جارية' : ($session->status === 'completed' ? 'مكتملة' : 'ملغاة')) }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Session Information -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">معلومات الجلسة</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Date & Time -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-blue-100 rounded-lg p-3">
                                <i class="ri-calendar-line text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">التاريخ والوقت</p>
                                <p class="font-bold text-gray-900">{{ formatDateTimeArabic($session->scheduled_at) }}</p>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-purple-100 rounded-lg p-3">
                                <i class="ri-time-line text-xl text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">المدة</p>
                                <p class="font-bold text-gray-900">{{ $session->duration_minutes }} دقيقة</p>
                                @if($session->status === 'completed' && $session->actual_duration_minutes)
                                    <p class="text-sm text-gray-600">المدة الفعلية: {{ $session->actual_duration_minutes }} دقيقة</p>
                                @endif
                            </div>
                        </div>

                        <!-- Teacher -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-green-100 rounded-lg p-3">
                                <i class="ri-user-line text-xl text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">المعلم</p>
                                <p class="font-bold text-gray-900">
                                    @if($session instanceof \App\Models\QuranSession)
                                        {{ $session->quranTeacher->user->name }}
                                    @else
                                        {{ $session->academicTeacher->user->name }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <!-- Student -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-yellow-100 rounded-lg p-3">
                                <i class="ri-user-smile-line text-xl text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">الطالب</p>
                                <p class="font-bold text-gray-900">{{ $session->student->name ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quran-specific Content -->
                @if($session instanceof \App\Models\QuranSession && $session->status === 'completed')
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900">تفاصيل الحفظ والمراجعة</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @if($session->pages_memorized_from || $session->pages_memorized_to)
                                    <div class="p-4 bg-green-50 rounded-lg">
                                        <p class="text-sm text-gray-600 mb-1">الحفظ الجديد</p>
                                        <p class="font-bold text-gray-900">
                                            من صفحة {{ $session->pages_memorized_from ?? '-' }} إلى {{ $session->pages_memorized_to ?? '-' }}
                                        </p>
                                    </div>
                                @endif

                                @if($session->pages_reviewed_from || $session->pages_reviewed_to)
                                    <div class="p-4 bg-blue-50 rounded-lg">
                                        <p class="text-sm text-gray-600 mb-1">المراجعة</p>
                                        <p class="font-bold text-gray-900">
                                            من صفحة {{ $session->pages_reviewed_from ?? '-' }} إلى {{ $session->pages_reviewed_to ?? '-' }}
                                        </p>
                                    </div>
                                @endif

                                @if($session->tajweed_score)
                                    <div class="p-4 bg-purple-50 rounded-lg">
                                        <p class="text-sm text-gray-600 mb-1">تقييم التجويد</p>
                                        <p class="font-bold text-gray-900">{{ $session->tajweed_score }}/10</p>
                                    </div>
                                @endif

                                @if($session->memorization_quality_score)
                                    <div class="p-4 bg-yellow-50 rounded-lg">
                                        <p class="text-sm text-gray-600 mb-1">جودة الحفظ</p>
                                        <p class="font-bold text-gray-900">{{ $session->memorization_quality_score }}/10</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Academic-specific Content -->
                @if(!($session instanceof \App\Models\QuranSession) && $session->status === 'completed')
                    @if($session->lesson_topic || $session->learning_outcomes)
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-6 border-b border-gray-200">
                                <h2 class="text-xl font-bold text-gray-900">محتوى الحصة</h2>
                            </div>
                            <div class="p-6 space-y-4">
                                @if($session->lesson_topic)
                                    <div>
                                        <p class="text-sm font-bold text-gray-700 mb-2">موضوع الدرس</p>
                                        <p class="text-gray-900">{{ $session->lesson_topic }}</p>
                                    </div>
                                @endif

                                @if($session->learning_outcomes)
                                    <div>
                                        <p class="text-sm font-bold text-gray-700 mb-2">نواتج التعلم</p>
                                        <p class="text-gray-900">{{ $session->learning_outcomes }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($session->homework_description)
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-6 border-b border-gray-200">
                                <h2 class="text-xl font-bold text-gray-900">الواجب المنزلي</h2>
                            </div>
                            <div class="p-6">
                                <p class="text-gray-900">{{ $session->homework_description }}</p>
                                @if($session->homework_file)
                                    <a href="{{ Storage::url($session->homework_file) }}" target="_blank" class="inline-flex items-center mt-3 text-blue-600 hover:text-blue-700">
                                        <i class="ri-download-line ml-1"></i>
                                        تحميل الملف المرفق
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Teacher Notes -->
                @if($session->teacher_notes && $session->status === 'completed')
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900">ملاحظات المعلم</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-gray-900 whitespace-pre-line">{{ $session->teacher_notes }}</p>
                        </div>
                    </div>
                @endif

                <!-- Cancellation Reason -->
                @if($session->status === 'cancelled' && $session->cancellation_reason)
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                        <div class="flex items-start space-x-3 space-x-reverse">
                            <i class="ri-error-warning-line text-2xl text-red-600"></i>
                            <div>
                                <p class="font-bold text-red-900 mb-1">سبب الإلغاء</p>
                                <p class="text-red-800">{{ $session->cancellation_reason }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Attendance Info -->
                @if($attendance)
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h3 class="text-lg font-bold text-gray-900">حالة الحضور</h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="text-center p-4 rounded-lg {{ $attendance->status === 'present' ? 'bg-green-100' : ($attendance->status === 'absent' ? 'bg-red-100' : 'bg-yellow-100') }}">
                                <i class="ri-user-{{ $attendance->status === 'present' ? 'check' : ($attendance->status === 'absent' ? 'unfollow' : 'clock') }}-line text-4xl mb-2 {{ $attendance->status === 'present' ? 'text-green-600' : ($attendance->status === 'absent' ? 'text-red-600' : 'text-yellow-600') }}"></i>
                                <p class="font-bold text-lg {{ $attendance->status === 'present' ? 'text-green-900' : ($attendance->status === 'absent' ? 'text-red-900' : 'text-yellow-900') }}">
                                    {{ $attendance->status === 'present' ? 'حاضر' : ($attendance->status === 'absent' ? 'غائب' : 'متأخر') }}
                                </p>
                            </div>

                            @if($attendance->attended_at)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">وقت الدخول</span>
                                    <span class="font-bold text-gray-900">{{ $attendance->attended_at->format('h:i A') }}</span>
                                </div>
                            @endif

                            @if($attendance->left_at)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">وقت الخروج</span>
                                    <span class="font-bold text-gray-900">{{ $attendance->left_at->format('h:i A') }}</span>
                                </div>
                            @endif

                            @if($attendance->duration_minutes)
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">مدة الحضور</span>
                                    <span class="font-bold text-gray-900">{{ $attendance->duration_minutes }} دقيقة</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Quick Stats -->
                <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4">إحصائيات سريعة</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-blue-100">الجلسات الكلية</span>
                            <span class="font-bold text-2xl">{{ $stats['total_sessions'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-blue-100">الجلسات المكتملة</span>
                            <span class="font-bold text-2xl">{{ $stats['completed_sessions'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-blue-100">نسبة الحضور</span>
                            <span class="font-bold text-2xl">{{ $stats['attendance_rate'] }}%</span>
                        </div>
                    </div>
                </div>

                <!-- Related Links -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">روابط ذات صلة</h3>
                    <div class="space-y-2">
                        <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <i class="ri-calendar-event-line text-blue-600"></i>
                                <span class="text-gray-900">الجلسات القادمة</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                        <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <i class="ri-history-line text-blue-600"></i>
                                <span class="text-gray-900">سجل الجلسات</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent-layout>
