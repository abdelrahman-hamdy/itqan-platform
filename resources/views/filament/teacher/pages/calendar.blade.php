<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Session Statistics Section --}}
        <x-filament::grid default="1" md="2" lg="4" class="gap-4">
            @foreach ($this->getSessionStatistics() as $stat)
                <x-filament::grid.column>
                    <x-filament::card>
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-500">
                                    {{ $stat['title'] }}
                                </h3>
                                <div class="mt-1">
                                    <span class="text-2xl font-bold text-gray-900">{{ $stat['value'] }}</span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 ml-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-{{ $stat['color'] }}-100">
                                    <x-dynamic-component 
                                        :component="$stat['icon']" 
                                        class="w-5 h-5 text-{{ $stat['color'] }}-600" 
                                    />
                                </div>
                            </div>
                        </div>
                    </x-filament::card>
                </x-filament::grid.column>
            @endforeach
        </x-filament::grid>

        {{-- Circles Management Section --}}
        <x-filament::section>
            <x-slot name="heading">
                إدارة الحلقات
            </x-slot>
            
            <x-slot name="description">
                اختر حلقة لجدولة جلساتها على التقويم
            </x-slot>
            
            

            {{-- Tabs using Filament --}}
            <x-filament::tabs label="أنواع الحلقات">
                <x-filament::tabs.item 
                    :active="$activeTab === 'group'"
                    wire:click="setActiveTab('group')"
                    icon="heroicon-m-user-group"
                >
                    الحلقات الجماعية
                </x-filament::tabs.item>
                
                <x-filament::tabs.item 
                    :active="$activeTab === 'individual'"
                    wire:click="setActiveTab('individual')"
                    icon="heroicon-m-user"
                >
                    الحلقات الفردية
                </x-filament::tabs.item>
            </x-filament::tabs>



            <div class="mt-6">
                {{-- Group Circles Tab --}}
                @if ($activeTab === 'group')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->getGroupCircles() as $circle)
                            <div 
                                wire:click="selectCircle({{ $circle['id'] }}, 'group')"
                                class="cursor-pointer transition-all duration-200 circle-card"
                                data-circle-id="{{ $circle['id'] }}"
                                data-circle-type="group"
                            >
                                <x-filament::card 
                                    class="border-2 border-gray-200 hover:ring-2 hover:ring-primary-300 hover:shadow-md transition-all duration-200"
                                    style="{{ $selectedCircleId === $circle['id'] ? 'border: 4px solid #3b82f6 !important; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3) !important; background-color: #eff6ff !important;' : '' }}"
                                >
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-lg font-medium text-gray-900">{{ $circle['name'] }}</h4>
                                        <x-filament::badge 
                                            :color="$circle['status'] === 'scheduled' ? 'success' : 'warning'"
                                        >
                                            {{ $circle['status'] === 'scheduled' ? 'مجدولة' : 'غير مجدولة' }}
                                        </x-filament::badge>
                                    </div>
                                    
                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-calendar-days class="w-4 h-4" />
                                            <span>عدد الجلسات: {{ $circle['sessions_count'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-users class="w-4 h-4" />
                                            <span>عدد الطلاب: {{ $circle['students_count'] }}/{{ $circle['max_students'] }}</span>
                                        </div>
                                        @if ($circle['status'] === 'scheduled')
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-clock class="w-4 h-4" />
                                                <span>الوقت: {{ $circle['schedule_time'] }}</span>
                                            </div>
                                        @endif
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-chart-bar class="w-4 h-4" />
                                            <span>الجلسات الشهرية: {{ $circle['monthly_sessions'] ?? 'غير محدد' }}</span>
                                        </div>
                                    </div>
                                    

                                </div>
                                </x-filament::card>
                            </div>
                        @empty
                            <div class="col-span-full">
                                <x-filament::section>
                                    <div class="text-center py-12">
                                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                            <x-heroicon-o-user-group class="w-8 h-8 text-gray-400" />
                                        </div>
                                        <h3 class="mt-2 text-lg font-medium text-gray-900">لا توجد حلقات جماعية</h3>
                                        <p class="mt-1 text-sm text-gray-500">سيتم عرض الحلقات الجماعية المخصصة لك هنا</p>
                                    </div>
                                </x-filament::section>
                            </div>
                        @endforelse
                    </div>
                    
                    {{-- Schedule Action Button for Group Circles --}}
                    @if($selectedCircleId && $selectedCircleType === 'group')
                        <div class="mt-6 flex justify-center">
                            {{ $this->scheduleAction }}
                        </div>
                    @endif
                @endif

                {{-- Individual Circles Tab --}}
                @if ($activeTab === 'individual')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->getIndividualCircles() as $circle)
                            <div 
                                wire:click="selectCircle({{ $circle['id'] }}, 'individual')"
                                class="cursor-pointer transition-all duration-200 circle-card"
                                data-circle-id="{{ $circle['id'] }}"
                                data-circle-type="individual"
                            >
                                <x-filament::card 
                                    class="border-2 border-gray-200 hover:ring-2 hover:ring-primary-300 hover:shadow-md transition-all duration-200"
                                    style="{{ $selectedCircleId === $circle['id'] ? 'border: 4px solid #3b82f6 !important; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3) !important; background-color: #eff6ff !important;' : '' }}"
                                >
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-lg font-medium text-gray-900">{{ $circle['name'] }}</h4>
                                        <x-filament::badge 
                                            :color="$circle['status'] === 'fully_scheduled' ? 'success' : ($circle['status'] === 'partially_scheduled' ? 'info' : 'warning')"
                                        >
                                            @if ($circle['status'] === 'fully_scheduled')
                                                مكتملة الجدولة
                                            @elseif ($circle['status'] === 'partially_scheduled')
                                                مجدولة جزئياً
                                            @else
                                                غير مجدولة
                                            @endif
                                        </x-filament::badge>
                                    </div>
                                    
                                    <div class="space-y-2 text-sm text-gray-600">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-user class="w-4 h-4" />
                                            <span>الطالب: {{ $circle['student_name'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-calendar-days class="w-4 h-4" />
                                            <span>إجمالي الجلسات: {{ $circle['sessions_count'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                            <span>المجدولة: {{ $circle['sessions_scheduled'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-m-clock class="w-4 h-4 text-orange-500" />
                                            <span>المتبقية: {{ $circle['sessions_remaining'] }}</span>
                                        </div>
                                        @if ($circle['subscription_start'])
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-play class="w-4 h-4" />
                                                <span>البداية: {{ $circle['subscription_start']->format('Y/m/d') }}</span>
                                            </div>
                                        @endif
                                        @if ($circle['subscription_end'])
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-stop class="w-4 h-4" />
                                                <span>الانتهاء: {{ $circle['subscription_end']->format('Y/m/d') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    

                                </div>
                                </x-filament::card>
                            </div>
                        @empty
                            <div class="col-span-full">
                                <x-filament::section>
                                    <div class="text-center py-12">
                                        <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                            <x-heroicon-o-user class="w-8 h-8 text-gray-400" />
                                        </div>
                                        <h3 class="mt-2 text-lg font-medium text-gray-900">لا توجد حلقات فردية</h3>
                                        <p class="mt-1 text-sm text-gray-500">سيتم عرض الحلقات الفردية المخصصة لك هنا</p>
                                    </div>
                                </x-filament::section>
                            </div>
                        @endforelse
                    </div>
                    
                    {{-- Schedule Action Button for Individual Circles --}}
                    @if($selectedCircleId && $selectedCircleType === 'individual')
                        <div class="mt-6 flex justify-center">
                            {{ $this->scheduleAction }}
                        </div>
                    @endif
                @endif


            </div>
        </x-filament::section>

        {{-- Calendar widget will render in footer widgets automatically --}}
    </div>

    {{-- JavaScript for enhanced circle selection --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enhanced circle selection with immediate visual feedback
            document.addEventListener('click', function(e) {
                const circleCard = e.target.closest('.circle-card');
                if (circleCard) {
                    // Remove previous selections
                    document.querySelectorAll('.circle-card').forEach(card => {
                        const cardElement = card.querySelector('.fi-card');
                        if (cardElement) {
                            cardElement.style.border = '2px solid #d1d5db';
                            cardElement.style.boxShadow = '';
                            cardElement.style.backgroundColor = '';
                        }
                    });
                    
                    // Add bold border to selected circle immediately
                    const selectedCard = circleCard.querySelector('.fi-card');
                    if (selectedCard) {
                        selectedCard.style.border = '4px solid #3b82f6';
                        selectedCard.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.3)';
                        selectedCard.style.backgroundColor = '#eff6ff';
                    }
                }
            });
            
            // Debug: Log when individual circles tab is clicked
            const individualTab = document.querySelector('[wire\\:click="setActiveTab(\'individual\')"]');
            if (individualTab) {
                individualTab.addEventListener('click', function() {
                    console.log('Individual circles tab clicked');
                    setTimeout(() => {
                        const individualCircles = document.querySelectorAll('[data-circle-type="individual"]');
                        console.log('Individual circles found:', individualCircles.length);
                    }, 100);
                });
            }
        });
        
        // Livewire hook for when the page updates
        document.addEventListener('livewire:navigated', function() {
            console.log('Livewire navigated - checking circles');
            setTimeout(() => {
                const groupCircles = document.querySelectorAll('[data-circle-type="group"]');
                const individualCircles = document.querySelectorAll('[data-circle-type="individual"]');
                console.log('Group circles:', groupCircles.length);
                console.log('Individual circles:', individualCircles.length);
            }, 100);
        });
    </script>

    {{-- Modal is handled by the Filament Action --}}
</x-filament-panels::page>