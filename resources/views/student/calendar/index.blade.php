@php
    $isParent = ($layout ?? 'student') === 'parent';
    $sidebarComponent = $isParent ? 'sidebar.parent-sidebar' : 'sidebar.student-sidebar';
    $eventsRoute = $isParent
        ? route('parent.calendar.events', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy'])
        : route('student.calendar.events', ['subdomain' => $user->academy->subdomain ?? 'itqan-academy']);
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isParent ? 'تقويم جلسات الأبناء' : 'التقويم والجلسات' }} - {{ config('app.name') }}</title>

    <x-app-head />
    <x-global-styles />

    <style>
        /* Calendar Grid Styles */
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: #e5e7eb;
            border: 1px solid #e5e7eb;
        }

        .calendar-header-cell {
            background-color: #f9fafb;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.875rem;
            color: #374151;
        }

        .calendar-day {
            background-color: white;
            min-height: 120px;
            padding: 0.5rem;
            position: relative;
            transition: all 0.2s;
        }

        .calendar-day:hover {
            background-color: #f9fafb;
        }

        .calendar-day.other-month {
            background-color: #fafafa;
            color: #9ca3af;
        }

        .calendar-day.today {
            background-color: #eff6ff;
        }

        .day-number {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .other-month .day-number {
            color: #9ca3af;
        }

        .today .day-number {
            color: #3b82f6;
            background-color: #3b82f6;
            color: white;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .calendar-event {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .calendar-event:hover {
            transform: translateX(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .calendar-event.status-scheduled {
            background-color: #dbeafe;
            border-right: 3px solid #3b82f6;
            color: #1e40af;
        }

        .calendar-event.status-ongoing {
            background-color: #fef3c7;
            border-right: 3px solid #f59e0b;
            color: #92400e;
        }

        .calendar-event.status-completed {
            background-color: #d1fae5;
            border-right: 3px solid #10b981;
            color: #065f46;
        }

        .calendar-event.status-cancelled {
            background-color: #fee2e2;
            border-right: 3px solid #ef4444;
            color: #991b1b;
        }

        /* Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            animation: fadeIn 0.2s;
        }

        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            max-width: 32rem;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Navigation Buttons */
        .nav-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background-color: white;
            border: 1px solid #e5e7eb;
            color: #374151;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-button:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
        }

        /* Stats Cards */
        .stat-card {
            background-color: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Top Navigation -->
    <x-navigation.app-navigation :role="$isParent ? 'parent' : 'student'" />

    <!-- Sidebar -->
    <x-dynamic-component :component="$sidebarComponent" />

    <!-- Main Content -->
    <main id="main-content" class="pt-20 transition-all duration-300 mr-0 md:mr-80">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-8">

            <!-- Page Header -->
            <div class="mb-6 md:mb-8">
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">{{ $isParent ? 'تقويم جلسات الأبناء' : 'التقويم والجلسات' }}</h1>
                <p class="text-sm md:text-base text-gray-600">{{ $isParent ? 'عرض جميع جلسات الأبناء وحلقاتهم ودوراتهم التعليمية' : 'عرض جميع جلساتك وحلقاتك ودوراتك التعليمية' }}</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6 md:mb-8">
                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs md:text-sm text-gray-600 mb-1">إجمالي الجلسات</p>
                            <p id="stat-total" class="text-xl md:text-2xl font-bold text-purple-600">0</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="ri-calendar-line text-xl md:text-2xl text-purple-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs md:text-sm text-gray-600 mb-1">جلسات مجدولة</p>
                            <p id="stat-scheduled" class="text-xl md:text-2xl font-bold text-blue-600">0</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="ri-calendar-check-line text-xl md:text-2xl text-blue-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs md:text-sm text-gray-600 mb-1">جلسات مكتملة</p>
                            <p id="stat-completed" class="text-xl md:text-2xl font-bold text-green-600">0</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="ri-checkbox-circle-line text-xl md:text-2xl text-green-600"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="flex items-center justify-between">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs md:text-sm text-gray-600 mb-1">جلسات ملغاة</p>
                            <p id="stat-cancelled" class="text-xl md:text-2xl font-bold text-red-600">0</p>
                        </div>
                        <div class="w-10 h-10 md:w-12 md:h-12 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="ri-close-circle-line text-xl md:text-2xl text-red-600"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Calendar Navigation -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4 md:mb-6">
                    <div class="flex items-center gap-2 md:gap-3 order-2 md:order-1">
                        <button onclick="changeMonth(-1)" class="nav-button min-h-[44px]">
                            <i class="ri-arrow-right-s-line text-xl"></i>
                            <span class="hidden sm:inline">الشهر السابق</span>
                        </button>
                        <button onclick="goToToday()" class="nav-button min-h-[44px] bg-blue-50 text-blue-600 border-blue-200 hover:bg-blue-100">
                            <i class="ri-calendar-check-line"></i>
                            <span>اليوم</span>
                        </button>
                        <button onclick="changeMonth(1)" class="nav-button min-h-[44px]">
                            <span class="hidden sm:inline">الشهر التالي</span>
                            <i class="ri-arrow-left-s-line text-xl"></i>
                        </button>
                    </div>
                    <div class="text-center order-1 md:order-2">
                        <h2 id="current-month" class="text-xl md:text-2xl font-bold text-gray-900"></h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Legend -->
                        <div class="hidden lg:flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-blue-500"></div>
                                <span class="text-gray-600">مجدولة</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-yellow-500"></div>
                                <span class="text-gray-600">جارية</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-green-500"></div>
                                <span class="text-gray-600">مكتملة</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-red-500"></div>
                                <span class="text-gray-600">ملغاة</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div id="calendar-grid" class="calendar-grid rounded-lg overflow-hidden">
                    <!-- Header Row -->
                    <div class="calendar-header-cell">السبت</div>
                    <div class="calendar-header-cell">الأحد</div>
                    <div class="calendar-header-cell">الإثنين</div>
                    <div class="calendar-header-cell">الثلاثاء</div>
                    <div class="calendar-header-cell">الأربعاء</div>
                    <div class="calendar-header-cell">الخميس</div>
                    <div class="calendar-header-cell">الجمعة</div>
                    <!-- Days will be inserted here by JavaScript -->
                </div>
            </div>

        </div>
    </main>

    <!-- Event Details Modal -->
    <div id="event-modal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <!-- Modal Header with Gradient -->
            <div id="modal-header" class="relative bg-gradient-to-br from-blue-500 to-blue-600 p-6 rounded-t-xl">
                <button onclick="closeModal()" class="absolute top-4 left-4 text-white/80 hover:text-white transition-colors p-2 hover:bg-white/10 rounded-lg">
                    <i class="ri-close-line text-2xl"></i>
                </button>
                <div class="pr-12">
                    <!-- Status Badge -->
                    <div id="modal-status" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm border border-white/30 text-white mb-3"></div>
                    <!-- Title -->
                    <h3 id="modal-title" class="text-2xl font-bold text-white leading-tight"></h3>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-6">
                <!-- Time & Duration Grid -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="ri-calendar-event-line text-blue-600 text-lg"></i>
                            <span class="text-xs font-semibold text-blue-600 uppercase">التاريخ</span>
                        </div>
                        <p id="modal-date" class="text-sm font-bold text-gray-900"></p>
                    </div>
                    <div class="bg-green-50 border border-green-100 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="ri-time-line text-green-600 text-lg"></i>
                            <span class="text-xs font-semibold text-green-600 uppercase">الوقت</span>
                        </div>
                        <p id="modal-time" class="text-sm font-bold text-gray-900"></p>
                    </div>
                </div>

                <!-- Duration -->
                <div class="bg-purple-50 border border-purple-100 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i class="ri-hourglass-line text-purple-600 text-lg"></i>
                            <span class="text-xs font-semibold text-purple-600 uppercase">مدة الجلسة</span>
                        </div>
                        <p id="modal-duration" class="text-lg font-bold text-gray-900"></p>
                    </div>
                </div>

                <!-- Teacher Info -->
                <div id="modal-teacher-container" class="mb-6 hidden">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">المعلم</h4>
                    <div id="modal-teacher" class="flex items-center gap-3 p-4 bg-gradient-to-r from-orange-50 to-orange-100/50 border border-orange-200 rounded-lg"></div>
                </div>

                <!-- Description -->
                <div id="modal-description-container" class="mb-6 hidden">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">وصف الجلسة</h4>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p id="modal-description" class="text-sm text-gray-700 leading-relaxed"></p>
                    </div>
                </div>

                <!-- Participants -->
                <div id="modal-participants-container" class="mb-6 hidden">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase mb-3">المشاركون في الجلسة</h4>
                    <div id="modal-participants" class="space-y-2"></div>
                </div>

                <!-- Action Button -->
                <a id="modal-view-button" href="#" class="block w-full text-center px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="ri-external-link-line text-lg ml-2"></i>
                    <span>عرض صفحة الجلسة</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Calendar data
        let currentDate = new Date();
        let eventsData = @json($events);

        // Arabic month names
        const arabicMonths = [
            'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو',
            'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'
        ];

        // Initialize calendar
        function initCalendar() {
            // Ensure stats are updated first
            updateStats();
            // Then render the calendar
            renderCalendar();
        }

        // Render calendar
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            // Update header
            document.getElementById('current-month').textContent = `${arabicMonths[month]} ${year}`;

            // Get first day of month and total days
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const totalDays = lastDay.getDate();

            // Get day of week (0 = Sunday, 6 = Saturday)
            // Adjust for Saturday as first day (6 = Saturday)
            let startDay = firstDay.getDay();
            // Convert to Saturday-based week: Sat=0, Sun=1, Mon=2, etc.
            startDay = (startDay + 1) % 7;

            // Get previous month's last days
            const prevMonthLastDay = new Date(year, month, 0).getDate();
            const prevMonthDays = startDay;

            // Calculate total cells needed
            const totalCells = Math.ceil((startDay + totalDays) / 7) * 7;

            // Build calendar days HTML
            let html = '';

            // Previous month days
            for (let i = prevMonthDays - 1; i >= 0; i--) {
                const day = prevMonthLastDay - i;
                html += `<div class="calendar-day other-month">
                    <div class="day-number">${day}</div>
                </div>`;
            }

            // Current month days
            const today = new Date();
            for (let day = 1; day <= totalDays; day++) {
                const isToday = (
                    day === today.getDate() &&
                    month === today.getMonth() &&
                    year === today.getFullYear()
                );

                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const dayEvents = getEventsForDate(dateStr);

                html += `<div class="calendar-day ${isToday ? 'today' : ''}">
                    <div class="day-number">${day}</div>
                    ${renderDayEvents(dayEvents)}
                </div>`;
            }

            // Next month days
            const remainingCells = totalCells - (prevMonthDays + totalDays);
            for (let day = 1; day <= remainingCells; day++) {
                html += `<div class="calendar-day other-month">
                    <div class="day-number">${day}</div>
                </div>`;
            }

            // Get the calendar grid and remove all day cells (keep headers)
            const calendarGrid = document.getElementById('calendar-grid');
            const children = Array.from(calendarGrid.children);
            // Remove all children except the first 7 (header cells)
            children.slice(7).forEach(child => child.remove());

            // Append new days HTML
            calendarGrid.insertAdjacentHTML('beforeend', html);

            // Update stats
            updateStats();
        }

        // Update stats based on current month's events
        function updateStats() {
            const SessionStatus = {
                SCHEDULED: 'scheduled',
                COMPLETED: 'completed',
                CANCELLED: 'cancelled'
            };

            const stats = {
                total: eventsData.length,
                scheduled: eventsData.filter(e => e.status === SessionStatus.SCHEDULED).length,
                completed: eventsData.filter(e => e.status === SessionStatus.COMPLETED).length,
                cancelled: eventsData.filter(e => e.status === SessionStatus.CANCELLED).length
            };

            document.getElementById('stat-total').textContent = stats.total;
            document.getElementById('stat-scheduled').textContent = stats.scheduled;
            document.getElementById('stat-completed').textContent = stats.completed;
            document.getElementById('stat-cancelled').textContent = stats.cancelled;
        }

        // Get events for specific date
        function getEventsForDate(dateStr) {
            return eventsData.filter(event => {
                const eventDate = new Date(event.start_time);
                const eventDateStr = `${eventDate.getFullYear()}-${String(eventDate.getMonth() + 1).padStart(2, '0')}-${String(eventDate.getDate()).padStart(2, '0')}`;
                return eventDateStr === dateStr;
            });
        }

        // Render events for a day
        function renderDayEvents(events) {
            if (events.length === 0) return '';

            let html = '<div class="space-y-1">';

            // Show maximum 3 events
            const displayEvents = events.slice(0, 3);

            displayEvents.forEach(event => {
                const time = new Date(event.start_time).toLocaleTimeString('ar-SA', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: true
                });

                const statusClass = `status-${event.status}`;
                const eventTitle = truncate(event.title, 30);

                html += `<div class="calendar-event ${statusClass}" onclick='showEventModal(${JSON.stringify(event)})'>
                    <i class="ri-time-line text-xs"></i>
                    <span class="flex-1 truncate">${eventTitle}</span>
                </div>`;
            });

            // Show "more" indicator if there are additional events
            if (events.length > 3) {
                html += `<div class="text-xs text-gray-500 pr-2">+${events.length - 3} جلسات أخرى</div>`;
            }

            html += '</div>';
            return html;
        }

        // Show event modal
        function showEventModal(event) {
            const modal = document.getElementById('event-modal');
            const modalHeader = document.getElementById('modal-header');

            // Set title
            document.getElementById('modal-title').textContent = event.title;

            // Set session-type-specific gradient colors for modal header
            const gradientColors = {
                'circle_session': 'bg-gradient-to-br from-green-500 to-green-600',
                'quran_session': 'bg-gradient-to-br from-yellow-600 to-yellow-700',
                'course_session': 'bg-gradient-to-br from-blue-500 to-blue-600',
                'academic_session': 'bg-gradient-to-br from-violet-500 to-violet-600'
            };

            // Remove all gradient classes and add the specific one
            modalHeader.className = `relative ${gradientColors[event.source] || 'bg-gradient-to-br from-blue-500 to-blue-600'} p-6 rounded-t-xl`;

            // Set status badge (adjusted for white background on gradient)
            const statusBadge = document.getElementById('modal-status');
            const statusInfo = getStatusInfo(event.status);
            statusBadge.className = `inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm border border-white/30 text-white mb-3`;
            statusBadge.innerHTML = `<i class="${statusInfo.icon}"></i> <span>${statusInfo.label}</span>`;

            // Set date and time separately
            const startDate = new Date(event.start_time);
            const endDate = new Date(event.end_time);

            const dateStr = startDate.toLocaleDateString('ar-SA', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const timeStr = `${startDate.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', hour12: true })} - ${endDate.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', hour12: true })}`;

            document.getElementById('modal-date').textContent = dateStr;
            document.getElementById('modal-time').textContent = timeStr;

            // Set duration
            document.getElementById('modal-duration').textContent = `${event.duration_minutes} دقيقة`;

            // Set teacher info with avatar
            if (event.teacher_data && event.teacher_data.name) {
                document.getElementById('modal-teacher-container').classList.remove('hidden');

                // Generate avatar URL using UI Avatars API
                const teacherName = encodeURIComponent(event.teacher_data.name);
                const avatarBgColor = event.teacher_data.gender === 'female' ? 'ec4899' : '3b82f6';
                const avatarUrl = `https://ui-avatars.com/api/?name=${teacherName}&background=${avatarBgColor}&color=fff&size=128&bold=true&format=svg`;

                document.getElementById('modal-teacher').innerHTML = `
                    <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 shadow-md ring-2 ring-white">
                        <img src="${avatarUrl}" alt="${event.teacher_data.name}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <p class="text-base font-bold text-gray-900">${event.teacher_data.name}</p>
                        <p class="text-xs text-gray-500">المعلم المسؤول</p>
                    </div>
                `;
            } else if (event.teacher_name) {
                // Fallback to teacher name only
                document.getElementById('modal-teacher-container').classList.remove('hidden');
                const teacherName = encodeURIComponent(event.teacher_name);
                const avatarUrl = `https://ui-avatars.com/api/?name=${teacherName}&background=3b82f6&color=fff&size=128&bold=true&format=svg`;

                document.getElementById('modal-teacher').innerHTML = `
                    <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 shadow-md ring-2 ring-white">
                        <img src="${avatarUrl}" alt="${event.teacher_name}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <p class="text-base font-bold text-gray-900">${event.teacher_name}</p>
                        <p class="text-xs text-gray-500">المعلم المسؤول</p>
                    </div>
                `;
            } else {
                document.getElementById('modal-teacher-container').classList.add('hidden');
            }

            // Set description
            if (event.description) {
                document.getElementById('modal-description-container').classList.remove('hidden');
                document.getElementById('modal-description').textContent = event.description;
            } else {
                document.getElementById('modal-description-container').classList.add('hidden');
            }

            // Set participants (excluding teacher if already shown)
            if (event.participants && event.participants.length > 0) {
                const students = event.participants.filter(p => p.role !== 'teacher');
                if (students.length > 0) {
                    document.getElementById('modal-participants-container').classList.remove('hidden');
                    const participantsHtml = students.map(p =>
                        `<div class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                            <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                                <span class="text-white font-bold text-sm">${p.name.charAt(0)}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">${p.name}</p>
                                <p class="text-xs text-gray-500">طالب</p>
                            </div>
                        </div>`
                    ).join('');
                    document.getElementById('modal-participants').innerHTML = participantsHtml;
                } else {
                    document.getElementById('modal-participants-container').classList.add('hidden');
                }
            } else {
                document.getElementById('modal-participants-container').classList.add('hidden');
            }

            // Set view button URL
            document.getElementById('modal-view-button').href = event.url || '#';

            // Show modal
            modal.classList.add('active');
        }

        // Get status text color for white background badges
        function getStatusTextColor(status) {
            const SessionStatus = {
                SCHEDULED: 'scheduled',
                ONGOING: 'ongoing',
                COMPLETED: 'completed',
                CANCELLED: 'cancelled'
            };

            const colorMap = {
                [SessionStatus.SCHEDULED]: 'text-blue-700',
                [SessionStatus.ONGOING]: 'text-yellow-700',
                [SessionStatus.COMPLETED]: 'text-green-700',
                [SessionStatus.CANCELLED]: 'text-red-700'
            };
            return colorMap[status] || 'text-gray-700';
        }

        // Close modal
        function closeModal(event) {
            if (!event || event.target.id === 'event-modal') {
                document.getElementById('event-modal').classList.remove('active');
            }
        }

        // Change month
        function changeMonth(delta) {
            currentDate.setMonth(currentDate.getMonth() + delta);

            // Fetch new events for the month
            fetchEventsForMonth();
        }

        // Go to today
        function goToToday() {
            currentDate = new Date();
            fetchEventsForMonth();
        }

        // Fetch events for current month
        function fetchEventsForMonth() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();

            // Get first and last day of month
            const firstDayOfMonth = new Date(year, month, 1);
            const lastDayOfMonth = new Date(year, month + 1, 0);

            // Extend to include full weeks (match PHP logic)
            const startDate = new Date(firstDayOfMonth);
            startDate.setDate(startDate.getDate() - ((startDate.getDay() + 1) % 7)); // Start of week (Saturday)

            const endDate = new Date(lastDayOfMonth);
            const daysToAdd = (6 - ((endDate.getDay() + 1) % 7)) % 7;
            endDate.setDate(endDate.getDate() + daysToAdd); // End of week (Friday)

            // Format dates for API
            const startStr = startDate.toISOString().split('T')[0];
            const endStr = endDate.toISOString().split('T')[0];

            // Fetch events via AJAX
            fetch(`{{ $eventsRoute }}?start=${startStr}&end=${endStr}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                eventsData = data;
                renderCalendar();
            })
            .catch(error => {
                console.error('Error fetching events:', error);
                renderCalendar(); // Render anyway with existing data
            });
        }

        // Helper functions
        function truncate(str, length) {
            return str.length > length ? str.substring(0, length) + '...' : str;
        }

        function getEventTypeLabel(source) {
            const labels = {
                'quran_session': 'جلسة قرآن فردية',
                'circle_session': 'حلقة قرآن جماعية',
                'course_session': 'جلسة دورة تفاعلية',
                'academic_session': 'درس أكاديمي'
            };
            return labels[source] || 'جلسة';
        }

        function getStatusInfo(status) {
            const SessionStatus = {
                SCHEDULED: 'scheduled',
                ONGOING: 'ongoing',
                COMPLETED: 'completed',
                CANCELLED: 'cancelled'
            };

            const statusMap = {
                [SessionStatus.SCHEDULED]: { label: 'مجدولة', icon: 'ri-calendar-check-line', class: 'bg-blue-100 text-blue-700' },
                [SessionStatus.ONGOING]: { label: 'جارية الآن', icon: 'ri-live-line', class: 'bg-yellow-100 text-yellow-700' },
                [SessionStatus.COMPLETED]: { label: 'مكتملة', icon: 'ri-checkbox-circle-line', class: 'bg-green-100 text-green-700' },
                [SessionStatus.CANCELLED]: { label: 'ملغاة', icon: 'ri-close-circle-line', class: 'bg-red-100 text-red-700' }
            };
            return statusMap[status] || statusMap[SessionStatus.SCHEDULED];
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initCalendar);

        // Close modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>
