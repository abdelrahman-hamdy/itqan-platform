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
            <x-filament::tabs label="أنواع الحلقات والجلسات">
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
                
                <x-filament::tabs.item 
                    :active="$activeTab === 'trial'"
                    wire:click="setActiveTab('trial')"
                    icon="heroicon-m-clock"
                >
                    الجلسات التجريبية
                </x-filament::tabs.item>
            </x-filament::tabs>



            <div class="mt-6">
                {{-- Group Circles Tab --}}
                @if ($activeTab === 'group')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->getGroupCircles() as $circle)
                            <div 
                                wire:click="selectCircle({{ $circle['id'] }}, 'group')"
                                class="cursor-pointer transition-all duration-200 circle-card {{ $selectedCircleId === $circle['id'] ? 'circle-selected' : '' }}"
                                data-circle-id="{{ $circle['id'] }}"
                                data-circle-type="group"
                            >
                                <x-filament::card 
                                    class="border-2 border-gray-200 hover:ring-2 hover:ring-primary-300 hover:shadow-md transition-all duration-200"
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
                                class="cursor-pointer transition-all duration-200 circle-card {{ $selectedCircleId === $circle['id'] ? 'circle-selected' : '' }}"
                                data-circle-id="{{ $circle['id'] }}"
                                data-circle-type="individual"
                            >
                                <x-filament::card 
                                    class="border-2 border-gray-200 hover:ring-2 hover:ring-primary-300 hover:shadow-md transition-all duration-200"
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

                {{-- Trial Sessions Tab --}}
                @if ($activeTab === 'trial')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->getTrialRequests() as $trialRequest)
                            <div 
                                wire:click="selectTrialRequest({{ $trialRequest['id'] }})"
                                class="cursor-pointer transition-all duration-200 trial-card {{ $selectedTrialRequestId === $trialRequest['id'] ? 'trial-selected' : '' }}"
                                data-trial-id="{{ $trialRequest['id'] }}"
                            >
                                <x-filament::card 
                                    class="border-2 border-gray-200 hover:ring-2 hover:ring-yellow-300 hover:shadow-md transition-all duration-200"
                                >
                                    <div class="space-y-3">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h3 class="text-lg font-semibold text-gray-900">
                                                    {{ $trialRequest['student_name'] }}
                                                </h3>
                                                <p class="text-sm text-gray-600">
                                                    المستوى: {{ $trialRequest['level_label'] }}
                                                </p>
                                            </div>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $trialRequest['status'] === 'scheduled' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($trialRequest['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}">
                                                {{ $trialRequest['status_label'] }}
                                            </span>
                                        </div>
                                        
                                        <div class="space-y-2 text-sm text-gray-600">
                                            <div class="flex items-center">
                                                <x-heroicon-o-clock class="w-4 h-4 ml-2 text-gray-400"/>
                                                <span>الوقت المفضل: {{ $trialRequest['preferred_time_label'] }}</span>
                                            </div>
                                            @if($trialRequest['scheduled_at'])
                                                <div class="flex items-center">
                                                    <x-heroicon-o-calendar class="w-4 h-4 ml-2 text-gray-400"/>
                                                    <span>موعد الجلسة: {{ $trialRequest['scheduled_at_formatted'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        @if($trialRequest['notes'])
                                            <div class="text-sm text-gray-600 bg-gray-50 p-2 rounded mt-3">
                                                <div class="flex items-start">
                                                    <x-heroicon-o-chat-bubble-left-ellipsis class="w-4 h-4 ml-2 mt-0.5 text-gray-400 flex-shrink-0"/>
                                                    <div>
                                                        <strong>ملاحظات الطالب:</strong>
                                                        <p class="mt-1">{{ Str::limit($trialRequest['notes'], 150) }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </x-filament::card>
                            </div>
                        @empty
                            <div class="col-span-full">
                                <x-filament::section>
                                    <div class="text-center py-12">
                                        <x-heroicon-o-clock class="mx-auto h-12 w-12 text-gray-400"/>
                                        <h3 class="mt-2 text-lg font-medium text-gray-900">لا توجد جلسات تجريبية</h3>
                                        <p class="mt-1 text-sm text-gray-500">سيتم عرض طلبات الجلسات التجريبية المخصصة لك هنا</p>
                                    </div>
                                </x-filament::section>
                            </div>
                        @endforelse
                    </div>
                    
                    {{-- Schedule Action Button for Trial Sessions --}}
                    @if($selectedTrialRequestId)
                        <div class="mt-6 flex justify-center">
                            <div wire:loading.remove>
                                {{ $this->scheduleTrialAction }}
                            </div>
                            <div wire:loading class="text-center">
                                <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-gray-500 bg-white transition ease-in-out duration-150 cursor-not-allowed">
                                    <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    جاري التحميل...
                                </div>
                            </div>
                        </div>
                    @endif
                @endif


            </div>
        </x-filament::section>

        {{-- Calendar widget will render in footer widgets automatically --}}
    </div>

    {{-- CSS for circle and trial session selection --}}
    <style>
        .circle-card, .trial-card {
            transition: all 0.3s ease !important;
            position: relative;
        }
        
        .circle-card .fi-card, .trial-card .fi-card {
            transition: all 0.3s ease !important;
        }
        
        .circle-selected .fi-card {
            border-width: 2px !important;
            border-color: #60a5fa !important; /* blue-400 */
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25) !important; /* subtle ring */
        }

        .trial-selected .fi-card {
            border-width: 2px !important;
            border-color: #60a5fa !important; /* blue-400 */
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25) !important; /* subtle ring */
        }

        .circle-card.circle-selected .fi-section-content {
            border: solid 1px #60a5fa !important; /* blue-600 */
            border-radius: 10px;
            background-color: #3d485b24 !important; /* blue-50 */
        }

        .trial-card.trial-selected .fi-section-content {
            border: solid 1px #60a5fa !important; /* blue-600 */
            border-radius: 10px;
            background-color: #3d485b24 !important; /* blue-50 */
        }

        /* Calendar indicators styling */
        .event-passed {
            text-decoration: line-through !important;
        }

    </style>

    {{-- JavaScript for GUARANTEED circle selection --}}
    <script>
        function makeCircleSelected(circleId, circleType) {
            console.log('🎯 Making circle selected:', circleId, circleType);
            
            // Remove all selections first
            document.querySelectorAll('.circle-card').forEach(card => {
                card.classList.remove('circle-selected');
                const cardElement = card.querySelector('.fi-card');
                if (cardElement) {
                    cardElement.style.border = '';
                    cardElement.style.backgroundColor = '';
                    cardElement.style.boxShadow = '';
                }
            });
            
            // Find and select the target circle
            const targetCard = document.querySelector(`[data-circle-id="${circleId}"][data-circle-type="${circleType}"]`);
            if (targetCard) {
                targetCard.classList.add('circle-selected');
                console.log('✅ Applied circle-selected class');
                
                // Force styles as backup
                const cardElement = targetCard.querySelector('.fi-card');
                if (cardElement) {
                    // Use setProperty with !important to override Filament styles
                    cardElement.style.setProperty('border', '2px solid #60a5fa', 'important');
                    cardElement.style.setProperty('background-color', '#eff6ff', 'important');
                    cardElement.style.setProperty('box-shadow', '0 0 0 3px rgba(96, 165, 250, 0.25)', 'important');
                }
                // Persist selection to reapply after Livewire DOM updates
                window.__teacherCalendarSelection = { id: String(circleId), type: String(circleType) };
            }
        }
        
        function makeTrialSelected(trialId) {
            console.log('🎯 Making trial selected:', trialId);
            
            // Remove all trial selections first
            document.querySelectorAll('.trial-card').forEach(card => {
                card.classList.remove('trial-selected');
                const cardElement = card.querySelector('.fi-card');
                if (cardElement) {
                    cardElement.style.border = '';
                    cardElement.style.backgroundColor = '';
                    cardElement.style.boxShadow = '';
                }
            });
            
            // Find target trial card and apply selection
            const targetCard = document.querySelector(`[data-trial-id="${trialId}"]`);
            if (targetCard) {
                targetCard.classList.add('trial-selected');
                console.log('✅ Applied trial-selected class');
                
                // Force styles as backup
                const cardElement = targetCard.querySelector('.fi-card');
                if (cardElement) {
                    cardElement.style.setProperty('border', '2px solid #60a5fa', 'important');
                    cardElement.style.setProperty('background-color', '#eff6ff', 'important');
                    cardElement.style.setProperty('box-shadow', '0 0 0 3px rgba(96, 165, 250, 0.25)', 'important');
                }
                // Persist selection
                window.__teacherTrialSelection = { id: String(trialId) };
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Circle and Trial selection system initialized');
            
            // Enhanced click handler for circles and trials
            document.addEventListener('click', function(e) {
                const circleCard = e.target.closest('.circle-card');
                const trialCard = e.target.closest('.trial-card');
                
                if (circleCard) {
                    const circleId = circleCard.getAttribute('data-circle-id');
                    const circleType = circleCard.getAttribute('data-circle-type');
                    
                    if (circleId && circleType) {
                        makeCircleSelected(circleId, circleType);
                    }
                } else if (trialCard) {
                    const trialId = trialCard.getAttribute('data-trial-id');
                    
                    if (trialId) {
                        makeTrialSelected(trialId);
                    }
                }
            });
            
            // Listen for Livewire updates and reapply selection
            const reapply = () => {
                setTimeout(() => {
                    // Reapply circle selections
                    const selectedCards = document.querySelectorAll('.circle-selected');
                    if (selectedCards.length > 0) {
                        selectedCards.forEach(card => {
                            const circleId = card.getAttribute('data-circle-id');
                            const circleType = card.getAttribute('data-circle-type');
                            if (circleId && circleType) makeCircleSelected(circleId, circleType);
                        });
                    } else if (window.__teacherCalendarSelection && window.__teacherCalendarSelection.id) {
                        makeCircleSelected(window.__teacherCalendarSelection.id, window.__teacherCalendarSelection.type);
                    }
                    
                    // Reapply trial selections
                    const selectedTrialCards = document.querySelectorAll('.trial-selected');
                    if (selectedTrialCards.length > 0) {
                        selectedTrialCards.forEach(card => {
                            const trialId = card.getAttribute('data-trial-id');
                            if (trialId) makeTrialSelected(trialId);
                        });
                    } else if (window.__teacherTrialSelection && window.__teacherTrialSelection.id) {
                        makeTrialSelected(window.__teacherTrialSelection.id);
                    }
                }, 50);
            };
            ['livewire:updated','livewire:load','livewire:message.processed','livewire:navigated'].forEach(evt => {
                document.addEventListener(evt, reapply);
            });
            
            // Add event listener for calendar events to handle passed sessions styling
            document.addEventListener('livewire:navigated', function() {
                setTimeout(applyPassedEventStyling, 500);
            });
        });
        
        // Function to apply strikethrough styling to passed events
        function applyPassedEventStyling() {
            // Wait for calendar to be rendered
            const calendar = document.querySelector('.fc');
            if (!calendar) return;
            
            // Find all events and check if they're passed
            const events = calendar.querySelectorAll('.fc-event');
            events.forEach(event => {
                // Get event data from the event element
                const eventTitle = event.querySelector('.fc-event-title');
                if (eventTitle) {
                    // Check if the event has passed based on its position in past time slots
                    const eventEl = event.closest('.fc-event');
                    const eventData = eventEl?._fcEvent;
                    
                    if (eventData && eventData.extendedProps?.isPassed) {
                        eventTitle.style.textDecoration = 'line-through';
                    }
                }
            });
        }
        
        // Also apply styling when calendar events are rendered
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(applyPassedEventStyling, 1000);
            
            // Apply styling on any calendar updates
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        const hasCalendarEvents = Array.from(mutation.addedNodes).some(node => 
                            node.nodeType === 1 && (
                                node.classList?.contains('fc-event') || 
                                node.querySelector?.('.fc-event')
                            )
                        );
                        if (hasCalendarEvents) {
                            setTimeout(applyPassedEventStyling, 100);
                        }
                    }
                });
            });
            
            const calendarContainer = document.querySelector('.fc');
            if (calendarContainer) {
                observer.observe(calendarContainer, { childList: true, subtree: true });
            }
        });
    </script>

    {{-- Modal is handled by the Filament Action --}}
</x-filament-panels::page>