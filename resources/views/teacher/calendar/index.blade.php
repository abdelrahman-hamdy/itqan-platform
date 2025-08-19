<x-layouts.teacher 
    :title="(auth()->user()->academy->name ?? 'أكاديمية إتقان') . ' - تقويم المعلم'"
    :description="'تقويم المعلم - ' . (auth()->user()->academy->name ?? 'أكاديمية إتقان')">

<x-slot:head>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <link href='https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.css' rel='stylesheet' />
    
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Cairo', 'Tajawal', sans-serif;
        }
    </style>
</x-slot:head>

<style>
                /* Calendar Styles */
        .fc {
            font-family: 'Cairo', sans-serif !important;
            direction: ltr !important;
        }
        
        .fc-header-toolbar {
            padding: 1rem !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
        }
        
        .fc-toolbar-chunk {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
        }
        
        .fc-button-group {
            border-radius: 8px !important;
            overflow: hidden !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        .fc-button-primary {
            background-color: {{ auth()->user()->academy->primary_color ?? '#4169E1' }} !important;
            border-color: {{ auth()->user()->academy->primary_color ?? '#4169E1' }} !important;
            color: white !important;
            font-weight: 500 !important;
            padding: 8px 16px !important;
            border-radius: 0 !important;
            margin: 0 !important;
            border-width: 1px !important;
        }
        
        .fc-button-primary:hover {
            background-color: {{ auth()->user()->academy->secondary_color ?? '#6495ED' }} !important;
            border-color: {{ auth()->user()->academy->secondary_color ?? '#6495ED' }} !important;
        }
        
        .fc-button-primary:focus {
            box-shadow: none !important;
        }
        
        .fc-button-primary.fc-button-active {
            background-color: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        .fc-today-button {
            margin: 0 8px !important;
            border-radius: 8px !important;
            background-color: #10b981 !important;
            border-color: #10b981 !important;
        }
        
        .fc-today-button:hover {
            background-color: #059669 !important;
            border-color: #059669 !important;
        }
        
        .fc-prev-button,
        .fc-next-button {
            margin: 0 !important;
            padding: 8px 12px !important;
        }
        
        .fc-prev-button {
            border-top-right-radius: 0 !important;
            border-bottom-right-radius: 0 !important;
        }
        
        .fc-next-button {
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }
        
        .fc-dayGridMonth-button.fc-button-active,
        .fc-timeGridWeek-button.fc-button-active,
        .fc-timeGridDay-button.fc-button-active {
            background-color: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
            transform: translateY(-1px) !important;
    }
    
    .fc-event {
            border-radius: 6px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
    }
    
    .fc-event-individual {
            background-color: #3b82f6 !important;
            border-color: #2563eb !important;
    }
    
    .fc-event-group {
            background-color: #10b981 !important;
            border-color: #059669 !important;
    }
    
    .fc-event-template {
            background-color: #f59e0b !important;
            border-color: #d97706 !important;
        }
        
        .fc-event-past {
            opacity: 0.6 !important;
        }
        
        .fc-daygrid-event {
            padding: 2px 4px !important;
        }
        
        .fc-timegrid-event {
            padding: 4px 6px !important;
        }
        
        /* Session Pills */
        .session-pill {
            background: #e5e7eb;
        color: #374151;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            cursor: grab;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            user-select: none;
        display: inline-block;
        }
        
        .session-pill:hover {
            background: #d1d5db;
            transform: scale(1.05);
        }
        
        .session-pill.dragging {
            opacity: 0.5;
            cursor: grabbing;
        }
        
        .session-pill.disabled {
            opacity: 0.4;
            background-color: #d1d5db !important;
            color: #6b7280 !important;
            cursor: not-allowed !important;
            pointer-events: none;
        }
        
        .session-pill.disabled:hover {
            background-color: #d1d5db !important;
        }
        
        /* Calendar drag hover effects */
        .fc-day.drag-hover,
        .fc-daygrid-day.drag-hover {
            background-color: rgba(59, 130, 246, 0.15) !important;
            border: 2px dashed rgba(59, 130, 246, 0.5) !important;
            border-radius: 4px !important;
        }
        
        /* Specific hover effects for time-grid slots (individual time slots) */
        .fc-timegrid-slot.drag-hover {
            background-color: rgba(59, 130, 246, 0.2) !important;
            border-left: 3px solid #3b82f6 !important;
            border-right: 3px solid #3b82f6 !important;
            border-top: 1px solid rgba(59, 130, 246, 0.3) !important;
            border-bottom: 1px solid rgba(59, 130, 246, 0.3) !important;
            transition: all 0.2s ease;
        }
        
        /* Hover effects for time-grid columns (day columns) */
        .fc-timegrid-col.fc-day.drag-hover {
            background-color: rgba(59, 130, 246, 0.08) !important;
            border-left: 2px solid rgba(59, 130, 246, 0.4) !important;
            border-right: 2px solid rgba(59, 130, 246, 0.4) !important;
        }
        
        /* Enhanced time slot styles */
        .enhanced-time-slot {
            cursor: crosshair !important;
            transition: all 0.2s ease;
            min-height: 24px;
        }
        
        .enhanced-time-slot:hover {
            background-color: rgba(59, 130, 246, 0.05) !important;
        }
        
        .enhanced-time-slot.drag-hover {
            background-color: rgba(59, 130, 246, 0.2) !important;
            border: 2px solid #3b82f6 !important;
            box-shadow: inset 0 0 10px rgba(59, 130, 246, 0.3);
            z-index: 10;
        }
        
        .fc-day:hover {
            background-color: rgba(59, 130, 246, 0.08) !important;
        }
        
        .fc-timegrid-slot:hover {
            background-color: rgba(59, 130, 246, 0.08) !important;
        }
        
        .fc-daygrid-day:hover {
            background-color: rgba(59, 130, 246, 0.08) !important;
        }
        
        /* Enhanced drag hover for better visibility */
        .fc-highlight {
            background-color: rgba(59, 130, 246, 0.2) !important;
        }
        
        /* Drag active state */
        .calendar-dragging .fc-day,
        .calendar-dragging .fc-timegrid-slot,
        .calendar-dragging .fc-daygrid-day {
            transition: background-color 0.2s ease, border 0.2s ease;
        }
        
        /* Custom Modal */
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5) !important;
        }
        
        /* Calendar Drop Zone */
        .fc-day:hover {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
        
        .fc-timegrid-slot:hover {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
        
        /* Changes Indicator */
        .changes-indicator {
        position: fixed;
            top: 100px;
            left: 20px;
            background: #fbbf24;
            color: #92400e;
            padding: 12px 16px;
        border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        z-index: 1000;
        }
        
        .changes-indicator.show {
            transform: translateX(0);
        }
    </style>
            
            @php
                $individualCount = is_countable($individualCircles ?? []) ? count($individualCircles ?? []) : 0;
                $groupCount = is_countable($groupCircles ?? []) ? count($groupCircles ?? []) : 0;
            @endphp
        
        <!-- Page Header -->
            <div class="mb-8 bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                            📅 تقويم المعلم
                        </h1>
                        <p class="text-gray-600 mt-2">إدارة وجدولة الجلسات للحلقات الفردية والجماعية بسهولة</p>
        </div>
                    <div class="flex items-center gap-4">
                        <!-- Save Changes Button -->
                        <button id="saveChangesBtn" 
                                class="hidden bg-emerald-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-all shadow-md"
                                onclick="saveAllChanges()">
                            💾 حفظ التغييرات
                        </button>
                        
                        <!-- Refresh Button -->
                        <button onclick="refreshCalendar()" 
                                class="bg-blue-600 text-white px-4 py-3 rounded-lg hover:bg-blue-700 transition-all shadow-md">
                            🔄 تحديث
                        </button>
                    </div>
                </div>
            </div>

            <!-- Quick Stats Row -->
            <div class="mb-6 bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 p-4 rounded-lg border border-blue-200">
                        <div class="text-2xl font-bold text-blue-600" id="totalIndividualCircles">{{ $individualCount }}</div>
                        <div class="text-sm text-blue-700 font-medium">حلقات فردية</div>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-green-100 p-4 rounded-lg border border-green-200">
                        <div class="text-2xl font-bold text-green-600" id="totalGroupCircles">{{ $groupCount }}</div>
                        <div class="text-sm text-green-700 font-medium">حلقات جماعية</div>
                    </div>
                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 p-4 rounded-lg border border-purple-200">
                        <div class="text-2xl font-bold text-purple-600" id="scheduledSessions">0</div>
                        <div class="text-sm text-purple-700 font-medium">جلسات مجدولة</div>
                    </div>
                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 p-4 rounded-lg border border-orange-200">
                        <div class="text-2xl font-bold text-orange-600" id="pendingSessions">0</div>
                        <div class="text-sm text-orange-700 font-medium">جلسات في الانتظار</div>
                    </div>
                </div>
            </div>

            <!-- Circle Selection & Bulk Scheduling Section -->
            <div class="mb-6 bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                        ⭕ اختيار الحلقة والجدولة الجماعية
                    </h3>
                    <!-- SIMPLE TEST BUTTON -->
                    <button onclick="makeCircleSelected(document.querySelector('input[name=selectedCircle]:checked')); alert('تم تطبيق التمييز!');" 
                            class="bg-red-500 text-white px-4 py-2 rounded font-medium hover:bg-red-600 transition-colors"
                            title="اضغط لاختبار تمييز الحلقة المحددة">
                        🎯 اختبر التمييز
                    </button>
                    <button id="viewSessionsBtn" 
                            class="bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-all shadow-md disabled:bg-gray-400 disabled:cursor-not-allowed"
                            onclick="viewSelectedCircleSessions()" disabled>
                        👁️ عرض جلسات الحلقة المحددة
                    </button>
                    <!-- Bulk scheduling removed - now handled in Filament dashboard -->
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Individual Circles -->
                    <div class="space-y-3">
                        <h4 class="text-lg font-medium text-gray-800 flex items-center gap-2">
                            👤 الحلقات الفردية ({{ $individualCount }})
                        </h4>
                        
                        @if($individualCount > 0)
                            @foreach($individualCircles as $circle)
                            <label class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer block circle-selection">
                                <input type="radio" name="selectedCircle" value="{{ $circle->id }}" data-type="individual" 
                                       data-sessions="{{ $circle->subscription->package->sessions_per_month ?? 0 }}"
                                       data-duration="{{ $circle->subscription->package->session_duration_minutes ?? 60 }}"
                                       data-name="{{ $circle->name ?? 'حلقة فردية' }}"
                                       class="mr-3" 
                                       onchange="makeCircleSelected(this);"
                                       onclick="setTimeout(function(){ makeCircleSelected(document.querySelector('input[name=selectedCircle]:checked')); }, 10);">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-gray-900">{{ $circle->name ?? 'حلقة فردية' }}</h5>
                                        <p class="text-sm text-gray-600">الطالب: {{ $circle->student->name ?? 'غير محدد' }}</p>
                                        @if($circle->subscription && $circle->subscription->package)
                                            <p class="text-xs text-blue-600 mt-1">
                                                📊 {{ $circle->subscription->package->sessions_per_month }} جلسة/شهر - 
                                                ⏱️ {{ $circle->subscription->package->session_duration_minutes }} دقيقة
                                            </p>
                                        @endif
                                    </div>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-1 rounded">فردية</span>
                                </div>

                                @if(isset($circle->templateSessions) && $circle->templateSessions->count() > 0)
                                    <div class="mt-3 text-sm text-orange-600">
                                        {{ $circle->templateSessions->count() }} جلسة في انتظار الجدولة
                                    </div>
                                @endif
                            </label>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-user-line text-3xl"></i>
                                <p class="text-sm">لا توجد حلقات فردية</p>
                            </div>
                        @endif
                    </div>

                    <!-- Group Circles -->
                    <div class="space-y-3">
                        <h4 class="text-lg font-medium text-gray-800 flex items-center gap-2">
                            👥 الحلقات الجماعية ({{ $groupCount }})
                        </h4>
                        
                        @if($groupCount > 0)
                            @foreach($groupCircles as $circle)
                            <label class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer block circle-selection">
                                <input type="radio" name="selectedCircle" value="{{ $circle->id }}" data-type="group"
                                       data-sessions="{{ $circle->monthly_sessions_count ?? 8 }}"
                                       data-duration="{{ $circle->session_duration_minutes ?? 60 }}"
                                       data-name="{{ $circle->name_ar ?? 'حلقة جماعية' }}"
                                       class="mr-3" 
                                       onchange="makeCircleSelected(this);"
                                       onclick="setTimeout(function(){ makeCircleSelected(document.querySelector('input[name=selectedCircle]:checked')); }, 10);">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <h5 class="font-semibold text-gray-900">{{ $circle->name_ar ?? 'حلقة جماعية' }}</h5>
                                        <p class="text-sm text-gray-600">{{ $circle->enrolled_students ?? 0 }} طالب - كود: {{ $circle->circle_code ?? 'غير محدد' }}</p>
                                        <p class="text-xs text-green-600 mt-1">
                                            📊 {{ $circle->sessions_count ?? 0 }} جلسة مجدولة ({{ $circle->monthly_sessions_count ?? 8 }} شهرياً) - 
                                            ⏱️ {{ $circle->session_duration_minutes ?? 60 }} دقيقة
                                        </p>
                                    </div>
                                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded">جماعية</span>
                                </div>
                            </label>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="ri-group-line text-3xl"></i>
                                <p class="text-sm">لا توجد حلقات جماعية</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="mb-6 bg-white rounded-xl shadow-sm p-6 border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    🔍 تصفية وعرض الجلسات
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700">حالة الجلسة</label>
                        <select id="statusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="applyFilters()">
                            <option value="all">جميع الجلسات</option>
                            <option value="upcoming">جلسات قادمة</option>
                            <option value="past">جلسات سابقة</option>
                            <option value="scheduled">مجدولة</option>
                            <option value="pending">في الانتظار</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700">نوع الجلسة</label>
                        <select id="typeFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="applyFilters()">
                            <option value="all">كل الأنواع</option>
                            <option value="individual">فردية</option>
                            <option value="group">جماعية</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700">الحلقة المحددة</label>
                        <select id="circleFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="applyFilters()">
                            <option value="all">جميع الحلقات</option>
                            <!-- Options will be populated by JavaScript -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- Calendar Section -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">

                <!-- Calendar Display -->
                <div class="p-6">
                    <div id="calendar" style="min-height: 600px;"></div>
                </div>
            </div>

            <!-- No Circles Message (when both are empty) -->
            @if($individualCount === 0 && $groupCount === 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 mt-6">
                    <div class="text-center py-12 text-gray-500">
                        <div class="mb-4">
                            <i class="ri-group-line text-6xl"></i>
                </div>
                        <h4 class="text-lg font-medium text-gray-700 mb-2">لا توجد حلقات متاحة حالياً</h4>
                        <p class="text-sm">يرجى التواصل مع الإدارة لإنشاء حلقات جديدة</p>
        </div>
        </div>
            @endif
    </div>
    </main>

    <!-- Changes Indicator -->
    <div id="changesIndicator" class="changes-indicator">
        <div class="flex items-center gap-2">
            <span>⚠️</span>
            <span>لديك تغييرات غير محفوظة</span>
</div>
    </div>


<script>
let calendar;
let currentFilter = 'all';
        let currentStatusFilter = 'all';
        let currentTypeFilter = 'all';
        let pendingChanges = [];
        let selectedCircle = null;
        let draggedSessionData = null;
        let currentlyDraggedPill = null;
        let scheduledSessions = new Set(); // Track already scheduled session IDs
        let existingModals = []; // Track open modals

        // Debug logging function
        function debugLog(message, data = null) {
            console.log(`[Calendar Debug] ${message}`, data);
        }

        // Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
            debugLog('Starting calendar initialization');
    initializeCalendar();
    initializeEventListeners();
            loadStats();
            checkGroupCircleStatuses();
            <!-- updateBulkScheduleButton call removed - no longer needed -->
            debugLog('Calendar initialization complete');
});

function initializeCalendar() {
    const calendarEl = document.getElementById('calendar');
    
    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'ar',
        direction: 'rtl',
        firstDay: 6, // Start week with Saturday (6 = Saturday, 0 = Sunday)
                height: 600,
        headerToolbar: {
                    left: 'next,prev today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        buttonText: {
            today: 'اليوم',
            month: 'شهر',
            week: 'أسبوع',
                    day: 'يوم',
                    next: '❮',
                    prev: '❯'
        },
                editable: true,
                droppable: true,
        events: function(fetchInfo, successCallback, failureCallback) {
            loadCalendarEvents(fetchInfo, successCallback, failureCallback);
        },
        eventClick: function(info) {
            handleEventClick(info);
        },
                eventDrop: function(info) {
                    handleEventDrop(info);
                },
                eventResize: function(info) {
                    handleEventResize(info);
                },
                drop: function(info) {
                    debugLog('Drop event triggered', info);
                    handleExternalDrop(info);
                },
                eventReceive: function(info) {
                    debugLog('Event received', info);
        },
        eventDidMount: function(info) {
                    const event = info.event;
                    const now = new Date();
                    const eventDate = new Date(event.start);
                    
                    if (eventDate < now) {
                        info.el.classList.add('fc-event-past');
                    }
                    
                    if (event.extendedProps.session_type === 'individual') {
                info.el.classList.add('fc-event-individual');
                    } else if (event.extendedProps.session_type === 'group') {
                info.el.classList.add('fc-event-group');
                    } else if (event.extendedProps.is_template) {
                info.el.classList.add('fc-event-template');
            }
        },
        
        // Customize day cells in month view
        dayCellDidMount: function(info) {
            // Add custom attributes for better drag targeting
            info.el.setAttribute('data-date', info.dateStr);
            info.el.setAttribute('data-day', info.date.getDay());
            info.el.classList.add('calendar-day-cell');
            
            debugLog('Day cell mounted', { 
                date: info.dateStr, 
                dayOfWeek: info.date.getDay(),
                element: info.el 
            });
        },
        
        // Customize time slots in week/day view  
        slotLabelDidMount: function(info) {
            // Add time information to slot labels
            if (info.text && info.text.trim()) {
                info.el.setAttribute('data-time', info.text);
                info.el.classList.add('calendar-time-label');
            }
        },
        
        // Add custom rendering for time grid slots
        viewDidMount: function(info) {
            debugLog('View mounted', { view: info.view.type });
            
            // After view renders, enhance time grid cells
            if (info.view.type === 'timeGridWeek' || info.view.type === 'timeGridDay') {
                setTimeout(() => {
                    enhanceTimeGridCells();
                }, 200); // Increased timeout to ensure DOM is ready
            }
        },
        
        // Also enhance on view change
        datesSet: function(info) {
            debugLog('Dates set (view changed)', { view: calendar.view.type });
            
            // Re-enhance time grid cells when dates change (navigation, view change)
            if (calendar.view.type === 'timeGridWeek' || calendar.view.type === 'timeGridDay') {
                setTimeout(() => {
                    enhanceTimeGridCells();
                }, 300);
            }
        }
    });
    
    calendar.render();
            debugLog('Calendar rendered');
            
            // Initialize external draggable elements with new approach
            setTimeout(() => {
                initializeDraggableElements();
            }, 500);
}

