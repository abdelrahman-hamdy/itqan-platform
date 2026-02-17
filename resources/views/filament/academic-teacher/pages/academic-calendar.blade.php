
<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Session Statistics Section --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach ($this->getSessionStatistics() as $stat)
                <div>
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
                            <div class="flex-shrink-0 ms-4">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-{{ $stat['color'] }}-100">
                                    <x-dynamic-component 
                                        :component="$stat['icon']" 
                                        class="w-5 h-5 text-{{ $stat['color'] }}-600" 
                                    />
                                </div>
                            </div>
                        </div>
                    </x-filament::card>
                </div>
            @endforeach
        </div>

        {{-- Academic Sessions Management Section --}}
        <x-filament::section>
            <x-slot name="heading">
                إدارة الجلسات الأكاديمية
            </x-slot>
            
            <x-slot name="description">
                اختر درس أو دورة لجدولة جلساتها على التقويم
            </x-slot>

            {{-- Tabs using Filament --}}
            <x-filament::tabs label="أنواع الجلسات الأكاديمية">
                <x-filament::tabs.item 
                    :active="$activeTab === 'private_lessons'"
                    wire:click="setActiveTab('private_lessons')"
                    icon="heroicon-m-user"
                >
                    الدروس الفردية
                </x-filament::tabs.item>
                
                <x-filament::tabs.item 
                    :active="$activeTab === 'interactive_courses'"
                    wire:click="setActiveTab('interactive_courses')"
                    icon="heroicon-m-user-group"
                >
                    الدورات التفاعلية
                </x-filament::tabs.item>
            </x-filament::tabs>

            <div class="mt-6">
                {{-- Private Lessons Tab --}}
                @if ($activeTab === 'private_lessons')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->privateLessons as $lesson)
                            <div 
                                wire:click="selectItem({{ $lesson['id'] }}, 'private_lesson')"
                                class="cursor-pointer transition-all duration-200 item-card {{ $selectedItemId === $lesson['id'] && $selectedItemType === 'private_lesson' ? 'item-selected' : '' }}"
                                data-item-id="{{ $lesson['id'] }}"
                                data-item-type="private_lesson"
                            >
                                <x-filament::card 
                                    class="border-2 border-gray-200 hover:ring-2 hover:ring-primary-300 hover:shadow-md transition-all duration-200"
                                >
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-lg font-medium text-gray-900">{{ $lesson['name'] }}</h4>
                                            <x-filament::badge 
                                                :color="$lesson['status'] === 'fully_scheduled' ? 'success' : ($lesson['status'] === 'partially_scheduled' ? 'info' : 'warning')"
                                            >
                                                @if ($lesson['status'] === 'fully_scheduled')
                                                    مكتملة الجدولة
                                                @elseif ($lesson['status'] === 'partially_scheduled')
                                                    مجدولة جزئياً
                                                @else
                                                    غير مجدولة
                                                @endif
                                            </x-filament::badge>
                                        </div>
                                        
                                        <div class="space-y-2 text-sm text-gray-600">
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-user class="w-4 h-4" />
                                                <span>الطالب: {{ $lesson['student_name'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-book-open class="w-4 h-4" />
                                                <span>المادة: {{ $lesson['subject_name'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-calendar-days class="w-4 h-4" />
                                                <span>إجمالي الجلسات: {{ $lesson['total_sessions'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                                <span>المجدولة: {{ $lesson['sessions_scheduled'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-clock class="w-4 h-4 text-orange-500" />
                                                <span>المتبقية: {{ $lesson['sessions_remaining'] }}</span>
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
                                            <x-heroicon-o-user class="w-8 h-8 text-gray-400" />
                                        </div>
                                        <h3 class="mt-2 text-lg font-medium text-gray-900">لا توجد دروس فردية</h3>
                                        <p class="mt-1 text-sm text-gray-500">سيتم عرض الدروس الفردية المخصصة لك هنا</p>
                                    </div>
                                </x-filament::section>
                            </div>
                        @endforelse
                    </div>
                    
                    {{-- Schedule Action Button for Private Lessons --}}
                    @if($selectedItemId && $selectedItemType === 'private_lesson')
                        <div class="mt-6 flex justify-center">
                            {{ $this->scheduleAction }}
                        </div>
                    @endif
                @endif

                {{-- Interactive Courses Tab --}}
                @if ($activeTab === 'interactive_courses')
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @forelse ($this->getInteractiveCourses() as $course)
                            <div 
                                wire:click="selectItem({{ $course['id'] }}, 'interactive_course')"
                                class="cursor-pointer transition-all duration-200 item-card {{ $selectedItemId === $course['id'] && $selectedItemType === 'interactive_course' ? 'item-selected' : '' }}"
                                data-item-id="{{ $course['id'] }}"
                                data-item-type="interactive_course"
                            >
                                <x-filament::card 
                                    class="border-2 border-gray-200 hover:ring-2 hover:ring-primary-300 hover:shadow-md transition-all duration-200"
                                >
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-lg font-medium text-gray-900">{{ $course['title'] }}</h4>
                                            <x-filament::badge 
                                                :color="$course['status'] === 'active' ? 'success' : ($course['status'] === 'published' ? 'info' : 'warning')"
                                            >
                                                {{ $course['status_arabic'] }}
                                            </x-filament::badge>
                                        </div>
                                        
                                        <div class="space-y-2 text-sm text-gray-600">
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-book-open class="w-4 h-4" />
                                                <span>المادة: {{ $course['subject_name'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-users class="w-4 h-4" />
                                                <span>المشتركون: {{ $course['enrolled_students'] }}/{{ $course['max_students'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-calendar-days class="w-4 h-4" />
                                                <span>إجمالي الجلسات: {{ $course['total_sessions'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-check-circle class="w-4 h-4 text-green-500" />
                                                <span>المجدولة: {{ $course['sessions_scheduled'] }}</span>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <x-heroicon-m-clock class="w-4 h-4 text-orange-500" />
                                                <span>المتبقية: {{ $course['sessions_remaining'] }}</span>
                                            </div>
                                            @if ($course['start_date'])
                                                <div class="flex items-center gap-2">
                                                    <x-heroicon-m-play class="w-4 h-4" />
                                                    <span>البداية: {{ $course['start_date'] }}</span>
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
                                            <x-heroicon-o-user-group class="w-8 h-8 text-gray-400" />
                                        </div>
                                        <h3 class="mt-2 text-lg font-medium text-gray-900">لا توجد دورات تفاعلية</h3>
                                        <p class="mt-1 text-sm text-gray-500">سيتم عرض الدورات التفاعلية المخصصة لك هنا</p>
                                    </div>
                                </x-filament::section>
                            </div>
                        @endforelse
                    </div>
                    
                    {{-- Schedule Action Button for Interactive Courses --}}
                    @if($selectedItemId && $selectedItemType === 'interactive_course')
                        <div class="mt-6 flex justify-center">
                            {{ $this->scheduleAction }}
                        </div>
                    @endif
                @endif
            </div>
        </x-filament::section>


        {{-- Calendar widget will render in footer widgets automatically --}}
    </div>

    {{-- CSS for item selection --}}
    <style>
        .item-card {
            transition: all 0.3s ease !important;
            position: relative;
        }
        
        .item-card .fi-card {
            transition: all 0.3s ease !important;
        }
        
        .item-selected .fi-card {
            border-width: 2px !important;
            border-color: #60a5fa !important; /* blue-400 */
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25) !important; /* subtle ring */
        }

        .item-card.item-selected .fi-section-content {
            border: solid 1px #60a5fa !important; /* blue-600 */
            border-radius: 10px;
            background-color: #3d485b24 !important; /* blue-50 */
        }

        /* Calendar indicators styling */
        .event-passed {
            text-decoration: line-through !important;
        }
    </style>

    {{-- JavaScript for GUARANTEED item selection --}}
    <script>
        function makeItemSelected(itemId, itemType) {
            
            // Remove all selections first
            document.querySelectorAll('.item-card').forEach(card => {
                card.classList.remove('item-selected');
                const cardElement = card.querySelector('.fi-card');
                if (cardElement) {
                    cardElement.style.border = '';
                    cardElement.style.backgroundColor = '';
                    cardElement.style.boxShadow = '';
                }
            });
            
            // Find and select the target item
            const targetCard = document.querySelector(`[data-item-id="${itemId}"][data-item-type="${itemType}"]`);
            if (targetCard) {
                targetCard.classList.add('item-selected');
                
                // Force styles as backup
                const cardElement = targetCard.querySelector('.fi-card');
                if (cardElement) {
                    // Use setProperty with !important to override Filament styles
                    cardElement.style.setProperty('border', '2px solid #60a5fa', 'important');
                    cardElement.style.setProperty('background-color', '#eff6ff', 'important');
                    cardElement.style.setProperty('box-shadow', '0 0 0 3px rgba(96, 165, 250, 0.25)', 'important');
                }
                // Persist selection to reapply after Livewire DOM updates
                window.__academicCalendarSelection = { id: String(itemId), type: String(itemType) };
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            
            // Enhanced click handler for items
            document.addEventListener('click', function(e) {
                const itemCard = e.target.closest('.item-card');
                
                if (itemCard) {
                    const itemId = itemCard.getAttribute('data-item-id');
                    const itemType = itemCard.getAttribute('data-item-type');
                    
                    if (itemId && itemType) {
                        makeItemSelected(itemId, itemType);
                    }
                }
            });
            
            // Listen for Livewire updates and reapply selection
            const reapply = () => {
                setTimeout(() => {
                    // Reapply item selections
                    const selectedCards = document.querySelectorAll('.item-selected');
                    if (selectedCards.length > 0) {
                        selectedCards.forEach(card => {
                            const itemId = card.getAttribute('data-item-id');
                            const itemType = card.getAttribute('data-item-type');
                            if (itemId && itemType) makeItemSelected(itemId, itemType);
                        });
                    } else if (window.__academicCalendarSelection && window.__academicCalendarSelection.id) {
                        makeItemSelected(window.__academicCalendarSelection.id, window.__academicCalendarSelection.type);
                    }
                }, 50);
            };
            ['livewire:updated','livewire:load','livewire:message.processed','livewire:navigated'].forEach(evt => {
                document.addEventListener(evt, reapply);
            });
        });
    </script>

    {{-- Modal is handled by the Filament Action --}}

    <style>
        /* Calendar styling */
        .fc-event {
            border-radius: 6px;
            border-width: 1px;
            font-size: 12px;
            padding: 2px 6px;
        }
        
        .fc-event:hover {
            opacity: 0.8;
            cursor: pointer;
        }

        .fc-daygrid-event {
            margin-bottom: 2px;
        }

        .fc-event-title {
            font-weight: 500;
        }

        /* Arabic RTL support */
        .fc-direction-rtl {
            direction: rtl;
        }

        /* Custom button styling */
        .fc-customButton-button {
            background-color: #6366f1;
            border-color: #6366f1;
            color: white;
        }

        .fc-customButton-button:hover {
            background-color: #4f46e5;
            border-color: #4f46e5;
        }
    </style>
</x-filament-panels::page>
