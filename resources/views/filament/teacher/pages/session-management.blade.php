<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Quick Stats -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @php
                $teacher = auth()->user();
                $todaySessions = App\Models\QuranSession::where('quran_teacher_id', $teacher->id)
                    ->whereDate('scheduled_at', today())
                    ->count();
                $activeSessions = App\Models\QuranSession::where('quran_teacher_id', $teacher->id)
                    ->where('status', 'ongoing')
                    ->count();
                $totalSessions = App\Models\QuranSession::where('quran_teacher_id', $teacher->id)
                    ->whereMonth('scheduled_at', now()->month)
                    ->count();
                $subscriptions = App\Models\QuranSubscription::where('quran_teacher_id', $teacher->id)
                    ->where('status', 'active')
                    ->count();
            @endphp

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ms-4">
                        <p class="text-sm font-medium text-gray-500">جلسات اليوم</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $todaySessions }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <div class="ms-4">
                        <p class="text-sm font-medium text-gray-500">الجلسات النشطة</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $activeSessions }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ms-4">
                        <p class="text-sm font-medium text-gray-500">جلسات هذا الشهر</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $totalSessions }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ms-4">
                        <p class="text-sm font-medium text-gray-500">الاشتراكات النشطة</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $subscriptions }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">إجراءات سريعة</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <button type="button" 
                            onclick="$wire.mountTableAction('create_individual_session')"
                            class="flex items-center justify-center px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <svg class="h-5 w-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        إنشاء جلسة فردية
                    </button>

                    <button type="button"
                            onclick="$wire.mountTableAction('create_recurring_schedule')"
                            class="flex items-center justify-center px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <svg class="h-5 w-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        جدولة متكررة
                    </button>

                    <a href="{{ route('filament.teacher.pages.profile', ['tenant' => auth()->user()->academy_id]) }}"
                       class="flex items-center justify-center px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <svg class="h-5 w-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        عرض التقويم
                    </a>

                    <a href="#"
                       class="flex items-center justify-center px-4 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        <svg class="h-5 w-5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        إدارة الاشتراكات
                    </a>
                </div>
            </div>
        </div>

        <!-- Session Types Info -->
        <div class="grid gap-6 md:grid-cols-2">
            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">الجلسات الفردية</h4>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mt-0.5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">تركيز فردي</p>
                                <p class="text-xs text-gray-500">اهتمام كامل بالطالب الواحد</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mt-0.5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">مرونة في المواعيد</p>
                                <p class="text-xs text-gray-500">إمكانية تحديد أوقات مخصصة</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-green-500 mt-0.5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">محتوى مخصص</p>
                                <p class="text-xs text-gray-500">منهج يناسب مستوى الطالب</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="p-6">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">الحلقات القرآنية</h4>
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-blue-500 mt-0.5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">تعلم جماعي</p>
                                <p class="text-xs text-gray-500">تفاعل بين الطلاب والمعلم</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-blue-500 mt-0.5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">جدولة ثابتة</p>
                                <p class="text-xs text-gray-500">مواعيد منتظمة أسبوعياً</p>
                            </div>
                        </div>
                        <div class="flex items-start">
                            <svg class="h-5 w-5 text-blue-500 mt-0.5 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-gray-900">تحفيز تنافسي</p>
                                <p class="text-xs text-gray-500">بيئة محفزة للتعلم</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sessions Table -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">الجلسات الأخيرة</h3>
                {{ $this->table }}
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any session management specific JavaScript here
        });
    </script>
    @endpush
</x-filament-panels::page>