// Enhance time grid cells for better drag & drop targeting
function enhanceTimeGridCells() {
    debugLog('Enhancing time grid cells - starting');
    
    // Clear any existing enhanced slots first to avoid duplicates
    const existingEnhanced = document.querySelectorAll('.enhanced-time-slot');
    existingEnhanced.forEach(slot => {
        slot.classList.remove('enhanced-time-slot');
        slot.removeAttribute('data-enhanced');
        // Remove event listeners by cloning and replacing node
        const newSlot = slot.cloneNode(true);
        slot.parentNode.replaceChild(newSlot, slot);
    });
    
    // Find all time grid columns (day columns)
    const dayColumns = document.querySelectorAll('.fc-timegrid-col');
    debugLog('Found day columns:', dayColumns.length);
    
    let enhancedCount = 0;
    
    dayColumns.forEach((column, dayIndex) => {
        // Skip the time axis column
        if (column.classList.contains('fc-timegrid-axis')) {
            debugLog(`Skipping axis column ${dayIndex}`);
            return;
        }
        
        // Get the date for this column from various possible sources
        let columnDate = null;
        
        // Try header first
        const dayHeader = document.querySelector(`.fc-col-header-cell[data-date]`);
        if (dayHeader) {
            columnDate = dayHeader.getAttribute('data-date');
        }
        
        // Try from the column itself
        if (!columnDate && column.getAttribute('data-date')) {
            columnDate = column.getAttribute('data-date');
        }
        
        // Try to find date from the header area
        if (!columnDate) {
            const headerCells = document.querySelectorAll('.fc-col-header-cell');
            if (headerCells[dayIndex - 1]) { // -1 because first column is usually axis
                columnDate = headerCells[dayIndex - 1].getAttribute('data-date');
            }
        }
        
        debugLog(`Column ${dayIndex}: date = ${columnDate}`);
        
        // Find all time slots in this column
        const timeSlots = column.querySelectorAll('.fc-timegrid-slot');
        debugLog(`Column ${dayIndex}: found ${timeSlots.length} time slots`);
        
        timeSlots.forEach((slot, timeIndex) => {
            // Get the time for this slot from the time axis
            let slotTime = null;
            
            // Try to find corresponding time label
            const allTimeLabels = document.querySelectorAll('.fc-timegrid-slot-label');
            if (allTimeLabels[timeIndex]) {
                const labelText = allTimeLabels[timeIndex].textContent.trim();
                if (labelText && labelText !== '') {
                    slotTime = labelText;
                }
            }
            
            // Calculate time based on position if not found
            if (!slotTime) {
                const hour = Math.floor(timeIndex / 2); // Assuming 30-minute slots
                const minute = (timeIndex % 2) * 30;
                slotTime = `${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
            }
            
            // Add enhanced attributes and classes
            slot.classList.add('enhanced-time-slot');
            slot.setAttribute('data-enhanced', 'true');
            
            if (columnDate) {
                slot.setAttribute('data-date', columnDate);
                slot.setAttribute('data-datetime', `${columnDate}T${slotTime}`);
            }
            
            if (slotTime) {
                slot.setAttribute('data-time', slotTime);
            }
            
            slot.setAttribute('data-day-index', dayIndex);
            slot.setAttribute('data-time-index', timeIndex);
            
            // Create a virtual table-like structure using CSS
            slot.style.position = 'relative';
            slot.style.borderRight = '1px solid rgba(0,0,0,0.1)';
            slot.style.minHeight = '30px'; // Ensure minimum clickable area
            
            // Add hover and click handlers specifically for this enhanced slot
            slot.addEventListener('dragover', handleSlotDragOver, { passive: false });
            slot.addEventListener('drop', handleSlotDrop, { passive: false });
            slot.addEventListener('dragenter', handleSlotDragEnter, { passive: false });
            slot.addEventListener('dragleave', handleSlotDragLeave, { passive: false });
            
            enhancedCount++;
            
            debugLog('Enhanced time slot', { 
                dayIndex, 
                timeIndex, 
                columnDate, 
                slotTime,
                hasDate: !!columnDate,
                hasTime: !!slotTime,
                slot: slot.tagName
            });
        });
    });
    
    debugLog(`Time grid cell enhancement complete - enhanced ${enhancedCount} slots`);
}

// Enhanced slot event handlers
function handleSlotDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    e.dataTransfer.dropEffect = 'copy';
    
    // Remove all existing hover effects
    removeAllDragHoverEffects();
    
    // Add hover effect to this specific slot
    const slot = e.currentTarget;
    if (slot && slot.classList.contains('enhanced-time-slot')) {
        addDragHoverEffect(slot);
        debugLog('Slot drag over', { 
            date: slot.getAttribute('data-date'),
            time: slot.getAttribute('data-time'),
            datetime: slot.getAttribute('data-datetime')
        });
    }
}

function handleSlotDragEnter(e) {
    e.preventDefault();
    e.stopPropagation();
}

function handleSlotDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    
    // Only remove hover if we're actually leaving this slot
    if (!e.currentTarget.contains(e.relatedTarget)) {
        const slot = e.currentTarget;
        if (slot) {
            removeDragHoverEffect(slot);
        }
    }
}

function handleSlotDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const slot = e.currentTarget;
    if (!slot || !slot.classList.contains('enhanced-time-slot')) {
        return;
    }
    
    // Get the precise datetime for this slot
    const datetime = slot.getAttribute('data-datetime');
    const date = slot.getAttribute('data-date');
    const time = slot.getAttribute('data-time');
    
    debugLog('Enhanced slot drop', { datetime, date, time, slot });
    
    if (datetime) {
        // Create a proper drop event similar to FullCalendar's structure
        const dropDate = new Date(datetime);
        
        // Process the drop using existing logic
        handleNativeDrop({
            target: slot,
            dataTransfer: e.dataTransfer,
            clientX: e.clientX,
            clientY: e.clientY,
            customDropDate: dropDate // Pass the precise date
        });
    }
    
    // Clean up hover effects
    removeAllDragHoverEffects();
}

function initializeEventListeners() {
            document.getElementById('statusFilter').addEventListener('change', applyFilters);
            document.getElementById('typeFilter').addEventListener('change', applyFilters);
            document.getElementById('circleFilter').addEventListener('change', applyFilters);
}

function loadCalendarEvents(fetchInfo, successCallback, failureCallback) {
    const params = new URLSearchParams({
        start: fetchInfo.startStr,
        end: fetchInfo.endStr,
                filter: currentFilter,
                status: currentStatusFilter,
                type: currentTypeFilter
            });
            
            debugLog('Loading calendar events', { 
                params: Object.fromEntries(params),
                view: calendar ? calendar.view.type : 'unknown'
            });
    
    fetch(`{{ route('teacher.calendar.events', ['subdomain' => request()->route('subdomain')]) }}?${params}`)
        .then(response => response.json())
        .then(data => {
                    debugLog('Received calendar events response', { 
                        success: data.success, 
                        eventCount: data.events ? data.events.length : 0,
                        events: data.events 
                    });
                    
            if (data.success) {
                        // Log group vs individual event counts
                        const groupEvents = data.events.filter(e => e.extendedProps && e.extendedProps.session_type === 'group');
                        const individualEvents = data.events.filter(e => e.extendedProps && e.extendedProps.session_type === 'individual');
                        
                        debugLog('Event breakdown', {
                            total: data.events.length,
                            group: groupEvents.length,
                            individual: individualEvents.length,
                            groupEvents: groupEvents,
                            individualEvents: individualEvents
                        });
                        
                successCallback(data.events);
                updateStats();
                        afterCalendarLoad(); // Initialize draggable elements after events are loaded
            } else {
                failureCallback(data.message);
            }
        })
        .catch(error => {
            console.error('Error loading events:', error);
                    debugLog('Error loading events', error);
                    failureCallback(error.message);
                });
        }

        function applyFilters() {
            currentStatusFilter = document.getElementById('statusFilter').value;
            currentTypeFilter = document.getElementById('typeFilter').value;
            calendar.refetchEvents();
        }

        function updateStats() {
            // Update statistics based on current events
            const events = calendar.getEvents();
            const scheduledCount = events.filter(e => e.extendedProps.status === 'scheduled').length;
            const pendingCount = events.filter(e => e.extendedProps.is_template).length;
            
            document.getElementById('scheduledSessions').textContent = scheduledCount;
            document.getElementById('pendingSessions').textContent = pendingCount;
        }

        function loadStats() {
            updateStats();
        }

        function initializeDraggableElements() {
            debugLog('Initializing draggable elements');
            const sessionPills = document.querySelectorAll('.session-pill');
            debugLog(`Found ${sessionPills.length} session pills`);
            
            sessionPills.forEach((pill, index) => {
                // Remove any existing event listeners
                pill.removeAttribute('draggable');
                
                // Make element draggable
                pill.setAttribute('draggable', 'true');
                
                // Add event listeners
                pill.addEventListener('dragstart', function(e) {
                    debugLog(`Drag start for pill ${index}`, e.target.dataset);
                    handleDragStart(e);
                });
                
                pill.addEventListener('dragend', function(e) {
                    e.target.classList.remove('dragging');
                    
                    // Remove calendar-dragging class and all hover effects
                    const calendarEl = document.getElementById('calendar');
                    if (calendarEl) {
                        calendarEl.classList.remove('calendar-dragging');
                    }
                    removeAllDragHoverEffects();
                    
                    debugLog('Drag end with cleanup');
                });
                
                debugLog(`Initialized pill ${index}:`, pill.dataset);
            });
            
            // Make calendar droppable using native HTML5 drag & drop
            const calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                calendarEl.addEventListener('dragover', function(e) {
                    // Skip if this is being handled by enhanced slot
                    if (e.target && e.target.classList.contains('enhanced-time-slot')) {
                        return;
                    }
                    
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'copy';
                    
                    // Remove previous hover effects first
                    removeAllDragHoverEffects();
                    
                    // Add hover effect to the specific target cell
                    const target = e.target;
                    const calendarCell = findCalendarCell(target);
                    if (calendarCell && !calendarCell.classList.contains('enhanced-time-slot')) {
                        addDragHoverEffect(calendarCell);
                        debugLog('Hover effect applied to specific cell', { 
                            cellClass: calendarCell.className,
                            cellTag: calendarCell.tagName 
                        });
                    }
                });
                
                calendarEl.addEventListener('dragleave', function(e) {
                    // Remove hover effects when leaving calendar area
                    const target = e.target;
                    const calendarCell = findCalendarCell(target);
                    if (calendarCell) {
                        removeDragHoverEffect(calendarCell);
                    }
                });
                
                calendarEl.addEventListener('drop', function(e) {
                    // Skip if this is being handled by enhanced slot
                    if (e.target && e.target.classList.contains('enhanced-time-slot')) {
                        return;
                    }
                    
                    e.preventDefault();
                    debugLog('Native drop event on calendar', e);
                    
                    // Remove all hover effects
                    removeAllDragHoverEffects();
                    
                    handleNativeDrop(e);
                });
            }
        }

        // Drag hover effect helper functions
        function findCalendarCell(element) {
            // Look for calendar cell elements in the DOM hierarchy
            let current = element;
            while (current && current !== document.body) {
                // For day grid (month view), use the day cell
                if (current.classList.contains('fc-daygrid-day')) {
                    return current;
                }
                
                // For time grid (week/day views), be more specific
                if (current.classList.contains('fc-timegrid-slot')) {
                    // Check if this is a specific day column slot, not an all-day slot
                    const dayCol = current.closest('.fc-timegrid-col');
                    if (dayCol && !current.classList.contains('fc-timegrid-slot-label')) {
                        return current;
                    }
                }
                
                // Look for more specific time grid elements
                if (current.classList.contains('fc-timegrid-col') && 
                    current.classList.contains('fc-day')) {
                    // This is a specific day column in time grid
                    return current;
                }
                
                // Fallback for other day elements
                if (current.classList.contains('fc-day') && 
                    !current.classList.contains('fc-timegrid-axis')) {
                    return current;
                }
                
                current = current.parentElement;
            }
            return null;
        }
        
        function addDragHoverEffect(element) {
            if (element) {
                element.classList.add('drag-hover');
                debugLog('Added drag hover effect to', element);
            }
        }
        
        function removeDragHoverEffect(element) {
            if (element) {
                element.classList.remove('drag-hover');
                debugLog('Removed drag hover effect from', element);
            }
        }
        
        function removeAllDragHoverEffects() {
            const hoveredElements = document.querySelectorAll('.drag-hover');
            hoveredElements.forEach(el => {
                el.classList.remove('drag-hover');
            });
            debugLog('Removed all drag hover effects');
        }

        function handleDragStart(event) {
            const target = event.target;
            const sessionId = target.dataset.sessionId;
            
            // Check if this session is already scheduled (should be disabled visually)
            if (scheduledSessions.has(sessionId) || target.classList.contains('disabled')) {
                debugLog('Session already scheduled, preventing drag', sessionId);
                event.preventDefault();
                return false;
            }
            
            // Add calendar-dragging class to enable drag styles
            const calendarEl = document.getElementById('calendar');
            if (calendarEl) {
                calendarEl.classList.add('calendar-dragging');
            }
            
            const sessionData = {
                sessionId: sessionId,
                circleId: target.dataset.circleId,
                duration: target.dataset.duration || '60',
                title: target.dataset.title || target.textContent.trim(),
                description: target.dataset.description || ''
            };
            
            debugLog('Setting drag data', sessionData);
            
            // Store references
            draggedSessionData = sessionData;
            currentlyDraggedPill = target;
            
            // Set data transfer
            event.dataTransfer.setData('text/plain', JSON.stringify(sessionData));
            event.dataTransfer.setData('application/json', JSON.stringify(sessionData));
            event.dataTransfer.effectAllowed = 'copy';
            
            // Visual feedback
            target.classList.add('dragging');
        }

        function handleNativeDrop(event) {
            debugLog('Native drop handler called', { hasCustomDate: !!event.customDropDate });
            
            // Try to get session data from dataTransfer or global variable
            let sessionData = null;
            
            try {
                const jsonData = event.dataTransfer.getData('application/json') || event.dataTransfer.getData('text/plain');
                if (jsonData) {
                    sessionData = JSON.parse(jsonData);
                }
            } catch (e) {
                debugLog('Error parsing drag data from dataTransfer', e);
            }
            
            if (!sessionData && draggedSessionData) {
                sessionData = draggedSessionData;
                debugLog('Using global session data', sessionData);
            }
            
            if (!sessionData) {
                debugLog('No session data available');
                showNotification('خطأ في بيانات الجلسة', 'error');
                return;
            }
            
            // Use custom drop date if provided (from enhanced slots), otherwise calculate it
            let dropDate;
            if (event.customDropDate) {
                dropDate = event.customDropDate;
                debugLog('Using custom drop date from enhanced slot', dropDate);
            } else {
                dropDate = getDropTargetDate(event);
                if (!dropDate) {
                    debugLog('Could not determine drop target date');
                    showNotification('خطأ في تحديد التاريخ', 'error');
                    return;
                }
            }
            
            debugLog('Drop target date determined', dropDate);
            
            // Get calendar view type for handling
            const calendarApi = calendar;
            const currentView = calendarApi.view.type;
            
            if (currentView === 'dayGridMonth') {
                showTimePickerModal(dropDate, sessionData);
    } else {
                // For week and day views, use the precise time we calculated
                createSessionEvent(dropDate, sessionData);
            }
        }
        
        function getDropTargetDate(event) {
            try {
                const calendarApi = calendar;
                const currentView = calendarApi.view.type;
                
                debugLog('Getting drop target date', { 
                    viewType: currentView, 
                    clientX: event.clientX, 
                    clientY: event.clientY,
                    target: event.target.className
                });
                
                // For time-grid views, try FullCalendar's datePointToDate API FIRST
                if (currentView === 'timeGridWeek' || currentView === 'timeGridDay') {
                    try {
                        const calendarEl = document.getElementById('calendar');
                        const rect = calendarEl.getBoundingClientRect();
                        const x = event.clientX - rect.left;
                        const y = event.clientY - rect.top;
                        
                        // Use FullCalendar's internal method to convert coordinates to date
                        const datePoint = { left: x, top: y };
                        const dateFromPosition = calendarApi.datePointToDate(datePoint);
                        
                        if (dateFromPosition) {
                            debugLog('Got precise date from mouse position', dateFromPosition.toISOString());
                            return dateFromPosition;
                        }
                    } catch (positionError) {
                        debugLog('Position-based date detection failed, trying DOM traversal', positionError);
                    }
                }
                
                // Try to find the closest calendar cell element
                let targetCell = event.target;
                let searchCount = 0;
                const maxSearchDepth = 10;
                
                // Traverse up the DOM to find a calendar cell
                while (targetCell && targetCell !== document.body && searchCount < maxSearchDepth) {
                    debugLog(`Checking element ${searchCount}`, {
                        tagName: targetCell.tagName,
                        className: targetCell.className,
                        dataDate: targetCell.getAttribute('data-date'),
                        hasDataDateAttr: targetCell.hasAttribute('data-date')
                    });
                    
                    // Check for data-date attribute
                    if (targetCell.getAttribute('data-date')) {
                        const dateStr = targetCell.getAttribute('data-date');
                        debugLog('Found data-date attribute', { dateStr, element: targetCell.className });
                        
                        const parsedDate = new Date(dateStr);
                        if (!isNaN(parsedDate.getTime())) {
                            // For month view, set to 9 AM on the target date
                            if (currentView === 'dayGridMonth') {
                                parsedDate.setHours(9, 0, 0, 0);
                            }
                            debugLog('Successfully parsed date from data-date', parsedDate.toISOString());
                            return parsedDate;
                        }
                    }
                    
                    // Check for FullCalendar day grid cells
                    if (targetCell.classList.contains('fc-daygrid-day')) {
                        const dateStr = targetCell.getAttribute('data-date');
                        if (dateStr) {
                            const parsedDate = new Date(dateStr);
                            if (!isNaN(parsedDate.getTime())) {
                                parsedDate.setHours(9, 0, 0, 0); // 9 AM for month view
                                debugLog('Found date from fc-daygrid-day', parsedDate.toISOString());
                                return parsedDate;
                            }
                        }
                    }
                    
                    targetCell = targetCell.parentElement;
                    searchCount++;
                }
                
                // For time-grid, try enhanced slot data
                if (currentView === 'timeGridWeek' || currentView === 'timeGridDay') {
                    const enhancedSlot = event.target.closest('.enhanced-time-slot');
                    if (enhancedSlot) {
                        const dateTime = enhancedSlot.getAttribute('data-datetime');
                        if (dateTime) {
                            const parsedDate = new Date(dateTime);
                            if (!isNaN(parsedDate.getTime())) {
                                debugLog('Found date from enhanced slot', parsedDate.toISOString());
                                return parsedDate;
                            }
                        }
                    }
                    
                    // Try to find time info from axis
                    const timeSlot = event.target.closest('[data-time]');
                    if (timeSlot) {
                        const timeStr = timeSlot.getAttribute('data-time');
                        const dayColumn = event.target.closest('[data-date]');
                        if (dayColumn && timeStr) {
                            const dayStr = dayColumn.getAttribute('data-date');
                            const dateTime = dayStr + 'T' + timeStr;
                            const parsedDate = new Date(dateTime);
                            if (!isNaN(parsedDate.getTime())) {
                                debugLog('Combined date+time from slots', parsedDate.toISOString());
                                return parsedDate;
                            }
                        }
                    }
                }
                
                // CRITICAL: This should almost never happen now
                debugLog('WARNING: Using fallback date - this indicates a problem with date detection');
                const fallbackDate = new Date();
                fallbackDate.setDate(fallbackDate.getDate() + 1); // Tomorrow
                fallbackDate.setHours(9, 0, 0, 0); // 9 AM
                debugLog('Using fallback date (tomorrow 9 AM)', fallbackDate.toISOString());
                
                return fallbackDate;
            } catch (error) {
                debugLog('Error in getDropTargetDate', error);
                const fallback = new Date();
                fallback.setDate(fallback.getDate() + 1); // Tomorrow
                fallback.setHours(9, 0, 0, 0); // 9 AM
                return fallback;
            }
        }

        function handleExternalDrop(info) {
            try {
                const sessionDataStr = info.jsEvent.dataTransfer.getData('text/plain');
                let sessionData = null;
                
                if (sessionDataStr) {
                    sessionData = JSON.parse(sessionDataStr);
                } else if (info.draggedEl && info.draggedEl.dataset.sessionId) {
                    sessionData = {
                        sessionId: info.draggedEl.dataset.sessionId,
                        circleId: info.draggedEl.dataset.circleId,
                        duration: info.draggedEl.dataset.duration,
                        title: info.draggedEl.dataset.title,
                        description: info.draggedEl.dataset.description
                    };
                }
                
                if (!sessionData || !sessionData.sessionId || !sessionData.circleId) {
                    console.error('Missing session or circle data');
                    showNotification('بيانات الجلسة غير مكتملة', 'error');
                    return;
                }
                
                const currentView = calendar.view.type;
                
                // For monthly view, show time picker modal
                if (currentView === 'dayGridMonth') {
                    showTimePickerModal(info.date, sessionData);
    } else {
                    // For week/day view, use the exact dropped time
                    createSessionEvent(info.date, sessionData);
                }
                
                // Remove the dragged element from the list
                if (info.draggedEl) {
                    info.draggedEl.remove();
                    updateCircleSessionCount(sessionData.circleId);
                }
                
            } catch (error) {
                console.error('Error handling drop:', error);
                showNotification('خطأ في إضافة الجلسة', 'error');
            }
        }

        function closeAllExistingModals() {
            // Remove all existing modals
            const existingModals = document.querySelectorAll('.modal-overlay');
            existingModals.forEach(modal => {
                modal.remove();
                debugLog('Removed existing modal');
            });
            
            // Clear modal tracking
            existingModals.length = 0;
        }

        function showTimePickerModal(date, sessionData) {
            debugLog('Showing time picker modal', { date, sessionData });
            
            // Close any existing modals first
            closeAllExistingModals();
            
            const modal = document.createElement('div');
            modal.className = 'modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4';
            
            const sessionDataJson = JSON.stringify(sessionData).replace(/"/g, '&quot;');
            const uniqueId = 'modal-' + Date.now();
            
            modal.id = uniqueId;
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">اختيار وقت الجلسة</h3>
                        <button onclick="closeTimePickerModal('${uniqueId}')" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="mb-4">
                        <p class="text-sm text-gray-600 mb-2">اليوم: ${date.toLocaleDateString('ar-EG')}</p>
                        <p class="text-sm text-gray-600 mb-4">الجلسة: ${sessionData.title}</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">وقت البداية</label>
                            <select id="timePickerHour-${uniqueId}" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                ${generateHourOptions()}
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button onclick="confirmTimeSelection('${date.toISOString()}', '${sessionDataJson}', '${uniqueId}')" 
                                class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            تأكيد الوقت
                        </button>
                        <button onclick="closeTimePickerModal('${uniqueId}')" 
                                class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                            إلغاء
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            existingModals.push(modal);
            debugLog('Time picker modal created with ID:', uniqueId);
        }
        
        function closeTimePickerModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.remove();
                debugLog('Closed time picker modal:', modalId);
            }
            
            // Reset drag state
            currentlyDraggedPill = null;
            draggedSessionData = null;
        }

        function generateHourOptions() {
            let options = '';
            for (let hour = 0; hour < 24; hour++) {
                const display12h = hour === 0 ? '12 AM' : 
                                  hour < 12 ? `${hour} AM` : 
                                  hour === 12 ? '12 PM' : 
                                  `${hour - 12} PM`;
                const displayArabic = hour === 0 ? '12:00 صباحاً' :
                                     hour < 12 ? `${hour}:00 صباحاً` :
                                     hour === 12 ? '12:00 ظهراً' :
                                     `${hour - 12}:00 مساءً`;
                options += `<option value="${hour.toString().padStart(2, '0')}:00">${displayArabic}</option>`;
            }
            return options;
        }

        function confirmTimeSelection(dateStr, sessionDataStr, modalId) {
            debugLog('Confirming time selection', { dateStr, sessionDataStr, modalId });
            
            try {
                // Decode HTML entities
                const decodedJson = sessionDataStr.replace(/&quot;/g, '"');
                const sessionData = JSON.parse(decodedJson);
                const selectedTime = document.getElementById('timePickerHour-' + modalId).value;
                
                debugLog('Parsed session data and selected time', { sessionData, selectedTime });
                
                const sessionDate = new Date(dateStr);
                const [hours, minutes] = selectedTime.split(':');
                sessionDate.setHours(parseInt(hours), parseInt(minutes));
                
                debugLog('Final session date:', sessionDate.toISOString());
                
                createSessionEvent(sessionDate, sessionData);
                
                // Close modal and clean up
                closeTimePickerModal(modalId);
                
                // Mark session as scheduled and remove pill
                markSessionAsScheduled(sessionData.sessionId);
                if (currentlyDraggedPill) {
                    currentlyDraggedPill.remove();
                    updateCircleSessionCount(sessionData.circleId);
                }
                
            } catch (error) {
                console.error('Error confirming time:', error);
                debugLog('Error details:', error);
                showNotification('خطأ في تأكيد الوقت', 'error');
            }
        }

        // Check for time conflicts before creating session
        function checkTimeConflict(startDate, durationMinutes) {
            const sessionStart = new Date(startDate);
            
            // Validate the input session start date
            if (isNaN(sessionStart.getTime())) {
                debugLog('Invalid session start date provided', startDate);
                return { hasConflict: false };
            }
            
            const sessionEnd = new Date(sessionStart.getTime() + (durationMinutes * 60000));
            
            debugLog('Checking time conflict', { 
                sessionStart: sessionStart.toISOString(), 
                sessionEnd: sessionEnd.toISOString(), 
                durationMinutes 
            });
            
            const existingEvents = calendar.getEvents();
            debugLog('Existing events count:', existingEvents.length);
            
            for (let event of existingEvents) {
                // Skip invalid events or events with no start time
                if (!event.start) {
                    debugLog('Skipping event with no start date', { eventId: event.id, eventTitle: event.title });
                    continue;
                }
                
                const eventStart = new Date(event.start);
                
                // Validate the existing event start date
                if (isNaN(eventStart.getTime())) {
                    debugLog('Skipping event with invalid start date', { 
                        eventId: event.id, 
                        eventTitle: event.title,
                        eventStart: event.start 
                    });
                    continue;
                }
                
                // Get duration from various possible locations
                let eventDuration = 60; // Default duration
                if (event.extendedProps && event.extendedProps.duration_minutes) {
                    eventDuration = parseInt(event.extendedProps.duration_minutes);
                } else if (event._def && event._def.extendedProps && event._def.extendedProps.duration_minutes) {
                    eventDuration = parseInt(event._def.extendedProps.duration_minutes);
                } else if (event.end) {
                    // Calculate duration from start/end if available
                    const eventEnd = new Date(event.end);
                    if (!isNaN(eventEnd.getTime())) {
                        eventDuration = Math.max(1, (eventEnd.getTime() - eventStart.getTime()) / (1000 * 60));
                    }
                }
                
                // Ensure valid duration
                if (isNaN(eventDuration) || eventDuration <= 0) {
                    eventDuration = 60; // fallback to 60 minutes
                }
                
                const eventEnd = new Date(eventStart.getTime() + (eventDuration * 60000));
                
                // Skip events that seem to be all-day or have suspicious times (like midnight default)
                const isAllDayEvent = event.allDay || (eventStart.getHours() === 0 && eventStart.getMinutes() === 0 && eventDuration >= 1440);
                if (isAllDayEvent) {
                    debugLog('Skipping all-day event', { 
                        eventId: event.id,
                        eventTitle: event.title,
                        eventStart: eventStart.toISOString(),
                        allDay: event.allDay,
                        duration: eventDuration
                    });
                    continue;
                }
                
                debugLog('Checking against existing event', { 
                    eventId: event.id,
                    eventTitle: event.title,
                    eventStart: eventStart.toISOString(), 
                    eventEnd: eventEnd.toISOString(), 
                    eventDuration,
                    eventExtendedProps: event.extendedProps
                });
                
                // Check for overlap - sessions are on the same day and times overlap
                const sessionDay = sessionStart.toDateString();
                const eventDay = eventStart.toDateString();
                
                if (sessionDay === eventDay) {
                    // Simple overlap check without confusing buffers
                    // Two sessions overlap if: (start1 < end2) AND (end1 > start2)
                    if ((sessionStart < eventEnd) && (sessionEnd > eventStart)) {
                        debugLog('Time conflict detected!', { 
                            newSession: { 
                                start: sessionStart.toISOString(), 
                                end: sessionEnd.toISOString(),
                                day: sessionDay
                            },
                            existingEvent: { 
                                start: eventStart.toISOString(), 
                                end: eventEnd.toISOString(),
                                day: eventDay,
                                title: event.title,
                                id: event.id
                            }
                        });
                        return {
                            hasConflict: true,
                            conflictingEvent: event
                        };
                    }
                }
            }
            
            debugLog('No time conflict found');
            return { hasConflict: false };
        }

        // Mark session pill as disabled visually
        function markSessionAsScheduled(sessionId) {
            const sessionPills = document.querySelectorAll(`[data-session-id="${sessionId}"]`);
            sessionPills.forEach(pill => {
                pill.classList.add('disabled');
                pill.draggable = false;
                debugLog('Marked session as scheduled', sessionId);
            });
            scheduledSessions.add(sessionId);
        }

        function createSessionEvent(date, sessionData) {
            debugLog('Creating session event', { date: date.toISOString(), sessionData });
            
            const duration = parseInt(sessionData.duration || '60');
            
            // Check if the session is being scheduled in the past
            const now = new Date();
            const sessionDate = new Date(date);
            
            if (sessionDate < now) {
                const pastTime = sessionDate.toLocaleString('ar-EG', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                showNotification(`لا يمكن جدولة جلسة في وقت ماضي (${pastTime})`, 'error');
                return false;
            }
            
            // Check for time conflicts
            const conflictCheck = checkTimeConflict(date, duration);
            if (conflictCheck.hasConflict) {
                const conflictTime = conflictCheck.conflictingEvent.start.toLocaleTimeString('ar-EG', {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                showNotification(`يوجد جلسة أخرى في نفس الوقت (${conflictTime}). لا يمكن للمعلم أن يكون في مكانين في نفس الوقت!`, 'error');
                // DO NOT disable pill if there's a conflict - session wasn't created
                return false;
            }
            
            const eventData = {
                id: 'temp-' + sessionData.sessionId,
                title: sessionData.title || 'جلسة جديدة',
                start: date.toISOString(),
                duration: duration + ':00',
                backgroundColor: '#10b981',
                borderColor: '#059669',
                extendedProps: {
                    is_temporary: true,
                    session_id: sessionData.sessionId,
                    circle_id: sessionData.circleId,
                    session_type: 'individual',
                    duration_minutes: duration
                }
            };
            
            debugLog('Adding event to calendar', eventData);
            calendar.addEvent(eventData);
            
            // Add to pending changes
            addPendingChange({
                type: 'schedule',
                sessionId: 'temp-' + sessionData.sessionId,
                date: date.toISOString(),
                circleId: sessionData.circleId,
                duration: duration
            });
            
            // ONLY mark session as scheduled if we successfully created it
            markSessionAsScheduled(sessionData.sessionId);
            if (currentlyDraggedPill) {
                currentlyDraggedPill.remove();
                updateCircleSessionCount(sessionData.circleId);
            }
            
            showNotification('تم جدولة الجلسة بنجاح', 'success');
            debugLog('Session event created successfully');
            return true;
        }

        function handleEventClick(info) {
            showSessionDetails(info.event);
        }

        function handleEventDrop(info) {
            addPendingChange({
                type: 'move',
                sessionId: info.event.id,
                date: info.event.startStr,
                originalDate: info.oldEvent.startStr
            });
            showChangesIndicator();
        }

        function handleEventResize(info) {
            addPendingChange({
                type: 'resize',
                sessionId: info.event.id,
                duration: info.event.end ? 
                    Math.round((info.event.end - info.event.start) / (1000 * 60)) : 60
            });
            showChangesIndicator();
        }

        function addPendingChange(change) {
            pendingChanges.push(change);
            showSaveButton();
            showChangesIndicator();
        }

        function showSaveButton() {
            document.getElementById('saveChangesBtn').classList.remove('hidden');
        }

        function hideSaveButton() {
            document.getElementById('saveChangesBtn').classList.add('hidden');
        }

        function showChangesIndicator() {
            document.getElementById('changesIndicator').classList.add('show');
        }

        function hideChangesIndicator() {
            document.getElementById('changesIndicator').classList.remove('show');
        }

        function saveAllChanges() {
            if (pendingChanges.length === 0) {
                return;
            }
            
            const saveBtn = document.getElementById('saveChangesBtn');
            saveBtn.textContent = '⏳ جارٍ الحفظ...';
            saveBtn.disabled = true;
            
            fetch(`{{ route('teacher.calendar.bulk-update', ['subdomain' => request()->route('subdomain')]) }}`, {
        method: 'POST',
        headers: {
                    'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    changes: pendingChanges
                })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
                    pendingChanges = [];
                    hideSaveButton();
                    hideChangesIndicator();
                    
                    calendar.getEvents().forEach(event => {
                        if (event.extendedProps.is_temporary) {
                            event.remove();
                        }
                    });
                    
            calendar.refetchEvents();
                    showNotification('تم حفظ التغييرات بنجاح', 'success');
        } else {
                    showNotification('خطأ في حفظ التغييرات: ' + data.message, 'error');
        }
    })
    .catch(error => {
                console.error('Error saving changes:', error);
                showNotification('حدث خطأ في حفظ التغييرات', 'error');
            })
            .finally(() => {
                saveBtn.textContent = '💾 حفظ التغييرات';
                saveBtn.disabled = false;
            });
        }

        function refreshCalendar() {
            calendar.refetchEvents();
            updateStats();
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-24 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all transform translate-x-full`;
            
            switch(type) {
                case 'success':
                    notification.classList.add('bg-green-500', 'text-white');
                    break;
                case 'error':
                    notification.classList.add('bg-red-500', 'text-white');
                    break;
                case 'warning':
                    notification.classList.add('bg-yellow-500', 'text-white');
                    break;
                default:
                    notification.classList.add('bg-blue-500', 'text-white');
            }
            
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.remove('translate-x-full'), 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function showSessionDetails(event) {
            // Implementation for session details modal
            console.log('Show session details for:', event);
        }

        // Reset individual circle sessions
        function resetIndividualCircleSessions(circleId) {
            debugLog('Reset individual circle sessions called', { circleId });
            
            if (!confirm('هل أنت متأكد من أنك تريد إعادة تعيين جميع جلسات هذه الحلقة الفردية؟ سيتم حذف جميع الجلسات المجدولة.')) {
                return;
            }
            
            // Get all events for this individual circle
            const eventsToRemove = calendar.getEvents().filter(event => 
                event.extendedProps.circle_id == circleId && 
                event.extendedProps.session_type === 'individual'
            );
            
            debugLog('Found events to remove', { count: eventsToRemove.length, events: eventsToRemove });
            
            // Remove events from calendar
            eventsToRemove.forEach(event => {
                const sessionId = event.extendedProps.session_id;
                if (sessionId) {
                    // Remove from scheduled sessions tracking
                    scheduledSessions.delete(sessionId.toString());
                    
                    // Re-enable the session pill if it exists - ENHANCED VERSION
                    debugLog('Looking for pills to restore', { sessionId, circleId });
                    
                    // Search in the specific circle container first
                    const sessionContainer = document.getElementById('sessions-' + circleId);
                    if (sessionContainer) {
                        const sessionPills = sessionContainer.querySelectorAll(`[data-session-id="${sessionId}"]`);
                        debugLog('Found pills in circle container', { count: sessionPills.length });
                        
                        sessionPills.forEach(pill => {
                            pill.classList.remove('disabled');
                            pill.draggable = true;
                            pill.style.cssText = ''; // Reset all inline styles
                            pill.style.display = 'inline-block';
                            pill.style.opacity = '1';
                            pill.style.pointerEvents = 'auto';
                            pill.style.visibility = 'visible';
                            debugLog('FULLY restored session pill', sessionId);
                        });
                    }
                    
                    // Also search globally to catch any missed pills
                    const allSessionPills = document.querySelectorAll(`[data-session-id="${sessionId}"]`);
                    allSessionPills.forEach(pill => {
                        pill.classList.remove('disabled');
                        pill.draggable = true;
                        pill.style.cssText = ''; // Reset all inline styles
                        pill.style.display = 'inline-block';
                        pill.style.opacity = '1';
                        pill.style.pointerEvents = 'auto';
                        pill.style.visibility = 'visible';
                        debugLog('GLOBALLY restored session pill', sessionId);
                    });
                }
                
                event.remove();
                debugLog('Removed event from calendar', event.id);
            });
            
            // Remove from pending changes
            pendingChanges = pendingChanges.filter(change => 
                !(change.circleId == circleId && change.type === 'schedule')
            );
            
            // Update session count and container styling
            updateCircleSessionCount(circleId);
            
            // FORCE restore the entire session container and all pills
            const sessionContainer = document.getElementById('sessions-' + circleId);
            if (sessionContainer) {
                sessionContainer.style.display = 'block';
                sessionContainer.style.visibility = 'visible';
                
                // FORCE all pills in container to be fully visible and draggable
                const allPills = sessionContainer.querySelectorAll('.session-pill');
                debugLog('Force restoring ALL pills in container', { circleId, pillCount: allPills.length });
                
                allPills.forEach((pill, index) => {
                    pill.classList.remove('disabled');
                    pill.draggable = true;
                    pill.style.cssText = ''; // Reset all inline styles
                    pill.style.display = 'inline-block';
                    pill.style.opacity = '1';
                    pill.style.pointerEvents = 'auto';
                    pill.style.visibility = 'visible';
                    
                    debugLog(`Pill ${index} restored:`, { 
                        sessionId: pill.getAttribute('data-session-id'),
                        classes: pill.className,
                        style: pill.style.cssText || 'none'
                    });
                });
                
                debugLog('Container restoration complete', { 
                    circleId, 
                    containerVisible: true,
                    pillCount: allPills.length 
                });
            } else {
                debugLog('ERROR: Session container not found!', { circleId });
            }
            
            showNotification(`تم إعادة تعيين ${eventsToRemove.length} جلسة بنجاح`, 'success');
            debugLog('Individual circle sessions reset completed', { circleId, removedCount: eventsToRemove.length });
        }

        // Bulk scheduling for individual circles
        function bulkScheduleIndividual(circleId, monthlySessionsLimit = 0, sessionDuration = 60) {
            debugLog('Bulk schedule individual called', { circleId, monthlySessionsLimit, sessionDuration });
            
            const sessionContainer = document.getElementById('sessions-' + circleId);
            const sessionPills = sessionContainer.querySelectorAll('.session-pill');
            
            if (sessionPills.length === 0) {
                showNotification('لا توجد جلسات متاحة للجدولة', 'warning');
        return;
    }
    
            // Calculate recommended weekdays based on subscription - not a hard limit
            let recommendedWeekdays = 2; // Default recommendation
            if (monthlySessionsLimit > 0) {
                // Calculate recommended weekdays per week based on monthly limit
                // Monthly sessions / 4 weeks = sessions per week
                recommendedWeekdays = Math.ceil(monthlySessionsLimit / 4);
                debugLog('Calculated recommended weekdays based on subscription', { 
                    monthlySessionsLimit, 
                    recommendedWeekdays 
                });
            }
            
            showIndividualSchedulingModal(circleId, sessionPills, recommendedWeekdays, monthlySessionsLimit);
        }

        function showIndividualSchedulingModal(circleId, sessionPills, recommendedWeekdays = 2, monthlySessionsLimit = 0) {
            debugLog('Creating individual scheduling modal', { 
                circleId, 
                sessionCount: sessionPills.length, 
                recommendedWeekdays, 
                monthlySessionsLimit 
            });
            
            const modal = document.createElement('div');
            modal.className = 'modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4';
            
            const weekdayOptions = [
                { value: 0, name: 'الأحد' },
                { value: 1, name: 'الاثنين' },
                { value: 2, name: 'الثلاثاء' },
                { value: 3, name: 'الأربعاء' },
                { value: 4, name: 'الخميس' },
                { value: 5, name: 'الجمعة' },
                { value: 6, name: 'السبت' }
            ];
            
            const weekdayCheckboxes = weekdayOptions.map(day => `
                <label class="flex items-center space-x-2 space-x-reverse">
                    <input type="checkbox" name="weekdays" value="${day.value}" class="rounded weekday-checkbox" onchange="checkIndividualWeekdayRecommendation(${recommendedWeekdays})">
                    <span class="text-sm">${day.name}</span>
                </label>
            `).join('');
            
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">جدولة الجلسات الفردية</h3>
                        <button onclick="this.closest('.modal-overlay').remove()" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    
                    ${monthlySessionsLimit > 0 ? `
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                        <h4 class="text-sm font-semibold text-green-800 mb-1">💡 توصية ذكية</h4>
                        <p class="text-xs text-green-700">📊 ${monthlySessionsLimit} جلسة/شهر - الموصى به: ${recommendedWeekdays} أيام/أسبوع</p>
                        <p class="text-xs text-green-600 mt-1">يمكنك اختيار أكثر أو أقل حسب احتياجاتك</p>
                    </div>
                    ` : ''}
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                أيام الأسبوع (مرن - يمكنك الاختيار بحرية)
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                ${weekdayCheckboxes}
                            </div>
                            <div id="weekday-recommendation-info" class="text-xs text-blue-600 mt-2">
                                💡 الموصى به: ${recommendedWeekdays} أيام في الأسبوع
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ بداية الجدولة (اختياري)</label>
                            <input type="date" id="individualStartDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg" min="${new Date().toISOString().split('T')[0]}">
                            <p class="text-xs text-gray-500 mt-1">اتركه فارغاً للبدء من اليوم</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">وقت الجلسة</label>
                            <input type="time" id="individualTime" class="w-full px-3 py-2 border border-gray-300 rounded-lg" value="16:00">
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button onclick="saveIndividualSchedule(${circleId})" class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            جدولة الجلسات
                        </button>
                        <button onclick="this.closest('.modal-overlay').remove()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                            إلغاء
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            debugLog('Individual scheduling modal created and added to DOM');
        }
        
        function checkWeekdayLimit(maxWeekdays) {
            const checkedBoxes = document.querySelectorAll('input[name="weekdays"]:checked');
            const warning = document.getElementById('weekday-limit-warning');
            
            if (checkedBoxes.length > maxWeekdays) {
                warning.classList.remove('hidden');
                // Disable the last checked checkbox
                checkedBoxes[checkedBoxes.length - 1].checked = false;
                showNotification(`الحد الأقصى المسموح: ${maxWeekdays} أيام في الأسبوع`, 'warning');
            } else {
                warning.classList.add('hidden');
            }
        }
        
        function checkGroupWeekdayLimit(maxWeekdays) {
            const checkedBoxes = document.querySelectorAll('input[name="weekdays"]:checked');
            const warning = document.getElementById('group-weekday-limit-warning');
            
            if (checkedBoxes.length > maxWeekdays) {
                warning.classList.remove('hidden');
                // Disable the last checked checkbox
                checkedBoxes[checkedBoxes.length - 1].checked = false;
                showNotification(`الحد الأقصى المسموح للحلقة: ${maxWeekdays} أيام في الأسبوع`, 'warning');
            } else {
                warning.classList.add('hidden');
            }
        }
        
        // New function for individual circles - provides recommendation instead of hard limit
        function checkIndividualWeekdayRecommendation(recommendedWeekdays) {
            const checkedBoxes = document.querySelectorAll('input[name="weekdays"]:checked');
            const infoDiv = document.getElementById('weekday-recommendation-info');
            const selectedCount = checkedBoxes.length;
            
            if (!infoDiv) return;
            
            if (selectedCount === 0) {
                infoDiv.innerHTML = `💡 الموصى به: ${recommendedWeekdays} أيام في الأسبوع`;
                infoDiv.className = 'text-xs text-blue-600 mt-2';
            } else if (selectedCount === recommendedWeekdays) {
                infoDiv.innerHTML = `✅ ممتاز! اخترت ${selectedCount} أيام (الموصى به)`;
                infoDiv.className = 'text-xs text-green-600 mt-2';
            } else if (selectedCount < recommendedWeekdays) {
                infoDiv.innerHTML = `📉 اخترت ${selectedCount} أيام (أقل من الموصى به: ${recommendedWeekdays})`;
                infoDiv.className = 'text-xs text-orange-600 mt-2';
            } else if (selectedCount <= recommendedWeekdays + 2) {
                infoDiv.innerHTML = `📈 اخترت ${selectedCount} أيام (أكثر من الموصى به: ${recommendedWeekdays} - ولكن لا بأس)`;
                infoDiv.className = 'text-xs text-blue-600 mt-2';
            } else {
                infoDiv.innerHTML = `⚠️ اخترت ${selectedCount} أيام (أكثر بكثير من الموصى به: ${recommendedWeekdays}). تأكد من وجود جلسات كافية`;
                infoDiv.className = 'text-xs text-amber-600 mt-2';
            }
        }

        function saveIndividualSchedule(circleId) {
            debugLog('saveIndividualSchedule called', { circleId });
            
            const modal = document.querySelector('.modal-overlay');
            if (!modal) {
                debugLog('Modal not found');
                showNotification('خطأ: النافذة غير موجودة', 'error');
                return;
            }
            
            // Get selected days with detailed debugging
            const weekdayCheckboxes = modal.querySelectorAll('input[name="weekdays"]');
            const selectedDays = [];
            
            debugLog(`Found ${weekdayCheckboxes.length} weekday checkboxes`);
            
            weekdayCheckboxes.forEach((checkbox, index) => {
                debugLog(`Checkbox ${index}: value=${checkbox.value}, checked=${checkbox.checked}`);
                if (checkbox.checked) {
                    selectedDays.push(parseInt(checkbox.value));
                }
            });
            
            debugLog('Selected days:', selectedDays);
            
            const timeInput = modal.querySelector('#individualTime');
            const time = timeInput ? timeInput.value : '16:00';
            
            debugLog('Selected time:', time);
            
            if (selectedDays.length === 0) {
                debugLog('No days selected - showing warning');
                showNotification('يرجى اختيار يوم واحد على الأقل', 'warning');
                return;
            }
            
            const sessionContainer = document.getElementById('sessions-' + circleId);
            if (!sessionContainer) {
                debugLog('Session container not found');
                showNotification('خطأ في العثور على الجلسات', 'error');
                modal.remove();
                return;
            }
            
            const sessionPills = sessionContainer.querySelectorAll('.session-pill');
            debugLog(`Found ${sessionPills.length} session pills`);
            
            // For individual circles, allow flexible scheduling even if limited template sessions exist
            let availableSessions = sessionPills.length;
            let needsMoreSessions = false;
            
            // Calculate how many sessions we might need based on selected days
            const selectedDaysCount = selectedDays.length;
            const estimatedSessionsNeeded = selectedDaysCount * 4; // Roughly 4 weeks worth
            
            if (availableSessions === 0) {
                showNotification('لا توجد جلسات للجدولة', 'warning');
                modal.remove();
                return;
            }
            
            // If we have fewer sessions than selected days, we'll create more on-demand
            if (availableSessions < selectedDaysCount) {
                needsMoreSessions = true;
                debugLog(`Will need to create additional sessions. Available: ${availableSessions}, Days selected: ${selectedDaysCount}`);
            }
            
            // NEW FLEXIBLE APPROACH: Schedule sessions for all selected days, not limited by available pills
            let scheduledCount = 0;
            let conflictCount = 0;
            let sessionCounter = 0;
            let week = 0;
            
            // Create a pool of available sessions and extend it if needed
            const pillsArray = Array.from(sessionPills);
            const maxSessionsToSchedule = Math.max(selectedDaysCount * 8, pillsArray.length); // At least 8 weeks worth
            
            debugLog('Starting flexible individual scheduling...', { 
                availablePills: pillsArray.length,
                selectedDays: selectedDaysCount,
                maxToSchedule: maxSessionsToSchedule
            });
            
            // Schedule sessions for multiple weeks across all selected days
            while (sessionCounter < maxSessionsToSchedule && week < 12) { // Max 12 weeks
                selectedDays.forEach((targetDay, dayIndex) => {
                    if (sessionCounter >= maxSessionsToSchedule) return;
                    
                    const sessionDate = new Date();
                    const today = new Date();
                    
                    // Calculate the date for this specific day of the week
                    let daysUntilTarget = (targetDay - today.getDay()) % 7;
                    if (daysUntilTarget <= 0) daysUntilTarget += 7; // Always schedule for future
                    
                    sessionDate.setDate(today.getDate() + daysUntilTarget + (week * 7));
                    sessionDate.setHours(parseInt(time.split(':')[0]), parseInt(time.split(':')[1]));
                    
                    // Use existing pill if available, otherwise create virtual session
                    const pill = pillsArray[sessionCounter] || null;
                    const duration = pill ? parseInt(pill.dataset.duration || '60') : 60;
                    const sessionId = pill ? pill.dataset.sessionId : `virtual-${circleId}-${sessionCounter}`;
                    const circleIdFromPill = pill ? pill.dataset.circleId : circleId;
                    const title = pill ? (pill.dataset.title || pill.textContent.trim()) : `جلسة فردية ${sessionCounter + 1}`;
                    
                    debugLog(`Scheduling session ${sessionCounter}:`, {
                        week,
                        targetDay,
                        sessionDate: sessionDate.toISOString(),
                        isVirtual: !pill
                    });
                    
                    // Check for time conflicts
                    const conflictCheck = checkTimeConflict(sessionDate, duration);
                    if (conflictCheck.hasConflict) {
                        debugLog('Skipping individual session due to conflict', {
                            sessionDate: sessionDate.toISOString(),
                            conflictingSession: conflictCheck.conflictingEvent.title
                        });
                        conflictCount++;
                    } else {
                        const eventData = {
                            id: 'temp-' + sessionId,
                            title: title,
                            start: sessionDate.toISOString(),
                            duration: duration + ':00',
                            backgroundColor: '#10b981',
                            borderColor: '#059669',
                            extendedProps: {
                                is_temporary: true,
                                session_id: sessionId,
                                circle_id: circleIdFromPill,
                                session_type: 'individual',
                                duration_minutes: duration,
                                is_virtual: !pill // Mark virtual sessions
                            }
                        };
                        
                        calendar.addEvent(eventData);
                        
                        addPendingChange({
                            type: 'schedule',
                            sessionId: 'temp-' + sessionId,
                            date: sessionDate.toISOString(),
                            circleId: circleIdFromPill,
                            duration: duration,
                            isVirtual: !pill
                        });
                        
                        // Mark session as scheduled if it's a real session
                        if (pill) {
                            markSessionAsScheduled(sessionId);
                            pill.remove();
                        }
                        
                        scheduledCount++;
                    }
                    
                    sessionCounter++;
                });
                
                week++;
            }
            
            // Remove any remaining unused pills
            pillsArray.forEach(pill => {
                if (pill.parentNode) {
                    pill.remove();
                }
            });
            
            // Ensure modal is removed
            const modalToRemove = document.querySelector('.modal-overlay');
            if (modalToRemove) {
                modalToRemove.remove();
                debugLog('Modal removed successfully');
            }
            
            updateCircleSessionCount(circleId);
            
            // Show success/warning message based on conflicts
            let message = `تم جدولة ${scheduledCount} جلسة بنجاح`;
            if (conflictCount > 0) {
                message += `. تم تخطي ${conflictCount} جلسة بسبب التعارض في المواعيد`;
                showNotification(message, 'warning');
            } else {
                showNotification(message, 'success');
            }
            
            debugLog('Individual scheduling completed', { scheduledCount, conflictCount });
        }

        // Recurring schedule for group circles  
        function setupRecurringSchedule(circleId, monthlySessionsLimit = 8, sessionDuration = 60, overrideStartDate = null, overrideSessionCount = null) {
            debugLog('Creating recurring schedule modal', { circleId, monthlySessionsLimit, sessionDuration });
            
            const modal = document.createElement('div');
            modal.className = 'modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4';
            
            // Calculate max weekdays for group circles
            const maxGroupWeekdays = Math.min(Math.ceil(monthlySessionsLimit / 4), 7);
            
            const weekdayOptions = [
                { value: 0, name: 'الأحد' },
                { value: 1, name: 'الاثنين' },
                { value: 2, name: 'الثلاثاء' },
                { value: 3, name: 'الأربعاء' },
                { value: 4, name: 'الخميس' },
                { value: 5, name: 'الجمعة' },
                { value: 6, name: 'السبت' }
            ];
            
            const weekdayCheckboxes = weekdayOptions.map(day => `
                <label class="flex items-center space-x-2 space-x-reverse">
                    <input type="checkbox" name="weekdays" value="${day.value}" class="rounded weekday-checkbox" onchange="checkGroupWeekdayLimit(${maxGroupWeekdays})">
                    <span class="text-sm">${day.name}</span>
                </label>
            `).join('');
            
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">إعداد الجدولة المتكررة</h3>
                        <button onclick="this.closest('.modal-overlay').remove()" class="text-gray-400 hover:text-gray-600">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    
                    <div class="bg-green-50 border border-green-200 rounded-lg p-3 mb-4">
                        <h4 class="text-sm font-semibold text-green-800 mb-1">إعدادات الحلقة</h4>
                        <p class="text-xs text-green-700">📊 ${monthlySessionsLimit} جلسات/شهر - حد إجباري: ${maxGroupWeekdays} أيام/أسبوع</p>
                        <p class="text-xs text-green-700">⏱️ مدة الجلسة: ${sessionDuration} دقيقة</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                أيام الأسبوع (حد إجباري: ${maxGroupWeekdays})
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                ${weekdayCheckboxes}
                            </div>
                            <div id="group-weekday-limit-warning" class="text-xs text-red-600 mt-2 hidden">
                                تجاوزت الحد المسموح من الأيام للحلقة
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ بداية الجدولة</label>
                            <input type="date" id="recurringStartDate" class="w-full px-3 py-2 border border-gray-300 rounded-lg" 
                                   value="${new Date().toISOString().split('T')[0]}" min="${new Date().toISOString().split('T')[0]}">
                        </div>
                        
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <label class="block text-sm font-semibold text-blue-800 mb-2">
                                📊 عدد الجلسات المطلوب إنشاؤها (تحديد يدوي)
                            </label>
                            <input type="number" id="recurringSessionCount" 
                                   class="w-full px-4 py-3 border-2 border-blue-300 rounded-lg focus:border-blue-500 focus:ring-2 focus:ring-blue-200 text-lg font-medium text-center" 
                                   value="${monthlySessionsLimit}" min="1" max="100" 
                                   placeholder="أدخل العدد المطلوب"
                                   onchange="updateSessionCountPreview('recurring')"
                                   oninput="updateSessionCountPreview('recurring')">
                            <p class="text-xs text-blue-600 mt-2 font-medium">
                                💡 حدد بنفسك عدد الجلسات التي تريد إنشاءها (افتراضي: ${monthlySessionsLimit})
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                يمكنك إنشاء من 1 إلى 100 جلسة حسب احتياجاتك
                            </p>
                        </div>
                        
                        <!-- SESSION COUNT FIELD - WORKING VERSION -->
                        <div style="background-color: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 16px; margin: 12px 0;">
                            <label style="display: block; font-weight: bold; color: #92400e; margin-bottom: 8px; font-size: 16px;">
                                🔢 عدد الجلسات المطلوب إنشاؤها (تحديد يدوي)
                            </label>
                            <input type="number" id="recurringSessionCount" 
                                   style="width: 100%; padding: 12px; font-size: 18px; text-align: center; border: 2px solid #f59e0b; border-radius: 6px; font-weight: bold;"
                                   value="${monthlySessionsLimit}" min="1" max="100" 
                                   placeholder="ادخل العدد">
                            <p style="color: #92400e; font-size: 12px; margin-top: 8px; font-weight: bold;">
                                💡 اكتب الرقم الذي تريده من 1 إلى 100 جلسة
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">وقت البداية</label>
                            <input type="time" id="recurringTime" class="w-full px-3 py-2 border border-gray-300 rounded-lg" value="16:00">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">مدة الجلسة</label>
                            <select id="recurringDuration" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                <option value="30" ${sessionDuration === 30 ? 'selected' : ''}>30 دقيقة</option>
                                <option value="60" ${sessionDuration === 60 ? 'selected' : ''}>60 دقيقة</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 mt-6">
                        <button onclick="saveRecurringSchedule(${circleId})" class="flex-1 bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                            بدء الجدولة
                        </button>
                        <button onclick="this.closest('.modal-overlay').remove()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors">
                            إلغاء
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            debugLog('Recurring schedule modal created and added to DOM');
        }

                        function saveRecurringSchedule(circleId) {
            debugLog('saveRecurringSchedule called', { circleId });
            
            const modal = document.querySelector('.modal-overlay');
            if (!modal) {
                debugLog('Modal not found');
                showNotification('خطأ: النافذة غير موجودة', 'error');
                return;
            }
            
            // Check if this circle already has scheduled sessions
            const existingGroupSessions = calendar.getEvents().filter(event => 
                event.extendedProps.circle_id == circleId && 
                event.extendedProps.session_type === 'group'
            );
            
            if (existingGroupSessions.length > 0) {
                debugLog('Circle already has scheduled sessions', { count: existingGroupSessions.length });
                
                // Ask user if they want to replace existing sessions
                if (confirm('هذه الحلقة لديها جلسات مجدولة بالفعل. هل تريد استبدالها بجدولة جديدة؟')) {
                    // Remove existing sessions
                    existingGroupSessions.forEach(session => {
                        session.remove();
                        debugLog('Removed existing group session:', session.id);
                    });
                    
                    // Clear from pending changes if they exist
                    pendingChanges = pendingChanges.filter(change => 
                        !(change.circleId == circleId && change.type === 'schedule')
                    );
                    
                    debugLog('Cleared existing sessions for circle', circleId);
                } else {
                    // User chose not to replace, exit
                    return;
                }
            }
            
            // Get selected days with detailed debugging
            const weekdayCheckboxes = modal.querySelectorAll('input[name="weekdays"]');
            const selectedDays = [];
            
            debugLog(`Found ${weekdayCheckboxes.length} weekday checkboxes`);
            
            weekdayCheckboxes.forEach((checkbox, index) => {
                debugLog(`Checkbox ${index}: value=${checkbox.value}, checked=${checkbox.checked}`);
                if (checkbox.checked) {
                    selectedDays.push(parseInt(checkbox.value));
                }
            });
            
            debugLog('Selected days:', selectedDays);
            
            const timeInput = modal.querySelector('#recurringTime');
            const durationInput = modal.querySelector('#recurringDuration');
            const startDateInput = modal.querySelector('#recurringStartDate');
            const sessionCountInput = modal.querySelector('#recurringSessionCount');
            
            const time = timeInput ? timeInput.value : '16:00';
            const duration = durationInput ? durationInput.value : '60';
            const startDate = overrideStartDate ? new Date(overrideStartDate) : (startDateInput ? new Date(startDateInput.value) : new Date());
            const sessionCount = overrideSessionCount ? overrideSessionCount : (sessionCountInput ? parseInt(sessionCountInput.value) : 8);
            
            debugLog('Selected time:', time);
            debugLog('Selected duration:', duration);
            debugLog('Selected start date:', startDate);
            debugLog('Selected session count:', sessionCount);
            
            if (selectedDays.length === 0) {
                debugLog('No days selected - showing warning');
                showNotification('يرجى اختيار يوم واحد على الأقل', 'warning');
                return;
            }
            
            // Generate sessions based on user-specified count and start date
            const potentialSessions = [];
            const conflicts = [];
            
            debugLog('Pre-checking conflicts for recurring sessions...', { sessionCount, startDate, selectedDays });
            
            // Validate session count
            if (sessionCount <= 0 || sessionCount > 100) {
                showNotification('عدد الجلسات يجب أن يكون بين 1 و 100', 'warning');
                return;
            }
            
            // Ensure start date is not in the past
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (startDate < today) {
                showNotification('تاريخ البداية لا يمكن أن يكون في الماضي', 'warning');
                return;
            }
            
            // Generate sessions starting from the specified start date
            let sessionsGenerated = 0;
            let currentDate = new Date(startDate);
            let searchWeeks = 0;
            
            while (sessionsGenerated < sessionCount && searchWeeks < 104) { // Max 2 years search
                selectedDays.forEach(dayOfWeek => {
                    if (sessionsGenerated >= sessionCount) return;
                    
                    // Find the next occurrence of this day of week from current date
                    const sessionDate = new Date(currentDate);
                    let daysUntilTarget = (dayOfWeek - sessionDate.getDay()) % 7;
                    if (daysUntilTarget < 0) daysUntilTarget += 7;
                    
                    sessionDate.setDate(sessionDate.getDate() + daysUntilTarget);
                    sessionDate.setHours(parseInt(time.split(':')[0]), parseInt(time.split(':')[1]), 0, 0);
                    
                    // Only add if this session date is on or after start date
                    if (sessionDate >= startDate) {
                        potentialSessions.push({
                            date: sessionDate,
                            dayOfWeek,
                            week: searchWeeks
                        });
                        sessionsGenerated++;
                    }
                });
                
                // Move to next week
                currentDate.setDate(currentDate.getDate() + 7);
                searchWeeks++;
            }
            
            // Sort sessions by date to ensure proper order
            potentialSessions.sort((a, b) => a.date - b.date);
            
            debugLog(`Generated ${potentialSessions.length} potential sessions`);
            
            // Check all sessions for conflicts upfront
            potentialSessions.forEach((session, index) => {
                const conflictCheck = checkTimeConflict(session.date, parseInt(duration));
                if (conflictCheck.hasConflict) {
                    const conflictTime = conflictCheck.conflictingEvent.start.toLocaleTimeString('ar-EG', {
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    const conflictDate = conflictCheck.conflictingEvent.start.toLocaleDateString('ar-EG');
                    
                    conflicts.push({
                        sessionIndex: index,
                        sessionDate: session.date,
                        conflictDate,
                        conflictTime,
                        conflictingEvent: conflictCheck.conflictingEvent
                    });
                }
            });
            
            // If ANY conflicts exist, reject the entire schedule
            if (conflicts.length > 0) {
                debugLog(`Found ${conflicts.length} conflicts, rejecting entire schedule`, conflicts);
                
                let conflictMessage = `يوجد تعارض في المواعيد في ${conflicts.length} جلسة. لا يمكن إنشاء الجدولة:\n\n`;
                conflicts.slice(0, 5).forEach(conflict => { // Show first 5 conflicts
                    conflictMessage += `• ${conflict.conflictDate} في ${conflict.conflictTime}\n`;
                });
                
                if (conflicts.length > 5) {
                    conflictMessage += `• و ${conflicts.length - 5} تعارض إضافي...\n`;
                }
                
                conflictMessage += '\nيرجى حل التعارضات أولاً ثم المحاولة مرة أخرى.';
                
                alert(conflictMessage);
                showNotification('يوجد تعارض في المواعيد - لم يتم إنشاء أي جلسات', 'error');
                return;
            }
            
            debugLog('No conflicts found, creating all sessions...');
            
            // If no conflicts, create all sessions
            let sessionsCreated = 0;
            potentialSessions.forEach((session, index) => {
                const eventData = {
                    id: 'temp-group-' + circleId + '-' + sessionsCreated,
                    title: 'جلسة جماعية متكررة',
                    start: session.date.toISOString(),
                    duration: duration + ':00',
                    backgroundColor: '#059669',
                    borderColor: '#047857',
                    extendedProps: {
                        is_temporary: true,
                        circle_id: circleId,
                        session_type: 'group',
                        duration_minutes: parseInt(duration),
                        recurring: true // Mark as recurring for month navigation
                    }
                };
                
                calendar.addEvent(eventData);
                
                addPendingChange({
                    type: 'schedule',
                    sessionId: 'temp-group-' + circleId + '-' + sessionsCreated,
                    date: session.date.toISOString(),
                    circleId: circleId,
                    duration: duration,
                    title: 'جلسة جماعية متكررة'
                });
                
                sessionsCreated++;
            });
            
            // Ensure modal is removed
            const modalToRemove = document.querySelector('.modal-overlay');
            if (modalToRemove) {
                modalToRemove.remove();
                debugLog('Modal removed successfully');
            }
            
            // Show success message 
            if (sessionsCreated > 0) {
                showNotification(`تم إنشاء ${sessionsCreated} جلسة متكررة`, 'success');
                updateGroupCircleStatus(circleId, 'scheduled');
            } else {
                showNotification('لم يتم إنشاء أي جلسات بسبب التعارض في المواعيد', 'warning');
            }
            
            debugLog('Recurring scheduling completed', { sessionsCreated });
        }

        function updateCircleSessionCount(circleId) {
            const sessionContainer = document.getElementById('sessions-' + circleId);
            if (!sessionContainer) {
                debugLog('Session container not found for circle', circleId);
                return;
            }
            
            // Count only non-disabled session pills
            const allSessions = sessionContainer.querySelectorAll('.session-pill');
            const remainingSessions = Array.from(allSessions).filter(pill => !pill.classList.contains('disabled')).length;
            const scheduledSessions = allSessions.length - remainingSessions;
            
            debugLog('Updating circle session count', { 
                circleId, 
                total: allSessions.length, 
                remaining: remainingSessions, 
                scheduled: scheduledSessions 
            });
            
            const circleElement = sessionContainer.closest('.border');
            const pendingElement = circleElement ? circleElement.querySelector('.text-orange-800') : null;
            
            if (pendingElement) {
                if (remainingSessions > 0) {
                    pendingElement.textContent = remainingSessions + ' جلسة في انتظار الجدولة';
                } else {
                    pendingElement.textContent = 'جميع الجلسات مجدولة';
                }
            }
            
            // Don't replace the container content, just update status text
            // This preserves the session pills for potential reset
            const pendingContainer = circleElement ? circleElement.querySelector('.bg-orange-50') : null;
            if (pendingContainer && remainingSessions === 0) {
                // Just update the styling to show completion, but keep the pills
                pendingContainer.classList.remove('bg-orange-50', 'border-orange-200');
                pendingContainer.classList.add('bg-green-50', 'border-green-200');
                
                // Update the pending element styling
                if (pendingElement) {
                    pendingElement.classList.remove('text-orange-800');
                    pendingElement.classList.add('text-green-800');
                }
            } else if (pendingContainer && remainingSessions > 0) {
                // Reset to pending styling if there are remaining sessions
                pendingContainer.classList.remove('bg-green-50', 'border-green-200');
                pendingContainer.classList.add('bg-orange-50', 'border-orange-200');
                
                // Update the pending element styling
                if (pendingElement) {
                    pendingElement.classList.remove('text-green-800');
                    pendingElement.classList.add('text-orange-800');
                }
            }
        }

        function updateGroupCircleStatus(circleId, status) {
            const statusElement = document.getElementById('schedule-status-' + circleId);
            if (statusElement) {
                switch(status) {
                    case 'scheduled':
                        statusElement.textContent = '✅ مجدولة';
                        statusElement.className = 'text-xs bg-green-100 text-green-800 px-2 py-1 rounded';
                        break;
                    case 'not-scheduled':
                        statusElement.textContent = '❌ غير مجدولة';
                        statusElement.className = 'text-xs bg-red-100 text-red-800 px-2 py-1 rounded';
                        break;
                    case 'unknown':
                        statusElement.textContent = '❓ غير معروف';
                        statusElement.className = 'text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded';
                        break;
                    default:
                        statusElement.textContent = '🔍 فحص الحالة...';
                        statusElement.className = 'text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded';
                }
            }
        }

        function checkGroupCircleStatuses() {
            // Check each group circle to see if it has scheduled sessions
            const groupCircles = document.querySelectorAll('[id^="schedule-status-"]');
            groupCircles.forEach(statusElement => {
                const circleId = statusElement.id.replace('schedule-status-', '');
                checkSingleGroupCircleStatus(circleId);
            });
        }

        function checkSingleGroupCircleStatus(circleId) {
            fetch(`{{ route('teacher.calendar.events', ['subdomain' => request()->route('subdomain')]) }}?circle_id=${circleId}&type=group`)
    .then(response => response.json())
    .then(data => {
                    if (data.success && data.events) {
                        const hasScheduledSessions = data.events.length > 0;
                        updateGroupCircleStatus(circleId, hasScheduledSessions ? 'scheduled' : 'not-scheduled');
        } else {
                        updateGroupCircleStatus(circleId, 'not-scheduled');
        }
    })
    .catch(error => {
                    console.error('Error checking circle status:', error);
                    updateGroupCircleStatus(circleId, 'unknown');
                });
        }

        // Handle drag end for session pills
        document.addEventListener('dragend', function(e) {
            if (e.target.classList.contains('session-pill')) {
                e.target.classList.remove('dragging');
                
                // Remove calendar-dragging class and all hover effects
                const calendarEl = document.getElementById('calendar');
                if (calendarEl) {
                    calendarEl.classList.remove('calendar-dragging');
                }
                removeAllDragHoverEffects();
                
                debugLog('Global drag end with cleanup');
            }
        });

                    // Also need to reinitialize draggable elements when new sessions are added
            function reinitializeDraggableElements() {
                debugLog('Reinitializing draggable elements');
                setTimeout(() => {
                    initializeDraggableElements();
                }, 100);
            }
            
            // Call reinitialize after calendar events are loaded
            function afterCalendarLoad() {
                setTimeout(() => {
                    reinitializeDraggableElements();
                }, 200);
            }

            // New functions for circle selection and bulk scheduling

            // Update session count preview for group circles - provides real-time feedback
            function updateSessionCountPreview(modalType) {
                const sessionCountInput = modalType === 'recurring' 
                    ? document.getElementById('recurringSessionCount')
                    : document.getElementById('bulkGroupSessionCount');
                
                if (!sessionCountInput) return;
                
                const count = parseInt(sessionCountInput.value) || 0;
                const modal = sessionCountInput.closest('.modal-overlay');
                if (!modal) return;
                
                // Find the preview area (the blue info paragraph)
                const previewArea = sessionCountInput.parentElement.querySelector('.text-blue-600');
                if (!previewArea) return;
                
                // Update preview text based on the count
                if (count === 0) {
                    previewArea.innerHTML = '⚠️ يجب إدخال عدد الجلسات أولاً';
                    previewArea.className = 'text-xs text-red-600 mt-2 font-medium';
                    sessionCountInput.classList.add('border-red-300');
                    sessionCountInput.classList.remove('border-blue-300');
                } else if (count < 1) {
                    previewArea.innerHTML = '❌ عدد الجلسات يجب أن يكون 1 على الأقل';
                    previewArea.className = 'text-xs text-red-600 mt-2 font-medium';
                    sessionCountInput.classList.add('border-red-300');
                    sessionCountInput.classList.remove('border-blue-300');
                } else if (count > 100) {
                    previewArea.innerHTML = '⚠️ عدد الجلسات كبير جداً (الحد الأقصى: 100)';
                    previewArea.className = 'text-xs text-orange-600 mt-2 font-medium';
                    sessionCountInput.classList.add('border-orange-300');
                    sessionCountInput.classList.remove('border-blue-300');
                } else if (count <= 10) {
                    previewArea.innerHTML = `✅ سيتم إنشاء ${count} جلسة - عدد مناسب`;
                    previewArea.className = 'text-xs text-green-600 mt-2 font-medium';
                    sessionCountInput.classList.add('border-green-300');
                    sessionCountInput.classList.remove('border-blue-300', 'border-red-300', 'border-orange-300');
                } else if (count <= 30) {
                    previewArea.innerHTML = `📊 سيتم إنشاء ${count} جلسة - عدد جيد`;
                    previewArea.className = 'text-xs text-blue-600 mt-2 font-medium';
                    sessionCountInput.classList.add('border-blue-300');
                    sessionCountInput.classList.remove('border-red-300', 'border-orange-300', 'border-green-300');
                } else {
                    previewArea.innerHTML = `📈 سيتم إنشاء ${count} جلسة - عدد كبير`;
                    previewArea.className = 'text-xs text-indigo-600 mt-2 font-medium';
                    sessionCountInput.classList.add('border-indigo-300');
                    sessionCountInput.classList.remove('border-blue-300', 'border-red-300', 'border-orange-300', 'border-green-300');
                }
            }

            // SIMPLE AND DIRECT CIRCLE SELECTION - GUARANTEED TO WORK
            function makeCircleSelected(radioInput) {
                console.log('🎯 Making circle selected:', radioInput);
                
                // First: Remove highlighting from ALL circles
                document.querySelectorAll('.circle-selection').forEach(function(label) {
                    // Reset all circles to default gray border
                    label.style.border = '1px solid #d1d5db';
                    label.style.backgroundColor = 'white';
                    label.style.boxShadow = 'none';
                    label.style.transform = 'none';
                });
                
                // Second: Highlight the selected circle
                if (radioInput && radioInput.checked) {
                    const selectedLabel = radioInput.closest('.circle-selection');
                    if (selectedLabel) {
                        // Apply VERY OBVIOUS styling
                        selectedLabel.style.border = '4px solid #3b82f6';
                        selectedLabel.style.backgroundColor = '#eff6ff';
                        selectedLabel.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.3)';
                        selectedLabel.style.transform = 'scale(1.05)';
                        
                        console.log('✅ Applied blue border to selected circle');
                    }
                } else {
                    // Find checked radio if not provided
                    const checkedRadio = document.querySelector('input[name="selectedCircle"]:checked');
                    if (checkedRadio) {
                        const selectedLabel = checkedRadio.closest('.circle-selection');
                        if (selectedLabel) {
                            selectedLabel.style.border = '4px solid #3b82f6';
                            selectedLabel.style.backgroundColor = '#eff6ff';
                            selectedLabel.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.3)';
                            selectedLabel.style.transform = 'scale(1.05)';
                            
                            console.log('✅ Applied blue border to checked circle');
                        }
                    }
                }
            }
            
            // OLD COMPLEX FUNCTION - KEEPING FOR BACKUP
            function highlightSelectedCircle() {
                console.log('🎯 Running circle highlight function');
                
                // METHOD 1: Remove all selected classes and inline styles
                document.querySelectorAll('.circle-selection').forEach((label, index) => {
                    console.log(`📝 Resetting circle ${index}`);
                    
                    // Remove all possible classes
                    label.classList.remove('selected', 'circle-selected-force');
                    
                    // Clear inline styles
                    label.style.border = '';
                    label.style.backgroundColor = '';
                    label.style.boxShadow = '';
                    label.style.transform = '';
                });
                
                // METHOD 2: Find selected radio and apply multiple styling approaches
                const selectedRadio = document.querySelector('input[name="selectedCircle"]:checked');
                console.log('🔍 Selected radio found:', !!selectedRadio);
                
                if (selectedRadio) {
                    const selectedLabel = selectedRadio.closest('.circle-selection');
                    console.log('🏷️ Selected label found:', !!selectedLabel);
                    
                    if (selectedLabel) {
                        console.log('✅ Applying selection styles...');
                        
                        // APPROACH A: Add CSS class
                        selectedLabel.classList.add('selected');
                        selectedLabel.classList.add('circle-selected-force');
                        
                        // APPROACH B: Inline styles as backup
                        selectedLabel.style.border = '4px solid #3b82f6 !important';
                        selectedLabel.style.backgroundColor = '#eff6ff !important';
                        selectedLabel.style.boxShadow = '0 0 0 4px rgba(59, 130, 246, 0.2) !important';
                        selectedLabel.style.transform = 'scale(1.02) !important';
                        
                        // APPROACH C: Force CSS via setAttribute
                        selectedLabel.setAttribute('style', 
                            'border: 4px solid #3b82f6 !important; ' +
                            'background-color: #eff6ff !important; ' +
                            'box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2) !important; ' +
                            'transform: scale(1.02) !important;'
                        );
                        
                        console.log('🎨 All styling methods applied!');
                        
                        // VERIFICATION: Check if styles were applied
                        setTimeout(() => {
                            const computedStyle = window.getComputedStyle(selectedLabel);
                            console.log('🔍 Verification - Border:', computedStyle.border);
                            console.log('🔍 Verification - Background:', computedStyle.backgroundColor);
                            console.log('🔍 Verification - Classes:', selectedLabel.className);
                        }, 100);
                    }
                }
            }

            <!-- updateBulkScheduleButton function removed - no longer needed -->

            <!-- Bulk scheduling functionality removed - now handled in Filament dashboard -->

            <!-- Individual bulk scheduling modal removed - now handled in Filament dashboard -->

            <!-- All bulk scheduling functionality removed - now handled in Filament dashboard -->

            // SIMPLE INITIALIZATION - GUARANTEED TO WORK  
            document.addEventListener('DOMContentLoaded', function() {
                console.log('🚀 Page loaded - Setting up circle selection');
                
                // Apply highlighting to any pre-selected circle
                makeCircleSelected(document.querySelector('input[name="selectedCircle"]:checked'));
                
                // Set up simple event listeners
                document.querySelectorAll('input[name="selectedCircle"]').forEach(function(radio) {
                    radio.addEventListener('change', function() {
                        makeCircleSelected(this);
                    });
                    
                    radio.addEventListener('click', function() {
                        setTimeout(function() {
                            makeCircleSelected(document.querySelector('input[name="selectedCircle"]:checked'));
                        }, 10);
                    });
                });
                
                console.log('✅ Circle selection setup complete');
            });
            
            // BACKUP: Enhanced version
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM loaded, setting up circle highlighting'); // Debug
                
                // Add event listeners to all circle radio buttons - MULTIPLE EVENTS FOR RELIABILITY
                document.querySelectorAll('input[name="selectedCircle"]').forEach((radio, index) => {
                    console.log(`Setting up radio ${index}:`, radio); // Debug
                    
                    // Multiple event types to ensure it works
                    radio.addEventListener('change', function() {
                        console.log('Radio change event triggered'); // Debug
                        highlightSelectedCircle();
                    });
                    
                    radio.addEventListener('click', function() {
                        console.log('Radio click event triggered'); // Debug
                        setTimeout(() => {
                            highlightSelectedCircle();
                        }, 10); // Small delay to ensure radio is checked
                    });
                    
                    radio.addEventListener('input', function() {
                        console.log('Radio input event triggered'); // Debug
                        highlightSelectedCircle();
                    });
                });
                
                // ALSO add click listeners to the entire label for better UX
                document.querySelectorAll('.circle-selection').forEach((label, index) => {
                    console.log(`Setting up label ${index}:`, label); // Debug
                    
                    label.addEventListener('click', function() {
                        console.log('Label click event triggered'); // Debug
                        setTimeout(() => {
                            highlightSelectedCircle();
                        }, 50); // Delay to ensure radio state is updated
                    });
                });
                
                // Highlight any pre-selected circle on page load
                console.log('Running initial highlight check'); // Debug
                highlightSelectedCircle();
                
                // EMERGENCY BACKUP: Run highlight check every 2 seconds for the first 10 seconds (debug)
                let checkCount = 0;
                const intervalId = setInterval(() => {
                    console.log(`⚡ Emergency backup highlight check ${checkCount + 1}`);
                    highlightSelectedCircle();
                    checkCount++;
                    if (checkCount >= 5) {
                        clearInterval(intervalId);
                        console.log('⏹️ Stopped emergency backup highlight checks');
                    }
                }, 2000);
                
                // CONTINUOUS BACKUP: Keep checking every 5 seconds indefinitely
                setInterval(() => {
                    console.log('🔄 Continuous backup highlight check');
                    highlightSelectedCircle();
                }, 5000);
            });
            
            // MANUAL TEST FUNCTION - YOU CAN CALL THIS FROM BROWSER CONSOLE TO TEST
            window.testCircleHighlighting = function() {
                console.log('=== MANUAL CIRCLE HIGHLIGHTING TEST ===');
                console.log('All circles:', document.querySelectorAll('.circle-selection').length);
                console.log('All radios:', document.querySelectorAll('input[name="selectedCircle"]').length);
                console.log('Selected radio:', document.querySelector('input[name="selectedCircle"]:checked'));
                
                // Force highlight the first circle for testing
                const firstRadio = document.querySelector('input[name="selectedCircle"]');
                if (firstRadio) {
                    firstRadio.checked = true;
                    console.log('Selected first radio for testing');
                    highlightSelectedCircle();
                    console.log('Applied highlighting');
                } else {
                    console.log('No radios found!');
                }
            };
</script>

<x-slot:scripts>
    <!-- FUCKING WORKING CSS FOR CIRCLE SELECTION - DIRECT APPROACH -->
    <style>
        /* DEFAULT STATE FOR ALL CIRCLES */
        .circle-selection {
            border: 1px solid #d1d5db !important;
            background-color: white !important;
            transition: all 0.3s ease !important;
        }
        
        /* SELECTED STATE - MULTIPLE APPROACHES */
        .circle-selection.selected {
            border: 4px solid #3b82f6 !important;
            background-color: #eff6ff !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2) !important;
            transform: scale(1.02) !important;
        }
        
        .circle-selection:has(input[type="radio"]:checked) {
            border: 4px solid #3b82f6 !important;
            background-color: #eff6ff !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2) !important;
            transform: scale(1.02) !important;
        }
        
        /* BACKUP APPROACH */
        .circle-selection input[type="radio"]:checked + * {
            border: 4px solid #3b82f6 !important;
        }
        
        /* FORCE SELECTED STYLING */
        .circle-selected-force {
            border: 4px solid #3b82f6 !important;
            background-color: #eff6ff !important;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.2) !important;
            transform: scale(1.02) !important;
        }
    </style>
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/@fullcalendar/interaction@6.1.8/index.global.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js'></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</x-slot:scripts>

</x-layouts.teacher>