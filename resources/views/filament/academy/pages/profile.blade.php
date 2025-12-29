<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Profile Information -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">معلومات الطالب</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-sm font-medium text-gray-500">الاسم</p>
                        <p class="text-lg text-gray-900">{{ $user->first_name }} {{ $user->last_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">البريد الإلكتروني</p>
                        <p class="text-lg text-gray-900">{{ $user->email }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">رقم الهاتف</p>
                        <p class="text-lg text-gray-900">{{ $user->phone ?? 'غير محدد' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">نوع المستخدم</p>
                        <p class="text-lg text-gray-900">
                            @if($isStudent)
                                طالب
                            @elseif($isTeacher)
                                معلم
                            @else
                                مستخدم
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Stats -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            @if($isStudent)
                <!-- Student-specific stats -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">دروسي اليوم</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['today_sessions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">الدروس المكتملة</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['completed_sessions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">الدروس القادمة</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['upcoming_sessions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">التقدم العام</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['progress_percentage'] ?? 0 }}%</p>
                        </div>
                    </div>
                </div>
            @else
                <!-- Teacher stats (fallback) -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">جلسات اليوم</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['today_sessions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">إجمالي الجلسات</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_sessions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">الجلسات القادمة</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['upcoming_sessions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">إجمالي الطلاب</p>
                            <p class="text-2xl font-semibold text-gray-900">{{ $stats['total_students'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Calendar Widget -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">
                        @if($isStudent)
                            تقويم الدروس
                        @else
                            تقويم الجلسات
                        @endif
                    </h3>
                    
                                         @if($isStudent)
                         <div class="flex space-x-2 space-x-reverse">
                             <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                 تحديث التقويم
                             </button>
                             <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
                                 طلب جلسة خاصة
                             </button>
                         </div>
                     @else
                         <div class="flex space-x-2 space-x-reverse">
                             <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition-colors">
                                 تحديث التقويم
                             </button>
                             <a href="#" 
                                class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition-colors">
                                 إدارة الجلسات
                             </a>
                         </div>
                     @endif
                </div>

                                 <!-- Calendar Container -->
                 <div id="academy-profile-calendar" class="min-h-96">
                     <!-- Enhanced calendar view with session details -->
                     @if(isset($events) && count($events) > 0)
                         <!-- Calendar Grid View -->
                         <div class="bg-white border rounded-lg">
                             <!-- Calendar Header -->
                             <div class="flex items-center justify-between p-4 border-b">
                                 <div class="flex items-center space-x-2 space-x-reverse">
                                     <button id="prevMonth" class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100">
                                         <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                         </svg>
                                     </button>
                                     <h4 id="currentMonth" class="text-lg font-medium text-gray-900">{{ now()->format('F Y') }}</h4>
                                     <button id="nextMonth" class="p-2 text-gray-500 hover:text-gray-700 rounded-lg hover:bg-gray-100">
                                         <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                         </svg>
                                     </button>
                                 </div>
                                 <div class="flex space-x-2 space-x-reverse">
                                     <button class="px-3 py-1 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                         عرض اليوم
                                     </button>
                                     <button class="px-3 py-1 text-sm bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                         عرض الأسبوع
                                     </button>
                                 </div>
                             </div>
                             
                             <!-- Calendar Days -->
                             <div class="grid grid-cols-7 gap-0 border-b text-center text-sm font-medium text-gray-500">
                                 <div class="p-2 border-l">الأحد</div>
                                 <div class="p-2 border-l">الاثنين</div>
                                 <div class="p-2 border-l">الثلاثاء</div>
                                 <div class="p-2 border-l">الأربعاء</div>
                                 <div class="p-2 border-l">الخميس</div>
                                 <div class="p-2 border-l">الجمعة</div>
                                 <div class="p-2">السبت</div>
                             </div>
                             
                             <!-- Calendar Grid -->
                             <div id="calendarGrid" class="grid grid-cols-7 gap-0">
                                 <!-- Calendar days will be populated by JavaScript -->
                             </div>
                         </div>
                         
                         <!-- Upcoming Sessions List -->
                         <div class="mt-4 space-y-3">
                             @foreach(array_slice($events, 0, 5) as $event)
                                 <div class="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                     <div class="flex items-center justify-between">
                                         <div class="flex-1">
                                             <h5 class="text-sm font-medium text-gray-900">{{ $event['title'] }}</h5>
                                             <p class="text-xs text-gray-500 mt-1">
                                                 {{ $event['start_time']->format('Y-m-d H:i') }} - {{ $event['end_time']->format('H:i') }}
                                             </p>
                                             @if(isset($event['teacher_name']))
                                                 <p class="text-xs text-gray-600 mt-1">المعلم: {{ $event['teacher_name'] }}</p>
                                             @endif
                                         </div>
                                         <div class="flex items-center space-x-2 space-x-reverse">
                                             @if(isset($event['meeting_link']))
                                                 <a href="{{ $event['meeting_link'] }}" target="_blank" 
                                                    class="px-3 py-1 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700">
                                                     انضم
                                                 </a>
                                             @endif
                                             <span class="px-2 py-1 text-xs rounded-full {{ $event['status'] === 'scheduled' ? 'bg-blue-100 text-blue-800' : ($event['status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                                                 @if($event['status'] === 'scheduled')
                                                     مجدولة
                                                 @elseif($event['status'] === 'completed')
                                                     مكتملة
                                                 @elseif($event['status'] === 'ongoing')
                                                     جارية
                                                 @else
                                                     {{ $event['status'] }}
                                                 @endif
                                             </span>
                                         </div>
                                     </div>
                                 </div>
                             @endforeach
                         </div>
                     @else
                         <!-- Empty state -->
                         <div class="flex items-center justify-center h-96 bg-gray-50 rounded-lg">
                             <div class="text-center">
                                 <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                 </svg>
                                 <h3 class="mt-2 text-sm font-medium text-gray-900">
                                     @if($isStudent)
                                         لا توجد دروس مجدولة
                                     @else
                                         لا توجد جلسات مجدولة
                                     @endif
                                 </h3>
                                 <p class="mt-1 text-sm text-gray-500">
                                     @if($isStudent)
                                         ستظهر دروسك ومواعيدك هنا عند جدولتها
                                     @else
                                         ستظهر جلساتك وأجتماعاتك هنا عند جدولتها
                                     @endif
                                 </p>
                                 @if($isStudent)
                                     <button class="mt-4 px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">
                                         طلب جلسة تجريبية
                                     </button>
                                 @endif
                             </div>
                         </div>
                     @endif
                 </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">إجراءات سريعة</h4>
                <div class="space-y-3">
                    @if($isStudent)
                        <button onclick="updateCalendar()" 
                                class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            تحديث التقويم
                        </button>
                        <a href="#" 
                           class="block bg-green-600 text-white text-center py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                            طلب جلسة تجريبية
                        </a>
                        <a href="#" 
                           class="block bg-purple-600 text-white text-center py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                            تقريري الشهري
                        </a>
                    @else
                        <button onclick="updateCalendar()" 
                                class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            تحديث التقويم
                        </button>
                        <a href="#" 
                           class="block bg-green-600 text-white text-center py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                            إدارة الجلسات
                        </a>
                        <a href="#" 
                           class="block bg-purple-600 text-white text-center py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                            إدارة الاشتراكات
                        </a>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">
                    @if($isStudent)
                        الدروس القادمة
                    @else
                        الجلسات القادمة
                    @endif
                </h4>
                <div class="space-y-3">
                    @if(isset($events) && count($events) > 0)
                        @foreach(array_slice($events, 0, 3) as $event)
                            <div class="border-l-4 border-blue-500 pl-3">
                                <p class="text-sm font-medium text-gray-900">{{ $event['title'] }}</p>
                                <p class="text-xs text-gray-500">{{ $event['start_time']->format('Y-m-d H:i') }}</p>
                            </div>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-500">
                            @if($isStudent)
                                لا توجد دروس قادمة
                            @else
                                لا توجد جلسات قادمة
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h4 class="text-lg font-medium text-gray-900 mb-4">إعدادات الحساب</h4>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="mr-2 text-sm text-gray-700">إشعارات الجلسات</span>
                    </label>
                    @if($isStudent)
                        <label class="flex items-center">
                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="mr-2 text-sm text-gray-700">تذكير قبل الدرس</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="mr-2 text-sm text-gray-700">إشعار ولي الأمر</span>
                        </label>
                    @else
                        <label class="flex items-center">
                            <input type="checkbox" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="mr-2 text-sm text-gray-700">مزامنة Google Calendar</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="mr-2 text-sm text-gray-700">تسجيل الجلسات تلقائياً</span>
                        </label>
                    @endif
                </div>
            </div>
        </div>
    </div>

         @push('scripts')
     <script>
         // Calendar initialization and event handling
         document.addEventListener('DOMContentLoaded', function() {
             
             // Initialize calendar data
             const events = @json($events ?? []);
             let currentDate = new Date();
             
             // Calendar navigation
             function updateCalendar() {
                 const monthNames = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                                   'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                 
                 const currentMonthElement = document.getElementById('currentMonth');
                 if (currentMonthElement) {
                     currentMonthElement.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
                 }
                 
                 // Update calendar grid
                 renderCalendarGrid();
             }
             
             function renderCalendarGrid() {
                 const calendarGrid = document.getElementById('calendarGrid');
                 if (!calendarGrid) return;
                 
                 calendarGrid.innerHTML = '';
                 
                 const year = currentDate.getFullYear();
                 const month = currentDate.getMonth();
                 
                 // Get first day of month and calculate starting point
                 const firstDay = new Date(year, month, 1);
                 const lastDay = new Date(year, month + 1, 0);
                 const startDate = new Date(firstDay);
                 startDate.setDate(startDate.getDate() - firstDay.getDay());
                 
                 // Generate 6 weeks (42 days)
                 for (let i = 0; i < 42; i++) {
                     const date = new Date(startDate);
                     date.setDate(startDate.getDate() + i);
                     
                     const dayElement = document.createElement('div');
                     dayElement.className = 'min-h-[100px] p-2 border-b border-l text-sm relative';
                     
                     // Style current month vs other months
                     if (date.getMonth() === month) {
                         dayElement.classList.add('bg-white');
                     } else {
                         dayElement.classList.add('bg-gray-50', 'text-gray-400');
                     }
                     
                     // Highlight today
                     const today = new Date();
                     if (date.toDateString() === today.toDateString()) {
                         dayElement.classList.add('bg-blue-50', 'border-blue-300');
                     }
                     
                     // Add day number
                     const dayNumber = document.createElement('div');
                     dayNumber.className = 'font-medium mb-1';
                     dayNumber.textContent = date.getDate();
                     dayElement.appendChild(dayNumber);
                     
                     // Add events for this day
                     const dayEvents = events.filter(event => {
                         const eventDate = new Date(event.start_time);
                         return eventDate.toDateString() === date.toDateString();
                     });
                     
                     dayEvents.forEach(event => {
                         const eventElement = document.createElement('div');
                         eventElement.className = 'text-xs p-1 mb-1 rounded truncate cursor-pointer';
                         
                         // Color code by event type/status
                         if (event.status === 'completed') {
                             eventElement.classList.add('bg-green-100', 'text-green-800');
                         } else if (event.status === 'ongoing') {
                             eventElement.classList.add('bg-yellow-100', 'text-yellow-800');
                         } else {
                             eventElement.classList.add('bg-blue-100', 'text-blue-800');
                         }
                         
                         eventElement.textContent = event.title;
                         eventElement.title = `${event.title}\n${new Date(event.start_time).toLocaleTimeString('ar-SA', {hour: '2-digit', minute: '2-digit'})}`;
                         
                         // Add click handler for event details
                         eventElement.addEventListener('click', function() {
                             showEventDetails(event);
                         });
                         
                         dayElement.appendChild(eventElement);
                     });
                     
                     calendarGrid.appendChild(dayElement);
                 }
             }
             
             function showEventDetails(event) {
                 // Simple alert for now - could be enhanced with a modal
                 const startTime = new Date(event.start_time).toLocaleString('ar-SA');
                 let details = `العنوان: ${event.title}\nالوقت: ${startTime}`;
                 
                 if (event.teacher_name) {
                     details += `\nالمعلم: ${event.teacher_name}`;
                 }
                 
                 if (event.meeting_link) {
                     details += `\nرابط الاجتماع متوفر`;
                     if (confirm(details + '\n\nهل تريد فتح رابط الاجتماع؟')) {
                         window.open(event.meeting_link, '_blank');
                     }
                 } else {
                     window.toast?.info(details);
                 }
             }
             
             // Event listeners for navigation
             const prevButton = document.getElementById('prevMonth');
             const nextButton = document.getElementById('nextMonth');
             
             if (prevButton) {
                 prevButton.addEventListener('click', function() {
                     currentDate.setMonth(currentDate.getMonth() - 1);
                     updateCalendar();
                 });
             }
             
             if (nextButton) {
                 nextButton.addEventListener('click', function() {
                     currentDate.setMonth(currentDate.getMonth() + 1);
                     updateCalendar();
                 });
             }
             
             // Initialize calendar
             updateCalendar();
         });
     </script>
     @endpush
</x-filament-panels::page> 