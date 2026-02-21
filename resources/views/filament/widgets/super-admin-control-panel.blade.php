<x-filament-widgets::widget>
    <div
        x-data="{
            activeTab: @entangle('activeTab'),
        }"
        wire:poll.60s
    >
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-command-line class="w-5 h-5 text-primary-500" />
                        <span>لوحة التحكم - {{ $academyName }}</span>
                    </div>
                    @if($quickStats['total_pending'] > 0)
                        <x-filament::badge color="danger" size="lg">
                            {{ $quickStats['total_pending'] }} بند معلق
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="success" size="lg">
                            لا توجد معلقات
                        </x-filament::badge>
                    @endif
                </div>
            </x-slot>

            {{-- Mini Stats Row --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="text-xl font-extrabold text-info-600 dark:text-info-400">
                        {{ $quickStats['today_sessions'] }}
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">جلسات اليوم</div>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="flex items-center justify-center gap-1.5">
                        @if($quickStats['ongoing_sessions'] > 0)
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                            </span>
                        @endif
                        <span class="text-xl font-extrabold text-primary-600 dark:text-primary-400">
                            {{ $quickStats['ongoing_sessions'] }}
                        </span>
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">جارية الآن</div>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="text-xl font-extrabold text-success-600 dark:text-success-400">
                        {{ $quickStats['completed_today'] }}
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">مكتملة اليوم</div>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="text-xl font-extrabold text-success-600 dark:text-success-400">
                        {{ $quickStats['active_subscriptions'] }}
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">اشتراكات نشطة</div>
                </div>
            </div>

            {{-- Main Tab Navigation --}}
            <x-filament::tabs>
                <x-filament::tabs.item
                    alpine-active="activeTab === 'quran'"
                    x-on:click="activeTab = 'quran'"
                    icon="heroicon-m-book-open"
                    :badge="collect($quran['pending'])->sum('count') ?: null"
                    badge-color="warning"
                >
                    القرآن الكريم
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    alpine-active="activeTab === 'academic'"
                    x-on:click="activeTab = 'academic'"
                    icon="heroicon-m-academic-cap"
                    :badge="collect($academic['pending'])->sum('count') ?: null"
                    badge-color="info"
                >
                    القسم الأكاديمي
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    alpine-active="activeTab === 'general'"
                    x-on:click="activeTab = 'general'"
                    icon="heroicon-m-cog-6-tooth"
                    :badge="collect($general['pending'])->sum('count') ?: null"
                    badge-color="danger"
                >
                    عام
                </x-filament::tabs.item>
            </x-filament::tabs>

            {{-- ======================== QURAN TAB ======================== --}}
            <div x-show="activeTab === 'quran'" x-cloak class="mt-4 space-y-5">
                {{-- Pending Items --}}
                @php $quranPending = collect($quran['pending'])->filter(fn($item) => $item['count'] > 0); @endphp
                @if($quranPending->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-warning-500 flex-shrink-0" />
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">بنود معلقة</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($quranPending as $item)
                                @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Today's Sessions --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-s-calendar-days class="w-4 h-4 text-success-500 flex-shrink-0" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">جلسات اليوم</h3>
                    </div>
                    @if($quran['sessions']['total'] > 0)
                        @include('filament.widgets.partials.control-panel-session-card', ['session' => $quran['sessions']])
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد جلسات قرآن مجدولة لليوم</p>
                    @endif
                </div>

                {{-- Quick Actions --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-s-plus-circle class="w-4 h-4 text-success-500 flex-shrink-0" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">إنشاء جديد</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($actions['quran'] as $action)
                            @include('filament.widgets.partials.control-panel-action', ['action' => $action])
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ======================== ACADEMIC TAB ======================== --}}
            <div x-show="activeTab === 'academic'" x-cloak class="mt-4 space-y-5">
                {{-- Pending Items --}}
                @php $academicPending = collect($academic['pending'])->filter(fn($item) => $item['count'] > 0); @endphp
                @if($academicPending->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-warning-500 flex-shrink-0" />
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">بنود معلقة</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($academicPending as $item)
                                @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Today's Sessions --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-s-calendar-days class="w-4 h-4 text-info-500 flex-shrink-0" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">جلسات اليوم</h3>
                    </div>
                    @php $hasAcademicSessions = ($academic['sessions']['academic']['total'] ?? 0) + ($academic['sessions']['interactive']['total'] ?? 0) > 0; @endphp
                    @if($hasAcademicSessions)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            @foreach($academic['sessions'] as $sessionType)
                                @include('filament.widgets.partials.control-panel-session-card', ['session' => $sessionType])
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500 dark:text-gray-400">لا توجد جلسات أكاديمية مجدولة لليوم</p>
                    @endif
                </div>

                {{-- Quick Actions --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-s-plus-circle class="w-4 h-4 text-info-500 flex-shrink-0" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">إنشاء جديد</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @foreach($actions['academic'] as $action)
                            @include('filament.widgets.partials.control-panel-action', ['action' => $action])
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ======================== GENERAL TAB ======================== --}}
            <div x-show="activeTab === 'general'" x-cloak class="mt-4 space-y-5">
                {{-- Pending Items --}}
                @php $generalPending = collect($general['pending'])->filter(fn($item) => $item['count'] > 0); @endphp
                @if($generalPending->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-danger-500 flex-shrink-0" />
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">بنود معلقة</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($generalPending as $item)
                                @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Inactive Users --}}
                @php $inactiveWithCount = collect($general['inactiveUsers'])->filter(fn($item) => $item['count'] > 0); @endphp
                @if($inactiveWithCount->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-2 mb-3">
                            <x-heroicon-s-user-minus class="w-4 h-4 text-gray-400 flex-shrink-0" />
                            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">مستخدمين غير نشطين</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                            @foreach($inactiveWithCount as $item)
                                @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-s-plus-circle class="w-4 h-4 text-primary-500 flex-shrink-0" />
                        <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300">إنشاء جديد</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($actions['general'] as $action)
                            @include('filament.widgets.partials.control-panel-action', ['action' => $action])
                        @endforeach
                    </div>
                </div>
            </div>

        </x-filament::section>
    </div>
</x-filament-widgets::widget>
