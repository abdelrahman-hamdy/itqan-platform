@props([
    'student',
    'session',
    'showChat' => true,
    'size' => 'sm'
])

@php
    $report = $session->studentReports->where('student_id', $student->id)->first();
    $attendance = $session->attendances->where('student_id', $student->id)->first();
    $attendanceStatus = $report ? $report->attendance_status : ($attendance ? $attendance->attendance_status : 'unknown');
    $homework = $session->homework ? $session->homework->first() : null;
    
    // Collect all info items
    $infoItems = [];
    
    if($report && $report->new_memorization_degree !== null) {
        $infoItems[] = [
            'icon' => 'ri-book-line text-green-600',
            'label' => 'درجة الحفظ',
            'value' => $report->new_memorization_degree . '/10',
            'badge_class' => 'bg-green-100 text-green-800'
        ];
    }
    
    // Only show reservation if homework has reservation enabled
    if($report && $report->reservation_degree !== null && $homework && ($homework->has_review || $homework->has_comprehensive_review)) {
        $infoItems[] = [
            'icon' => 'ri-refresh-line text-blue-600',
            'label' => 'درجة المراجعة',
            'value' => $report->reservation_degree . '/10',
            'badge_class' => 'bg-blue-100 text-blue-800'
        ];
    }
    
    if($report && $report->actual_attendance_minutes !== null) {
        $infoItems[] = [
            'icon' => 'ri-time-line text-purple-600',
            'label' => 'مدة الحضور',
            'value' => $report->actual_attendance_minutes . ' دقيقة',
            'badge_class' => 'bg-purple-100 text-purple-800'
        ];
    }
    
    if($report && $report->connection_quality_score !== null) {
        $infoItems[] = [
            'icon' => 'ri-wifi-line text-orange-600',
            'label' => 'جودة الاتصال',
            'value' => $report->connection_quality_grade,
            'badge_class' => 'bg-orange-100 text-orange-800'
        ];
    }
    
    // Split items into two columns
    $leftColumn = [];
    $rightColumn = [];
    foreach($infoItems as $index => $item) {
        if($index % 2 === 0) {
            $leftColumn[] = $item;
        } else {
            $rightColumn[] = $item;
        }
    }
@endphp

<div class="border border-gray-200 rounded-lg p-4 transition-colors">
    <!-- Header with Avatar, Name, and Attendance Status -->
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
            <!-- Student Avatar -->
            <x-student-avatar :student="$student" :size="$size" />
            
            <div>
                <h4 class="font-semibold text-gray-900 {{ $size === 'md' ? 'text-lg' : '' }}">{{ $student->name }}</h4>
            </div>
        </div>
        
        <!-- Attendance Status (floated to absolute left/right in RTL) -->
        <div class="flex items-center">
            @if($attendanceStatus === 'present')
                <span class="inline-flex items-center px-3 py-1.5 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                    <i class="ri-check-line ml-1"></i>
                    حاضر @if($report && $report->attendance_percentage)({{ number_format($report->attendance_percentage, 0) }}%)@endif
                </span>
            @elseif($attendanceStatus === 'late')
                <span class="inline-flex items-center px-3 py-1.5 bg-yellow-100 text-yellow-800 rounded-full text-sm font-semibold">
                    <i class="ri-time-line ml-1"></i>
                    متأخر @if($report && $report->late_minutes)({{ $report->late_minutes }}د)@endif
                </span>
            @elseif($attendanceStatus === 'partial')
                <span class="inline-flex items-center px-3 py-1.5 bg-orange-100 text-orange-800 rounded-full text-sm font-semibold">
                    <i class="ri-pie-chart-line ml-1"></i>
                    جزئي @if($report && $report->attendance_percentage)({{ number_format($report->attendance_percentage, 0) }}%)@endif
                </span>
            @elseif($attendanceStatus === 'absent')
                <span class="inline-flex items-center px-3 py-1.5 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                    <i class="ri-close-line ml-1"></i>
                    غائب
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-600 rounded-full text-sm font-semibold">
                    <i class="ri-question-line ml-1"></i>
                    غير محدد
                </span>
            @endif       
        </div>
    </div>
    
    <!-- Student Report Data -->
    @if($report && !empty($infoItems))
        <div class="bg-white border border-gray-300 rounded-lg mb-3 p-3">
            <div class="grid grid-cols-2 gap-4">
                <!-- Left Column -->
                <div class="space-y-3">
                    @foreach($leftColumn as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="{{ $item['icon'] }} ml-2"></i>
                                <span class="text-gray-900 text-sm">{{ $item['label'] }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
                
                <!-- Right Column -->
                <div class="space-y-3">
                    @foreach($rightColumn as $item)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="{{ $item['icon'] }} ml-2"></i>
                                <span class="text-gray-900 text-sm">{{ $item['label'] }}</span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $item['badge_class'] }}">
                                {{ $item['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
            
            @if($report->notes)
                <div class="mt-3 pt-3 border-t border-gray-200">
                    <div class="flex items-start">
                        <i class="ri-sticky-note-line text-amber-600 ml-2 mt-0.5"></i>
                        <div>
                            <span class="text-gray-600 text-xs font-medium">الملاحظات:</span>
                            <p class="text-gray-800 text-sm mt-1">{{ $report->notes }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @elseif($report)
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-3">
            <div class="flex items-center text-gray-600 text-sm">
                <i class="ri-information-line ml-2"></i>
                <span>تم إنشاء التقرير بدون بيانات إضافية</span>
            </div>
        </div>
    @else
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-3">
            <div class="flex items-center text-amber-800 text-sm">
                <i class="ri-information-line ml-2"></i>
                <span>لم يتم تقييم الطالب بعد</span>
            </div>
        </div>
    @endif
    
    <!-- Action Buttons -->
    <div class="flex items-center justify-end gap-2">
        <button class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm" onclick="editStudentReport({{ $student->id }}, {{ $report?->id ?? 'null' }})">
            <i class="ri-edit-line ml-1"></i>
            {{ $report ? 'تعديل التقرير' : 'إنشاء تقرير' }}
        </button>
        
        @if($showChat)
        <button class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors shadow-sm" onclick="messageStudent({{ $student->id }})">
            <i class="ri-message-line ml-1"></i>
            رسالة
        </button>
        @endif
    </div>
</div>
