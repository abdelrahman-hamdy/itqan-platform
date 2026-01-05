<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Teacher Dashboard Header -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">مرحباً بك في لوحة تحكم المعلم</h1>
                    <p class="text-gray-600 mt-1">إدارة جلساتك وطلابك بكل سهولة</p>
                </div>
                <div class="text-center">
                    <div class="text-3xl font-bold text-green-600">
                        {{ auth()->user()->quranTeacherProfile?->rating ? number_format(auth()->user()->quranTeacherProfile->rating, 1) : 'جديد' }}
                    </div>
                    <div class="text-sm text-gray-500">التقييم العام</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <a href="{{ url('/teacher-panel/quran-trial-requests') }}" 
               class="bg-blue-50 hover:bg-blue-100 rounded-lg p-4 border border-blue-200 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="bg-blue-500 rounded-full p-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-blue-900">طلبات الجلسات التجريبية</div>
                        <div class="text-sm text-blue-600">إدارة طلبات الطلاب الجدد</div>
                    </div>
                </div>
            </a>

            <a href="{{ url('/teacher-panel/quran-sessions') }}" 
               class="bg-green-50 hover:bg-green-100 rounded-lg p-4 border border-green-200 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="bg-green-500 rounded-full p-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-green-900">جلساتي</div>
                        <div class="text-sm text-green-600">عرض وإدارة الجلسات</div>
                    </div>
                </div>
            </a>

            <a href="{{ url('/teacher-panel/quran-subscriptions') }}" 
               class="bg-purple-50 hover:bg-purple-100 rounded-lg p-4 border border-purple-200 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="bg-purple-500 rounded-full p-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-purple-900">اشتراكات طلابي</div>
                        <div class="text-sm text-purple-600">متابعة اشتراكات الطلاب</div>
                    </div>
                </div>
            </a>

            <a href="{{ url('/teacher-panel/teacher-profile') }}" 
               class="bg-orange-50 hover:bg-orange-100 rounded-lg p-4 border border-orange-200 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="bg-orange-500 rounded-full p-2">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div>
                        <div class="font-semibold text-orange-900">ملفي الشخصي</div>
                        <div class="text-sm text-orange-600">إدارة معلوماتي الشخصية</div>
                    </div>
                </div>
            </a>
        </div>

        <!-- Widgets Section -->
        <div class="space-y-6">
            <x-filament-widgets::widgets 
                :widgets="$this->getWidgets()" 
                :columns="$this->getColumns()" 
            />
        </div>
    </div>
</x-filament-panels::page>