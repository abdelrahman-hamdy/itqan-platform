<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Calendar Filter Buttons -->
        <div class="flex justify-between items-center">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">التقويم الأكاديمي</h2>
            <div class="flex gap-2">
                <x-filament::button 
                    color="info" 
                    size="sm"
                    onclick="filterCalendar('individual')"
                >
                    <x-heroicon-o-user class="w-4 h-4 mr-2" />
                    الدروس الفردية
                </x-filament::button>
                <x-filament::button 
                    color="success" 
                    size="sm"
                    onclick="filterCalendar('interactive_course')"
                >
                    <x-heroicon-o-user-group class="w-4 h-4 mr-2" />
                    الدورات التفاعلية
                </x-filament::button>
                <x-filament::button 
                    color="gray" 
                    size="sm"
                    onclick="filterCalendar('all')"
                >
                    <x-heroicon-o-eye class="w-4 h-4 mr-2" />
                    عرض الكل
                </x-filament::button>
            </div>
        </div>

        <!-- Legend -->
        <div class="flex gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-blue-600 rounded"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300">الدروس الفردية</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 bg-green-600 rounded"></div>
                <span class="text-sm text-gray-700 dark:text-gray-300">الدورات التفاعلية</span>
            </div>
        </div>

        <!-- Calendar Widget -->
        @livewire(\App\Filament\AcademicTeacher\Widgets\AcademicFullCalendarWidget::class)
    </div>

    <script>
        let calendarApi = null;
        
        // Wait for calendar to be initialized
        document.addEventListener('DOMContentLoaded', function() {
            // Get the calendar API after a short delay to ensure it's initialized
            setTimeout(() => {
                const calendarEl = document.querySelector('.fc');
                if (calendarEl && window.Calendar) {
                    calendarApi = window.Calendar.getInstance(calendarEl);
                }
            }, 1000);
        });

        // Filter function for calendar events
        function filterCalendar(type) {
            if (!calendarApi) {
                // Try to get calendar API
                const calendarEl = document.querySelector('.fc');
                if (calendarEl && window.Calendar) {
                    calendarApi = window.Calendar.getInstance(calendarEl);
                }
            }

            if (calendarApi) {
                const events = calendarApi.getEvents();
                
                events.forEach(event => {
                    if (type === 'all') {
                        event.setProp('display', 'auto');
                    } else {
                        const eventType = event.extendedProps.type;
                        if (eventType === type) {
                            event.setProp('display', 'auto');
                        } else {
                            event.setProp('display', 'none');
                        }
                    }
                });
            }
        }

        // Handle event clicks
        function handleEventClick(info) {
            const eventProps = info.event.extendedProps;
            const url = eventProps.url;
            
            if (url) {
                window.open(url, '_blank');
            }
        }
    </script>

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
