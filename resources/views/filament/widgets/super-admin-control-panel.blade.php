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
                        <x-heroicon-o-command-line class="w-5 h-5 text-amber-500" />
                        <span>لوحة التحكم - {{ $academyName }}</span>
                    </div>
                    @if($totalPending > 0)
                        <x-filament::badge color="danger" size="lg">
                            {{ $totalPending }} بند معلق
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="success" size="lg">
                            لا توجد معلقات
                        </x-filament::badge>
                    @endif
                </div>
            </x-slot>

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
                    badge-color="warning"
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
            <div x-show="activeTab === 'quran'" x-cloak class="mt-4 space-y-4">
                {{-- Period Filter --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach($periodLabels as $key => $label)
                        <button
                            wire:click="$set('quranPeriod', '{{ $key }}')"
                            @class([
                                'px-3 py-1 text-xs font-bold rounded-full transition-all',
                                'bg-emerald-500 text-white shadow-sm' => $quranPeriod === $key,
                                'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 ring-1 ring-gray-200 dark:ring-gray-600' => $quranPeriod !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Sessions Summary --}}
                <div>
                    @if($quran['sessions']['total'] > 0)
                        @include('filament.widgets.partials.control-panel-session-card', ['session' => $quran['sessions']])
                    @else
                        <div class="flex items-center gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-400" />
                            <span class="text-sm text-gray-500 dark:text-gray-400">لا توجد جلسات قرآن {{ $periodLabels[$quranPeriod] }}</span>
                        </div>
                    @endif
                </div>

                {{-- Pending Items --}}
                @php $quranPending = collect($quran['pending'])->filter(fn($item) => $item['count'] > 0); @endphp
                @if($quranPending->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-1.5 mb-2.5">
                            <x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" />
                            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">بنود معلقة</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                            @foreach($quranPending as $item)
                                @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div>
                    <div class="flex items-center gap-1.5 mb-2.5">
                        <x-heroicon-s-plus-circle class="w-3.5 h-3.5 text-emerald-500 flex-shrink-0" />
                        <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">إنشاء جديد</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($actions['quran'] as $action)
                            @include('filament.widgets.partials.control-panel-action', ['action' => $action])
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ======================== ACADEMIC TAB ======================== --}}
            <div x-show="activeTab === 'academic'" x-cloak class="mt-4 space-y-4">
                {{-- Period Filter --}}
                <div class="flex items-center gap-1.5 flex-wrap">
                    @foreach($periodLabels as $key => $label)
                        <button
                            wire:click="$set('academicPeriod', '{{ $key }}')"
                            @class([
                                'px-3 py-1 text-xs font-bold rounded-full transition-all',
                                'bg-blue-500 text-white shadow-sm' => $academicPeriod === $key,
                                'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 ring-1 ring-gray-200 dark:ring-gray-600' => $academicPeriod !== $key,
                            ])
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Sessions Summary --}}
                <div>
                    @php $hasAcademicSessions = ($academic['sessions']['academic']['total'] ?? 0) + ($academic['sessions']['interactive']['total'] ?? 0) > 0; @endphp
                    @if($hasAcademicSessions)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($academic['sessions'] as $sessionType)
                                @include('filament.widgets.partials.control-panel-session-card', ['session' => $sessionType])
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center gap-2 p-3 rounded-lg bg-gray-50 dark:bg-gray-800">
                            <x-heroicon-o-calendar-days class="w-4 h-4 text-gray-400" />
                            <span class="text-sm text-gray-500 dark:text-gray-400">لا توجد جلسات أكاديمية {{ $periodLabels[$academicPeriod] }}</span>
                        </div>
                    @endif
                </div>

                {{-- Pending Items --}}
                @php $academicPending = collect($academic['pending'])->filter(fn($item) => $item['count'] > 0); @endphp
                @if($academicPending->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-1.5 mb-2.5">
                            <x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-amber-500 flex-shrink-0" />
                            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">بنود معلقة</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                            @foreach($academicPending as $item)
                                @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div>
                    <div class="flex items-center gap-1.5 mb-2.5">
                        <x-heroicon-s-plus-circle class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                        <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">إنشاء جديد</h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        @foreach($actions['academic'] as $action)
                            @include('filament.widgets.partials.control-panel-action', ['action' => $action])
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- ======================== GENERAL TAB ======================== --}}
            <div x-show="activeTab === 'general'" x-cloak class="mt-4 space-y-4">
                {{-- Pending Items --}}
                @php $generalPending = collect($general['pending'])->filter(fn($item) => $item['count'] > 0); @endphp
                @if($generalPending->isNotEmpty())
                    <div>
                        <div class="flex items-center gap-1.5 mb-2.5">
                            <x-heroicon-s-exclamation-triangle class="w-3.5 h-3.5 text-red-500 flex-shrink-0" />
                            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">بنود معلقة</h3>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
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
                        <div class="flex items-center gap-1.5 mb-2.5">
                            <x-heroicon-s-user-minus class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" />
                            <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">مستخدمون غير نشطين</h3>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                            @foreach($inactiveWithCount as $item)
                                @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Quick Actions --}}
                <div>
                    <div class="flex items-center gap-1.5 mb-2.5">
                        <x-heroicon-s-plus-circle class="w-3.5 h-3.5 text-indigo-500 flex-shrink-0" />
                        <h3 class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wide">إنشاء جديد</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($actions['general'] as $action)
                            @include('filament.widgets.partials.control-panel-action', ['action' => $action])
                        @endforeach
                    </div>
                </div>
            </div>

        </x-filament::section>
    </div>
</x-filament-widgets::widget>
