{{--
    Student Attendance Status Component
    Displays real-time attendance tracking status for students
--}}

@props([
    'sessionId'
])

<!-- Enhanced Attendance Status -->
<div class="attendance-status bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 shadow-sm" id="attendance-status">
    <div class="flex items-center gap-3 mb-3">
        <div class="attendance-indicator flex items-center gap-2">
            <span class="attendance-dot w-3 h-3 rounded-full bg-gray-400 transition-all duration-300"></span>
            <i class="attendance-icon ri-user-line text-lg text-gray-600"></i>
            <h3 class="text-sm font-semibold text-gray-900">حالة الحضور</h3>
        </div>
    </div>
    <div class="attendance-details">
        <div class="attendance-text text-sm text-gray-700 font-medium mb-1">جاري التحميل...</div>
        <div class="attendance-time text-xs text-gray-500">--</div>
    </div>

    <!-- Optional: Progress bar for attendance percentage -->
    <div class="mt-3 hidden" id="attendance-progress">
        <div class="flex justify-between items-center text-xs text-gray-600 mb-1">
            <span>نسبة الحضور</span>
            <span class="attendance-percentage">0%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: 0%" id="attendance-progress-bar"></div>
        </div>
    </div>
</div>
