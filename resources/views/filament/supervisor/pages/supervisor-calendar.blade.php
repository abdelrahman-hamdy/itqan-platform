<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Teacher Selection Section --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-user-circle class="w-5 h-5 text-primary-500" />
                    <span>اختيار المعلم</span>
                </div>
            </x-slot>

            <x-slot name="description">
                اختر المعلم لعرض وإدارة جلساته في التقويم
            </x-slot>

            @if($this->hasAssignedTeachers())
                <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                    <div class="w-full sm:w-72">
                        <select
                            wire:model.live="selectedTeacherKey"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option value="">-- اختر المعلم --</option>
                            @foreach($this->getTeacherOptions() as $key => $label)
                                <option value="{{ $key }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($this->hasSelectedTeacher())
                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-success-500" />
                            <span>عرض تقويم: <strong class="text-gray-900 dark:text-gray-100">{{ $this->getSelectedTeacherName() }}</strong></span>
                        </div>
                    @endif
                </div>
            @else
                <div class="text-center py-8">
                    <div class="w-16 h-16 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                        <x-heroicon-o-user-minus class="w-8 h-8 text-gray-400" />
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">لا يوجد معلمون معينون</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">تواصل مع المسؤول لتعيين معلمين لك</p>
                </div>
            @endif
        </x-filament::section>

        {{-- Teacher Resources Section --}}
        @if($this->hasSelectedTeacher())
            @php
                $resourceTabs = $this->getResourceTabConfiguration();
            @endphp

            @if(count($resourceTabs) > 0)
                <x-filament::section collapsible>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-folder-open class="w-5 h-5 text-primary-500" />
                            <span>{{ $this->getResourceSectionHeading() }}</span>
                        </div>
                    </x-slot>

                    <x-slot name="description">
                        {{ $this->getResourceSectionDescription() }}
                    </x-slot>

                    {{-- Resource Tabs --}}
                    <x-filament::tabs label="أنواع الموارد">
                        @foreach($resourceTabs as $tabKey => $tabConfig)
                            <x-filament::tabs.item
                                :active="$activeResourceTab === $tabKey"
                                wire:click="setActiveResourceTab('{{ $tabKey }}')"
                                :icon="$tabConfig['icon']"
                            >
                                {{ $tabConfig['label'] }}
                            </x-filament::tabs.item>
                        @endforeach
                    </x-filament::tabs>

                    {{-- Resource Items Grid --}}
                    <div class="mt-6">
                        @include('filament.shared.partials.schedulable-items-grid', [
                            'items' => $this->resourceItems,
                            'selectedItemId' => $selectedItemId,
                            'selectedItemType' => $selectedItemType,
                        ])

                        {{-- Schedule Action Button --}}
                        @if($selectedItemId)
                            <div class="mt-6 flex justify-center">
                                {{ $this->scheduleAction }}
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endif
        @endif

        {{-- Calendar Section --}}
        @if($this->hasSelectedTeacher())
            @php
                $legendData = $this->getColorLegendData();
            @endphp

            {{-- Timezone Information --}}
            <div class="mb-6 rounded-xl bg-gradient-to-r from-primary-50 to-primary-100 dark:from-primary-950 dark:to-primary-900 p-5 shadow-md border-2 border-primary-200 dark:border-primary-800" wire:poll.60s>
                <div class="flex items-center justify-center gap-6 text-center">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-globe-alt class="w-7 h-7 text-primary-600 dark:text-primary-400" />
                        <span class="text-lg font-bold text-primary-900 dark:text-primary-100">{{ $this->getTimezoneNotice() }}</span>
                    </div>
                    <div class="h-8 w-px bg-primary-300 dark:bg-primary-700"></div>
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-clock class="w-7 h-7 text-primary-600 dark:text-primary-400" />
                        <span class="text-lg font-bold text-primary-900 dark:text-primary-100">{{ $this->getCurrentTimeDisplay() }}</span>
                    </div>
                </div>
            </div>

            {{-- Calendar Widget --}}
            <div wire:key="calendar-{{ $this->selectedTeacherId }}-{{ $this->selectedTeacherType }}">
                @livewire(\App\Filament\Supervisor\Widgets\SupervisorCalendarWidget::class, [
                    'selectedTeacherId' => $this->selectedTeacherId,
                    'selectedTeacherType' => $this->selectedTeacherType,
                ], key('supervisor-calendar-widget-' . $this->selectedTeacherId . '-' . $this->selectedTeacherType))
            </div>

            {{-- Color Legend Section --}}
            <x-filament::section>
                <div class="space-y-3">
                    {{-- Status Colors Legend --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <x-heroicon-m-swatch class="w-3.5 h-3.5" />
                            الوان الجلسات على التقويم
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            @foreach($legendData['statusIndicators'] as $status)
                                <div class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-gray-800 rounded-md border border-gray-200 dark:border-gray-700 shadow-sm">
                                    <span
                                        class="w-3 h-3 rounded-full flex-shrink-0"
                                        style="background-color: {{ $status['color'] }}"
                                    ></span>
                                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                        {{ $status['label'] }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Session Types --}}
                    @if(count($legendData['sessionTypes']) > 0)
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <x-heroicon-m-squares-2x2 class="w-3.5 h-3.5" />
                                انواع الجلسات
                            </h4>
                            <div class="flex flex-wrap gap-3">
                                @foreach($legendData['sessionTypes'] as $type)
                                    <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-400">
                                        @if(isset($type['icon']))
                                            <x-dynamic-component
                                                :component="$type['icon']"
                                                class="w-4 h-4"
                                                style="color: {{ $type['color'] }}"
                                            />
                                        @endif
                                        <span>{{ $type['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Calendar Hints --}}
                    <div class="flex flex-wrap justify-center gap-4 text-xs text-gray-500 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-m-cursor-arrow-rays class="w-3.5 h-3.5" />
                            <span>اسحب لاعادة الجدولة</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-m-arrows-pointing-out class="w-3.5 h-3.5" />
                            <span>اسحب الحواف لتغيير المدة</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <x-heroicon-m-cursor-arrow-ripple class="w-3.5 h-3.5" />
                            <span>اضغط على الجلسة لعرض التفاصيل</span>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        @else
            {{-- No Teacher Selected --}}
            <x-filament::section>
                <div class="text-center py-16">
                    <div class="w-20 h-20 mx-auto bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-6">
                        <x-heroicon-o-calendar class="w-10 h-10 text-gray-400" />
                    </div>
                    <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100 mb-2">اختر معلم لعرض التقويم</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-md mx-auto">
                        بعد اختيار المعلم من القائمة اعلاه، سيتم عرض جميع جلساته في التقويم وستتمكن من ادارتها
                    </p>
                </div>
            </x-filament::section>
        @endif
    </div>

    {{-- Shared Calendar Styles --}}
    @include('filament.shared.partials.calendar-styles')

    {{-- Shared Item Selection JavaScript --}}
    @include('filament.shared.partials.calendar-item-selection')
</x-filament-panels::page>
