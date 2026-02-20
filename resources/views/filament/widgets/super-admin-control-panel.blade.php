<x-filament-widgets::widget>
    <div
        x-data="{
            activeTab: @entangle('activeTab'),
        }"
        wire:poll.60s
    >
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 overflow-hidden">

            {{-- Header --}}
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 sm:w-10 sm:h-10 bg-primary-100 dark:bg-primary-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                        <x-heroicon-o-command-line class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-base sm:text-lg font-bold text-gray-900 dark:text-white truncate">لوحة التحكم</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $academyName }}</p>
                    </div>
                </div>

                @if($quickStats['total_pending'] > 0)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-bold bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 flex-shrink-0">
                        {{ $quickStats['total_pending'] }} معلق
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-bold bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 flex-shrink-0">
                        لا توجد معلقات
                    </span>
                @endif
            </div>

            {{-- Mini Stats Row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-px bg-gray-200 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
                <div class="bg-white dark:bg-gray-800 px-3 py-2.5 sm:px-4 sm:py-3 text-center">
                    <div class="text-lg sm:text-xl font-bold {{ $quickStats['total_pending'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                        {{ $quickStats['total_pending'] }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">بنود معلقة</div>
                </div>
                <div class="bg-white dark:bg-gray-800 px-3 py-2.5 sm:px-4 sm:py-3 text-center">
                    <div class="text-lg sm:text-xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $quickStats['today_sessions'] }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">جلسات اليوم</div>
                </div>
                <div class="bg-white dark:bg-gray-800 px-3 py-2.5 sm:px-4 sm:py-3 text-center">
                    <div class="text-lg sm:text-xl font-bold text-emerald-600 dark:text-emerald-400">
                        {{ $quickStats['active_subscriptions'] }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">اشتراكات نشطة</div>
                </div>
                <div class="bg-white dark:bg-gray-800 px-3 py-2.5 sm:px-4 sm:py-3 text-center">
                    <div class="text-lg sm:text-xl font-bold text-amber-600 dark:text-amber-400">
                        {{ number_format($quickStats['monthly_revenue'], 0) }}
                    </div>
                    <div class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">إيرادات الشهر</div>
                </div>
            </div>

            {{-- Tab Navigation --}}
            <div class="border-b border-gray-200 dark:border-gray-700 px-4 overflow-x-auto scrollbar-none">
                <nav class="flex gap-1 min-w-max" aria-label="Tabs">
                    <button
                        @click="activeTab = 'pending'"
                        :class="activeTab === 'pending'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="py-2.5 px-3 border-b-2 text-xs sm:text-sm font-medium whitespace-nowrap transition-colors"
                    >
                        المعلقات
                        @if(collect($pending)->sum('count') > 0)
                            <span class="mr-1 inline-flex items-center justify-center min-w-[20px] px-1.5 py-0.5 text-[10px] sm:text-xs rounded-full bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                {{ collect($pending)->sum('count') }}
                            </span>
                        @endif
                    </button>
                    <button
                        @click="activeTab = 'today'"
                        :class="activeTab === 'today'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="py-2.5 px-3 border-b-2 text-xs sm:text-sm font-medium whitespace-nowrap transition-colors"
                    >
                        جلسات اليوم
                        @if(collect($today)->sum('total') > 0)
                            <span class="mr-1 inline-flex items-center justify-center min-w-[20px] px-1.5 py-0.5 text-[10px] sm:text-xs rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                {{ collect($today)->sum('total') }}
                            </span>
                        @endif
                    </button>
                    <button
                        @click="activeTab = 'actions'"
                        :class="activeTab === 'actions'
                            ? 'border-primary-500 text-primary-600 dark:text-primary-400'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                        class="py-2.5 px-3 border-b-2 text-xs sm:text-sm font-medium whitespace-nowrap transition-colors"
                    >
                        إجراءات سريعة
                    </button>
                </nav>
            </div>

            {{-- Tab Content: Pending Items --}}
            <div x-show="activeTab === 'pending'" x-cloak class="p-3 sm:p-4">
                @php
                    $urgentItems = collect($pending)->filter(fn($item) => $item['urgent'] && $item['count'] > 0);
                    $regularItems = collect($pending)->filter(fn($item) => !$item['urgent'] && $item['count'] > 0);
                @endphp

                @if($urgentItems->isNotEmpty())
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-red-500 flex-shrink-0" />
                        <h3 class="text-xs sm:text-sm font-semibold text-red-600 dark:text-red-400">تحتاج إجراء عاجل</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2 sm:gap-3 mb-4 sm:mb-6">
                        @foreach($urgentItems as $key => $item)
                            @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                        @endforeach
                    </div>
                @endif

                @if($regularItems->isNotEmpty())
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-600 dark:text-gray-400">تحتاج متابعة</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-2 sm:gap-3">
                        @foreach($regularItems as $key => $item)
                            @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                        @endforeach
                    </div>
                @endif

                @if($urgentItems->isEmpty() && $regularItems->isEmpty())
                    <div class="text-center py-6 sm:py-8">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mb-3">
                            <x-heroicon-o-check-circle class="w-6 h-6 sm:w-8 sm:h-8 text-green-500" />
                        </div>
                        <p class="text-sm sm:text-base text-green-600 dark:text-green-400 font-medium">لا توجد بنود معلقة</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">كل شيء تحت السيطرة</p>
                    </div>
                @endif
            </div>

            {{-- Tab Content: Today's Sessions --}}
            <div x-show="activeTab === 'today'" x-cloak class="p-3 sm:p-4">
                @if(collect($today)->sum('total') > 0)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 sm:gap-4">
                        @foreach($today as $type => $session)
                            <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-3 sm:p-4 border border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <x-dynamic-component :component="$session['icon']" class="w-5 h-5 text-{{ $session['color'] }}-500" />
                                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $session['label'] }}</h4>
                                    </div>
                                    <span class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">{{ $session['total'] }}</span>
                                </div>

                                <div class="space-y-1.5 text-xs sm:text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">مجدولة</span>
                                        <span class="font-medium text-blue-600 dark:text-blue-400">{{ $session['scheduled'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            @if($session['ongoing'] > 0)
                                                <span class="relative flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                                                </span>
                                            @endif
                                            <span class="text-gray-500 dark:text-gray-400">جارية الآن</span>
                                        </div>
                                        <span class="font-medium text-primary-600 dark:text-primary-400">{{ $session['ongoing'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-500 dark:text-gray-400">مكتملة</span>
                                        <span class="font-medium text-green-600 dark:text-green-400">{{ $session['completed'] }}</span>
                                    </div>
                                    @if($session['cancelled'] > 0)
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-500 dark:text-gray-400">ملغاة</span>
                                            <span class="font-medium text-red-600 dark:text-red-400">{{ $session['cancelled'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if($session['total'] > 0)
                                    @php $pct = round(($session['completed'] / $session['total']) * 100); @endphp
                                    <div class="mt-3">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="bg-green-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <p class="text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 mt-1">{{ $pct }}% مكتملة</p>
                                    </div>
                                @endif

                                <a href="{{ $session['url'] }}"
                                   class="mt-3 inline-flex items-center gap-1 text-xs sm:text-sm font-medium text-{{ $session['color'] }}-600 hover:text-{{ $session['color'] }}-800 dark:text-{{ $session['color'] }}-400 dark:hover:text-{{ $session['color'] }}-300 transition-colors">
                                    عرض الكل
                                    <x-heroicon-o-chevron-left class="w-3.5 h-3.5" />
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6 sm:py-8">
                        <div class="w-12 h-12 sm:w-16 sm:h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3">
                            <x-heroicon-o-calendar-days class="w-6 h-6 sm:w-8 sm:h-8 text-gray-400" />
                        </div>
                        <p class="text-sm sm:text-base text-gray-500 dark:text-gray-400 font-medium">لا توجد جلسات مجدولة لليوم</p>
                    </div>
                @endif
            </div>

            {{-- Tab Content: Quick Actions --}}
            <div x-show="activeTab === 'actions'" x-cloak class="p-3 sm:p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">

                    {{-- Quran Section --}}
                    <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-3 sm:p-4 border border-emerald-200 dark:border-emerald-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-book-open class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-emerald-900 dark:text-emerald-100">القرآن الكريم</h4>
                        </div>
                        <div class="space-y-1.5">
                            <a href="{{ route('filament.admin.resources.quran-circles.index') }}"
                               class="block text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100 transition-colors py-0.5">
                                حلقات القرآن
                            </a>
                            <a href="{{ route('filament.admin.resources.quran-individual-circles.index') }}"
                               class="block text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100 transition-colors py-0.5">
                                الحلقات الفردية
                            </a>
                            <a href="{{ route('filament.admin.resources.quran-packages.index') }}"
                               class="block text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100 transition-colors py-0.5">
                                باقات القرآن
                            </a>
                            <a href="{{ route('filament.admin.resources.quran-sessions.index') }}"
                               class="block text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100 transition-colors py-0.5">
                                جلسات القرآن
                            </a>
                            <a href="{{ route('filament.admin.resources.quran-teacher-profiles.index') }}"
                               class="block text-xs sm:text-sm text-emerald-700 dark:text-emerald-300 hover:text-emerald-900 dark:hover:text-emerald-100 transition-colors py-0.5">
                                معلمو القرآن
                            </a>
                        </div>
                    </div>

                    {{-- Academic Section --}}
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-3 sm:p-4 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-academic-cap class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-blue-900 dark:text-blue-100">القسم الأكاديمي</h4>
                        </div>
                        <div class="space-y-1.5">
                            <a href="{{ route('filament.admin.resources.academic-teacher-profiles.index') }}"
                               class="block text-xs sm:text-sm text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 transition-colors py-0.5">
                                المدرسين الأكاديميين
                            </a>
                            <a href="{{ route('filament.admin.resources.academic-packages.index') }}"
                               class="block text-xs sm:text-sm text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 transition-colors py-0.5">
                                الباقات الأكاديمية
                            </a>
                            <a href="{{ route('filament.admin.resources.academic-sessions.index') }}"
                               class="block text-xs sm:text-sm text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 transition-colors py-0.5">
                                الجلسات الأكاديمية
                            </a>
                            <a href="{{ route('filament.admin.resources.interactive-courses.index') }}"
                               class="block text-xs sm:text-sm text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 transition-colors py-0.5">
                                الدورات التفاعلية
                            </a>
                            <a href="{{ route('filament.admin.resources.recorded-courses.index') }}"
                               class="block text-xs sm:text-sm text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 transition-colors py-0.5">
                                الدورات المسجلة
                            </a>
                        </div>
                    </div>

                    {{-- Users Section --}}
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-3 sm:p-4 border border-purple-200 dark:border-purple-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-users class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-purple-900 dark:text-purple-100">المستخدمين</h4>
                        </div>
                        <div class="space-y-1.5">
                            <a href="{{ route('filament.admin.resources.student-profiles.index') }}"
                               class="block text-xs sm:text-sm text-purple-700 dark:text-purple-300 hover:text-purple-900 dark:hover:text-purple-100 transition-colors py-0.5">
                                الطلاب
                            </a>
                            <a href="{{ route('filament.admin.resources.parent-profiles.index') }}"
                               class="block text-xs sm:text-sm text-purple-700 dark:text-purple-300 hover:text-purple-900 dark:hover:text-purple-100 transition-colors py-0.5">
                                أولياء الأمور
                            </a>
                            <a href="{{ route('filament.admin.resources.supervisor-profiles.index') }}"
                               class="block text-xs sm:text-sm text-purple-700 dark:text-purple-300 hover:text-purple-900 dark:hover:text-purple-100 transition-colors py-0.5">
                                المشرفين
                            </a>
                            <a href="{{ route('filament.admin.resources.users.index') }}"
                               class="block text-xs sm:text-sm text-purple-700 dark:text-purple-300 hover:text-purple-900 dark:hover:text-purple-100 transition-colors py-0.5">
                                جميع المستخدمين
                            </a>
                        </div>
                    </div>

                    {{-- Finance Section --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-3 sm:p-4 border border-amber-200 dark:border-amber-800">
                        <div class="flex items-center gap-2 mb-3">
                            <div class="w-8 h-8 bg-amber-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-banknotes class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-amber-900 dark:text-amber-100">المالية</h4>
                        </div>
                        <div class="space-y-1.5">
                            <a href="{{ route('filament.admin.resources.payments.index') }}"
                               class="block text-xs sm:text-sm text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 transition-colors py-0.5">
                                المدفوعات
                            </a>
                            <a href="{{ route('filament.admin.resources.quran-subscriptions.index') }}"
                               class="block text-xs sm:text-sm text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 transition-colors py-0.5">
                                اشتراكات القرآن
                            </a>
                            <a href="{{ route('filament.admin.resources.academic-subscriptions.index') }}"
                               class="block text-xs sm:text-sm text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 transition-colors py-0.5">
                                الاشتراكات الأكاديمية
                            </a>
                            <a href="{{ route('filament.admin.resources.teacher-earnings.index') }}"
                               class="block text-xs sm:text-sm text-amber-700 dark:text-amber-300 hover:text-amber-900 dark:hover:text-amber-100 transition-colors py-0.5">
                                أرباح المعلمين
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>
