@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="تفاصيل الاشتراك">
    <div class="space-y-6">
        <!-- Back Button -->
        <div>
            <a href="{{ route('parent.subscriptions.index', ['subdomain' => $subdomain]) }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-bold">
                <i class="ri-arrow-right-line ml-2"></i>
                العودة إلى الاشتراكات
            </a>
        </div>

        <!-- Subscription Header -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-4 space-x-reverse">
                    @if($type === 'quran')
                        <div class="bg-green-100 rounded-lg p-4">
                            <i class="ri-book-read-line text-3xl text-green-600"></i>
                        </div>
                    @elseif($type === 'academic')
                        <div class="bg-blue-100 rounded-lg p-4">
                            <i class="ri-book-2-line text-3xl text-blue-600"></i>
                        </div>
                    @else
                        <div class="bg-purple-100 rounded-lg p-4">
                            <i class="ri-video-line text-3xl text-purple-600"></i>
                        </div>
                    @endif
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">
                            @if($type === 'quran')
                                {{ $subscription->package->name ?? 'اشتراك قرآن' }}
                            @elseif($type === 'academic')
                                {{ $subscription->subject_name ?? 'اشتراك أكاديمي' }}
                            @else
                                {{ $subscription->recordedCourse?->title ?? $subscription->interactiveCourse?->title ?? 'اشتراك دورة' }}
                            @endif
                        </h1>
                        <p class="text-gray-600 mt-1">
                            @if($type === 'quran')
                                {{ $subscription->subscription_type === 'individual' ? 'اشتراك فردي' : 'حلقة جماعية' }}
                            @elseif($type === 'academic')
                                {{ $subscription->grade_level_name ?? 'مستوى' }}
                            @else
                                دورة تعليمية
                            @endif
                        </p>
                    </div>
                </div>
                <span class="px-4 py-2 text-sm font-bold rounded-full
                    {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $subscription->status === 'expired' ? 'bg-red-100 text-red-800' : '' }}
                    {{ $subscription->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                    {{ $subscription->status === 'active' ? 'نشط' : ($subscription->status === 'expired' ? 'منتهي' : 'قيد الانتظار') }}
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Subscription Details -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">تفاصيل الاشتراك</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <!-- Student -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-blue-100 rounded-lg p-3">
                                <i class="ri-user-smile-line text-xl text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">الطالب</p>
                                <p class="font-bold text-gray-900">{{ $subscription->student->name ?? '-' }}</p>
                            </div>
                        </div>

                        @if($type === 'quran' || $type === 'academic')
                            <!-- Teacher -->
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="bg-green-100 rounded-lg p-3">
                                    <i class="ri-user-line text-xl text-green-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">المعلم</p>
                                    <p class="font-bold text-gray-900">
                                        @if($type === 'quran')
                                            {{ $subscription->quranTeacher->user->name ?? '-' }}
                                        @else
                                            {{ $subscription->academicTeacher->user->name ?? '-' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- Dates -->
                        <div class="flex items-center space-x-3 space-x-reverse">
                            <div class="bg-purple-100 rounded-lg p-3">
                                <i class="ri-calendar-line text-xl text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">تاريخ البدء</p>
                                <p class="font-bold text-gray-900">
                                    @if($type === 'course')
                                        {{ $subscription->enrolled_at?->format('Y/m/d') ?? '-' }}
                                    @else
                                        {{ $subscription->start_date?->format('Y/m/d') ?? '-' }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        @if(($type !== 'course' && $subscription->end_date) || ($type === 'course' && $subscription->expires_at))
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="bg-yellow-100 rounded-lg p-3">
                                    <i class="ri-calendar-check-line text-xl text-yellow-600"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">تاريخ الانتهاء</p>
                                    <p class="font-bold text-gray-900">
                                        @if($type === 'course')
                                            {{ $subscription->expires_at?->format('Y/m/d') ?? '-' }}
                                        @else
                                            {{ $subscription->end_date?->format('Y/m/d') ?? '-' }}
                                        @endif
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Progress & Stats -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-gray-900">الإحصائيات والتقدم</h2>
                    </div>
                    <div class="p-6">
                        @if($type === 'quran')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
                                    <i class="ri-calendar-line text-4xl text-green-600 mb-3"></i>
                                    <p class="text-sm text-gray-600 mb-1">إجمالي الجلسات</p>
                                    <p class="text-4xl font-bold text-gray-900">{{ $subscription->total_sessions }}</p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
                                    <i class="ri-timer-line text-4xl text-blue-600 mb-3"></i>
                                    <p class="text-sm text-gray-600 mb-1">الجلسات المتبقية</p>
                                    <p class="text-4xl font-bold text-gray-900">{{ $subscription->sessions_remaining }}</p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg">
                                    <i class="ri-check-line text-4xl text-purple-600 mb-3"></i>
                                    <p class="text-sm text-gray-600 mb-1">الجلسات المكتملة</p>
                                    <p class="text-4xl font-bold text-gray-900">{{ $subscription->total_sessions - $subscription->sessions_remaining }}</p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg">
                                    <i class="ri-percent-line text-4xl text-yellow-600 mb-3"></i>
                                    <p class="text-sm text-gray-600 mb-1">نسبة الإنجاز</p>
                                    <p class="text-4xl font-bold text-gray-900">
                                        {{ $subscription->total_sessions > 0 ? round((($subscription->total_sessions - $subscription->sessions_remaining) / $subscription->total_sessions) * 100) : 0 }}%
                                    </p>
                                </div>
                            </div>
                        @elseif($type === 'academic')
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="text-center p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg">
                                    <i class="ri-calendar-check-line text-4xl text-blue-600 mb-3"></i>
                                    <p class="text-sm text-gray-600 mb-1">الحصص المكتملة</p>
                                    <p class="text-4xl font-bold text-gray-900">{{ $subscription->total_sessions_completed ?? 0 }}</p>
                                </div>

                                <div class="text-center p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-lg">
                                    <i class="ri-time-line text-4xl text-green-600 mb-3"></i>
                                    <p class="text-sm text-gray-600 mb-1">إجمالي الساعات</p>
                                    <p class="text-4xl font-bold text-gray-900">{{ $subscription->total_hours ?? 0 }}</p>
                                </div>
                            </div>
                        @else
                            <div class="space-y-4">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-gray-700 font-bold">نسبة الإنجاز</span>
                                        <span class="text-2xl font-bold text-purple-600">{{ $subscription->progress_percentage ?? 0 }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-4">
                                        <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-4 rounded-full" style="width: {{ $subscription->progress_percentage ?? 0 }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Recent Sessions/Activities -->
                @if($recentSessions && $recentSessions->isNotEmpty())
                    <div class="bg-white rounded-lg shadow">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-xl font-bold text-gray-900">الجلسات الأخيرة</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            @foreach($recentSessions->take(5) as $session)
                                <div class="p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3 space-x-reverse">
                                            <i class="ri-calendar-event-line text-xl text-blue-600"></i>
                                            <div>
                                                <p class="font-bold text-gray-900">
                                                    {{ formatDateArabic($session->scheduled_at) }}
                                                </p>
                                                <p class="text-sm text-gray-600">{{ formatTimeArabic($session->scheduled_at) }}</p>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 text-xs font-bold rounded-full
                                            {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : '' }}
                                            {{ $session->status === 'cancelled' ? 'bg-red-100 text-red-800' : '' }}">
                                            {{ $session->status === 'completed' ? 'مكتملة' : ($session->status === 'scheduled' ? 'مجدولة' : 'ملغاة') }}
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
                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                    <div class="space-y-2">
                        @if($type === 'quran' || $type === 'academic')
                            <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="flex items-center justify-between p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <i class="ri-calendar-event-line text-blue-600"></i>
                                    <span class="text-gray-900 font-bold">الجلسات القادمة</span>
                                </div>
                                <i class="ri-arrow-left-line text-gray-400"></i>
                            </a>

                            <a href="{{ route('parent.calendar.index', ['subdomain' => $subdomain]) }}" class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors">
                                <div class="flex items-center space-x-2 space-x-reverse">
                                    <i class="ri-history-line text-gray-600"></i>
                                    <span class="text-gray-900 font-bold">سجل الجلسات</span>
                                </div>
                                <i class="ri-arrow-left-line text-gray-400"></i>
                            </a>
                        @endif

                        <a href="{{ route('parent.payments.index', ['subdomain' => $subdomain]) }}" class="flex items-center justify-between p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <i class="ri-money-dollar-circle-line text-green-600"></i>
                                <span class="text-gray-900 font-bold">سجل المدفوعات</span>
                            </div>
                            <i class="ri-arrow-left-line text-gray-400"></i>
                        </a>
                    </div>
                </div>

                <!-- Subscription Status -->
                <div class="bg-gradient-to-br {{ $subscription->status === 'active' ? 'from-green-500 to-green-600' : ($subscription->status === 'expired' ? 'from-red-500 to-red-600' : 'from-yellow-500 to-yellow-600') }} rounded-lg shadow-lg p-6 text-white">
                    <h3 class="text-lg font-bold mb-4">حالة الاشتراك</h3>
                    <div class="text-center">
                        <i class="ri-{{ $subscription->status === 'active' ? 'checkbox-circle' : ($subscription->status === 'expired' ? 'close-circle' : 'time') }}-line text-6xl mb-3 opacity-80"></i>
                        <p class="text-2xl font-bold">
                            {{ $subscription->status === 'active' ? 'نشط' : ($subscription->status === 'expired' ? 'منتهي' : 'قيد الانتظار') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.parent-layout>
