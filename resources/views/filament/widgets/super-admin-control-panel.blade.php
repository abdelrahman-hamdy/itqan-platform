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
                    <div class="text-xl font-extrabold {{ $quickStats['total_pending'] > 0 ? 'text-danger-600 dark:text-danger-400' : 'text-success-600 dark:text-success-400' }}">
                        {{ $quickStats['total_pending'] }}
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">بنود معلقة</div>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="text-xl font-extrabold text-info-600 dark:text-info-400">
                        {{ $quickStats['today_sessions'] }}
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">جلسات اليوم</div>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="text-xl font-extrabold text-success-600 dark:text-success-400">
                        {{ $quickStats['active_subscriptions'] }}
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">اشتراكات نشطة</div>
                </div>
                <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-3 text-center ring-1 ring-gray-950/5 dark:ring-white/10">
                    <div class="text-xl font-extrabold text-warning-600 dark:text-warning-400">
                        {{ number_format($quickStats['monthly_revenue'], 0) }}
                    </div>
                    <div class="text-xs font-semibold text-gray-600 dark:text-gray-400 mt-0.5">إيرادات الشهر</div>
                </div>
            </div>

            {{-- Tab Navigation --}}
            <x-filament::tabs>
                <x-filament::tabs.item
                    alpine-active="activeTab === 'pending'"
                    x-on:click="activeTab = 'pending'"
                    icon="heroicon-m-clipboard-document-list"
                    :badge="collect($pending)->sum('count') ?: null"
                    badge-color="danger"
                >
                    المعلقات
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    alpine-active="activeTab === 'today'"
                    x-on:click="activeTab = 'today'"
                    icon="heroicon-m-calendar-days"
                    :badge="collect($today)->sum('total') ?: null"
                    badge-color="info"
                >
                    جلسات اليوم
                </x-filament::tabs.item>

                <x-filament::tabs.item
                    alpine-active="activeTab === 'actions'"
                    x-on:click="activeTab = 'actions'"
                    icon="heroicon-m-bolt"
                >
                    إجراءات سريعة
                </x-filament::tabs.item>
            </x-filament::tabs>

            {{-- Tab Content: Pending Items --}}
            <div x-show="activeTab === 'pending'" x-cloak class="mt-4">
                @php
                    $urgentItems = collect($pending)->filter(fn($item) => $item['urgent'] && $item['count'] > 0);
                    $regularItems = collect($pending)->filter(fn($item) => !$item['urgent'] && $item['count'] > 0);
                @endphp

                @if($urgentItems->isNotEmpty())
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-s-exclamation-triangle class="w-4 h-4 text-danger-500 flex-shrink-0" />
                        <h3 class="text-sm font-bold text-danger-600 dark:text-danger-400">تحتاج إجراء عاجل</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3 mb-5">
                        @foreach($urgentItems as $key => $item)
                            @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                        @endforeach
                    </div>
                @endif

                @if($regularItems->isNotEmpty())
                    <div class="flex items-center gap-2 mb-3">
                        <x-heroicon-s-clock class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        <h3 class="text-sm font-bold text-gray-600 dark:text-gray-400">تحتاج متابعة</h3>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                        @foreach($regularItems as $key => $item)
                            @include('filament.widgets.partials.control-panel-card', ['item' => $item])
                        @endforeach
                    </div>
                @endif

                @if($urgentItems->isEmpty() && $regularItems->isEmpty())
                    <x-filament::section>
                        <div class="text-center py-6">
                            <div class="w-14 h-14 mx-auto bg-success-50 dark:bg-success-500/10 rounded-full flex items-center justify-center mb-3">
                                <x-heroicon-o-check-circle class="w-7 h-7 text-success-500" />
                            </div>
                            <p class="text-sm font-bold text-success-600 dark:text-success-400">لا توجد بنود معلقة</p>
                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1">كل شيء تحت السيطرة</p>
                        </div>
                    </x-filament::section>
                @endif
            </div>

            {{-- Tab Content: Today's Sessions --}}
            <div x-show="activeTab === 'today'" x-cloak class="mt-4">
                @if(collect($today)->sum('total') > 0)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        @foreach($today as $type => $session)
                            <div class="rounded-xl bg-gray-50 dark:bg-white/5 p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <x-dynamic-component :component="$session['icon']" class="w-5 h-5 text-{{ $session['color'] }}-500" />
                                        <h4 class="text-sm font-bold text-gray-950 dark:text-white">{{ $session['label'] }}</h4>
                                    </div>
                                    <span class="text-2xl font-extrabold text-gray-950 dark:text-white">{{ $session['total'] }}</span>
                                </div>

                                <div class="space-y-2 text-sm">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-600 dark:text-gray-400">مجدولة</span>
                                        <span class="font-bold text-info-600 dark:text-info-400">{{ $session['scheduled'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-1.5">
                                            @if($session['ongoing'] > 0)
                                                <span class="relative flex h-2 w-2">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span>
                                                </span>
                                            @endif
                                            <span class="font-medium text-gray-600 dark:text-gray-400">جارية الآن</span>
                                        </div>
                                        <span class="font-bold text-primary-600 dark:text-primary-400">{{ $session['ongoing'] }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-gray-600 dark:text-gray-400">مكتملة</span>
                                        <span class="font-bold text-success-600 dark:text-success-400">{{ $session['completed'] }}</span>
                                    </div>
                                    @if($session['cancelled'] > 0)
                                        <div class="flex items-center justify-between">
                                            <span class="font-medium text-gray-600 dark:text-gray-400">ملغاة</span>
                                            <span class="font-bold text-danger-600 dark:text-danger-400">{{ $session['cancelled'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                @if($session['total'] > 0)
                                    @php $pct = round(($session['completed'] / $session['total']) * 100); @endphp
                                    <div class="mt-3">
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                            <div class="bg-success-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-1">{{ $pct }}% مكتملة</p>
                                    </div>
                                @endif

                                <div class="mt-3">
                                    <x-filament::link
                                        :href="$session['url']"
                                        icon="heroicon-m-arrow-left"
                                        icon-position="after"
                                        size="sm"
                                    >
                                        عرض الكل
                                    </x-filament::link>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <div class="w-14 h-14 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3">
                            <x-heroicon-o-calendar-days class="w-7 h-7 text-gray-400" />
                        </div>
                        <p class="text-sm font-bold text-gray-500 dark:text-gray-400">لا توجد جلسات مجدولة لليوم</p>
                    </div>
                @endif
            </div>

            {{-- Tab Content: Quick Actions --}}
            <div x-show="activeTab === 'actions'" x-cloak class="mt-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                    {{-- Quran Section --}}
                    <div class="rounded-xl bg-gray-50 dark:bg-white/5 p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                        <div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-200 dark:border-white/10">
                            <div class="w-8 h-8 bg-success-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-book-open class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-gray-950 dark:text-white">القرآن الكريم</h4>
                        </div>
                        <div class="space-y-1">
                            <x-filament::link href="{{ route('filament.admin.resources.quran-circles.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                حلقات القرآن
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.quran-individual-circles.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                الحلقات الفردية
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.quran-packages.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                باقات القرآن
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.quran-sessions.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                جلسات القرآن
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.quran-teacher-profiles.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                معلمو القرآن
                            </x-filament::link>
                        </div>
                    </div>

                    {{-- Academic Section --}}
                    <div class="rounded-xl bg-gray-50 dark:bg-white/5 p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                        <div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-200 dark:border-white/10">
                            <div class="w-8 h-8 bg-info-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-academic-cap class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-gray-950 dark:text-white">القسم الأكاديمي</h4>
                        </div>
                        <div class="space-y-1">
                            <x-filament::link href="{{ route('filament.admin.resources.academic-teacher-profiles.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                المدرسين الأكاديميين
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.academic-packages.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                الباقات الأكاديمية
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.academic-sessions.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                الجلسات الأكاديمية
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.interactive-courses.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                الدورات التفاعلية
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.recorded-courses.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                الدورات المسجلة
                            </x-filament::link>
                        </div>
                    </div>

                    {{-- Users Section --}}
                    <div class="rounded-xl bg-gray-50 dark:bg-white/5 p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                        <div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-200 dark:border-white/10">
                            <div class="w-8 h-8 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-users class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-gray-950 dark:text-white">المستخدمين</h4>
                        </div>
                        <div class="space-y-1">
                            <x-filament::link href="{{ route('filament.admin.resources.student-profiles.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                الطلاب
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.parent-profiles.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                أولياء الأمور
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.supervisor-profiles.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                المشرفين
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.users.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                جميع المستخدمين
                            </x-filament::link>
                        </div>
                    </div>

                    {{-- Finance Section --}}
                    <div class="rounded-xl bg-gray-50 dark:bg-white/5 p-4 ring-1 ring-gray-950/5 dark:ring-white/10">
                        <div class="flex items-center gap-2 mb-3 pb-3 border-b border-gray-200 dark:border-white/10">
                            <div class="w-8 h-8 bg-warning-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <x-heroicon-o-banknotes class="w-4 h-4 text-white" />
                            </div>
                            <h4 class="text-sm font-bold text-gray-950 dark:text-white">المالية</h4>
                        </div>
                        <div class="space-y-1">
                            <x-filament::link href="{{ route('filament.admin.resources.payments.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                المدفوعات
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.quran-subscriptions.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                اشتراكات القرآن
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.academic-subscriptions.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                الاشتراكات الأكاديمية
                            </x-filament::link>
                            <x-filament::link href="{{ route('filament.admin.resources.teacher-earnings.index') }}" icon="heroicon-m-chevron-left" icon-position="after" class="justify-between w-full">
                                أرباح المعلمين
                            </x-filament::link>
                        </div>
                    </div>
                </div>
            </div>

        </x-filament::section>
    </div>
</x-filament-widgets::widget>
