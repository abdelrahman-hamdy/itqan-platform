<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">معلومات المعلم</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">الاسم</p>
                        <p class="text-lg text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">البريد الإلكتروني</p>
                        <p class="text-lg text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">رقم الهاتف</p>
                        <p class="text-lg text-gray-900">{{ $user->phone ?? 'غير محدد' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">التخصص</p>
                        <p class="text-lg text-gray-900">
                            @if($user->isQuranTeacher())
                                معلم قرآن
                            @elseif($user->isAcademicTeacher())
                                معلم أكاديمي
                            @else
                                معلم
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Google Calendar Connection Status -->
        @if($user->google_calendar_connected_at)
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-600 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-800">
                        <strong>مربوط بـ Google Calendar</strong> - 
                        متصل منذ {{ $user->google_calendar_connected_at->diffForHumans() }}
                    </p>
                </div>
            </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-yellow-600 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.882 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <p class="text-sm text-yellow-800">
                            <strong>غير مربوط بـ Google Calendar</strong> - 
                            اربط حسابك لإنشاء الاجتماعات تلقائياً
                        </p>
                    </div>
                    <a href="{{ route('filament.admin.resources.google-settings.index') }}" 
                       class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                        إعدادات Google
                    </a>
                </div>
            </div>
        @endif

        <!-- Calendar Stats -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">جلسات اليوم</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['today_sessions'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">جلسات هذا الشهر</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['month_sessions'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">الجلسات القادمة</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['upcoming_sessions'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">إجمالي الطلاب</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_students'] ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Management Info -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">إدارة الجلسات</h3>
                <p class="text-gray-600">استخدم التقويم من القائمة الجانبية لإنشاء وإدارة جلساتك مع الطلاب</p>
            </div>
        </div>

        <!-- Quick Actions and Info -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">إجراءات سريعة</h4>
                <div class="space-y-3">
                    <a href="{{ route('filament.teacher.pages.session-management', ['tenant' => auth()->user()->academy_id]) }}" 
                       class="block bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        إدارة الجلسات
                    </a>
                    <a href="{{ route('teacher.profile') }}" 
                       class="block bg-purple-600 text-white text-center py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                        الملف الشخصي
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">الجلسات القادمة</h4>
                <div class="space-y-3">
                    @if(isset($events) && count($events) > 0)
                        @foreach(array_slice($events, 0, 3) as $event)
                            <div class="border-l-4 border-blue-500 pl-3">
                                <p class="text-sm font-medium text-gray-900">{{ $event['title'] }}</p>
                                <p class="text-xs text-gray-500">{{ $event['start']->format('Y-m-d H:i') }}</p>
                                @if(isset($event['meeting_link']))
                                    <a href="{{ $event['meeting_link'] }}" target="_blank" 
                                       class="text-xs text-blue-600 hover:text-blue-800">
                                        انضم للاجتماع
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500">لا توجد جلسات قادمة</p>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">إعدادات الحساب</h4>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="mr-2 text-sm text-gray-700">إشعارات الجلسات</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="mr-2 text-sm text-gray-700">مزامنة Google Calendar</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="mr-2 text-sm text-gray-700">تسجيل الجلسات تلقائياً</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Calendar initialization and event handling
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Teacher profile with calendar loaded');
            
            // Initialize calendar data
            const events = @json($events ?? []);
            
            // Add calendar functionality here
            // This would include FullCalendar.js integration for viewing/managing sessions
        });
    </script>
    @endpush
</x-filament-panels::page>