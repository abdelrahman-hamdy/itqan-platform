<x-layouts.teacher 
    :title="'تقرير تقدم الطالب ' . $student->name . ' في حلقة ' . $circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تقرير التقدم للطالب ' . $student->name . ' في الحلقة الجماعية: ' . $circle->name">

<!-- Custom Styles -->
<style>
.progress-gradient {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
}
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
.progress-ring {
    transform: rotate(-90deg);
}
.progress-ring circle {
    transition: stroke-dasharray 0.3s ease;
}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 p-6">
    <!-- Header Section -->
    <div class="progress-gradient rounded-2xl shadow-lg text-white p-8 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6 space-x-reverse">
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="text-white/80 hover:text-white transition-colors">
                    <i class="ri-arrow-right-line text-2xl"></i>
                </a>
                
                <!-- Student Profile -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <x-avatar
                        :user="$student"
                        size="xl"
                        userType="student"
                        :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
                    <div>
                        <h1 class="text-3xl font-bold mb-1">{{ $student->name }}</h1>
                        <div class="flex items-center space-x-4 space-x-reverse text-white/90">
                            <span class="flex items-center space-x-1 space-x-reverse">
                                <i class="ri-group-line"></i>
                                <span>حلقة {{ $circle->name }}</span>
                            </span>
                            <span class="flex items-center space-x-1 space-x-reverse">
                                <i class="ri-user-star-line"></i>
                                <span>{{ $circle->quranTeacher->user->name ?? 'المعلم' }}</span>
                            </span>
                        </div>
                        
                        <!-- Quick Progress Indicator -->
                        <div class="mt-3 flex items-center space-x-4 space-x-reverse">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <div class="w-24 h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full transition-all duration-500" 
                                         style="width: {{ number_format($stats['attendance_rate'], 1) }}%"></div>
                                </div>
                                <span class="text-sm font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                            </div>
                            <span class="text-sm text-white/80">معدل الحضور</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-3 space-x-reverse">
                <button onclick="window.print()" class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-xl hover:bg-white/30 transition-colors">
                    <i class="ri-printer-line ml-2"></i>
                    طباعة التقرير
                </button>
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="inline-flex items-center px-6 py-3 bg-white text-indigo-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="ri-group-line ml-2"></i>
                    عرض الحلقة
                </a>
            </div>
        </div>
    </div>

    <!-- Student Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Sessions -->
        <div class="bg-white rounded-2xl shadow-lg border border-blue-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600 mb-1">إجمالي الجلسات</p>
                    <p class="text-3xl font-bold text-blue-900 mb-2">{{ $stats['total_sessions'] }}</p>
                    <div class="flex items-center text-xs text-blue-600">
                        <i class="ri-calendar-line ml-1"></i>
                        <span>في هذه الحلقة</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-book-line text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Attended Sessions -->
        <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 mb-1">الجلسات المحضورة</p>
                    <p class="text-3xl font-bold text-green-900 mb-2">{{ $stats['attended_sessions'] }}</p>
                    <div class="flex items-center text-xs text-green-600">
                        <i class="ri-check-line ml-1"></i>
                        <span>{{ number_format($stats['attendance_rate'], 1) }}% معدل الحضور</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-checkbox-circle-line text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Pages Memorized -->
        <div class="bg-white rounded-2xl shadow-lg border border-purple-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600 mb-1">الصفحات المحفوظة</p>
                    <p class="text-3xl font-bold text-purple-900 mb-2">{{ $stats['total_pages_memorized'] }}</p>
                    <div class="flex items-center text-xs text-purple-600">
                        <i class="ri-book-open-line ml-1"></i>
                        <span>في هذه الحلقة</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-pages-line text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Average Performance -->
        <div class="bg-white rounded-2xl shadow-lg border border-orange-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-600 mb-1">متوسط الأداء</p>
                    <p class="text-3xl font-bold text-orange-900 mb-2">{{ number_format(($stats['avg_recitation_quality'] + $stats['avg_tajweed_accuracy']) / 2, 1) }}</p>
                    <div class="flex items-center text-xs text-orange-600">
                        <i class="ri-star-line ml-1"></i>
                        <span>من 10</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-trophy-line text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
        <!-- Performance Over Time -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">تطور الأداء</h3>
                    <p class="text-gray-600 text-sm">التلاوة والتجويد عبر الجلسات</p>
                </div>
                <i class="ri-line-chart-line text-2xl text-indigo-600"></i>
            </div>
            <div class="chart-container">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Attendance Pattern -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">نمط الحضور</h3>
                    <p class="text-gray-600 text-sm">آخر 10 جلسات</p>
                </div>
                <i class="ri-calendar-check-line text-2xl text-green-600"></i>
            </div>
            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Sessions History -->
        <div class="xl:col-span-2">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900">سجل الجلسات</h3>
                    <span class="text-sm bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-medium">
                        {{ $sessions->count() }} جلسة
                    </span>
                </div>
                
                <div class="space-y-4">
                    @forelse($sessions->take(10) as $session)
                        @php
                            $attendance = $session->attendances->where('student_id', $student->id)->first();
                        @endphp
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 hover:shadow-md transition-all duration-300">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4 space-x-reverse">
                                    <!-- Status Indicator -->
                                    <div class="flex flex-col items-center">
                                        @if($attendance && $attendance->attendance_status === 'attended')
                                            <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                            <span class="text-xs text-green-600 font-bold">حضر</span>
                                        @elseif($attendance && $attendance->attendance_status === 'late')
                                            <div class="w-4 h-4 bg-yellow-500 rounded-full mb-1"></div>
                                            <span class="text-xs text-yellow-600 font-bold">متأخر</span>
                                        @elseif($attendance && $attendance->attendance_status === 'absent')
                                            <div class="w-4 h-4 bg-red-500 rounded-full mb-1"></div>
                                            <span class="text-xs text-red-600 font-bold">غائب</span>
                                        @else
                                            <div class="w-4 h-4 bg-gray-400 rounded-full mb-1"></div>
                                            <span class="text-xs text-gray-500 font-bold">غير محدد</span>
                                        @endif
                                    </div>
                                    
                                    <!-- Session Details -->
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 space-x-reverse mb-2">
                                            <h4 class="font-bold text-gray-900 text-lg">{{ $session->title ?? 'جلسة قرآنية' }}</h4>
                                            @if($attendance && ($attendance->papers_memorized_today || $attendance->verses_memorized_today))
                                                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">
                                                    @if($attendance->papers_memorized_today)
                                                        +{{ $attendance->papers_memorized_today }} صفحة
                                                    @elseif($attendance->verses_memorized_today)
                                                        +{{ $attendance->verses_memorized_today }} آية
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600">
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-calendar-line"></i>
                                                <span>{{ $session->scheduled_at ? $session->scheduled_at->format('Y/m/d') : 'غير مجدولة' }}</span>
                                            </span>
                                            <span class="flex items-center space-x-1 space-x-reverse">
                                                <i class="ri-time-line"></i>
                                                <span>{{ $session->scheduled_at ? $session->scheduled_at->format('H:i') : '--:--' }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Performance Scores -->
                                @if($attendance && ($attendance->recitation_quality || $attendance->tajweed_accuracy))
                                    <div class="flex items-center space-x-2 space-x-reverse">
                                        @if($attendance->recitation_quality)
                                            <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-medium">
                                                تلاوة: {{ $attendance->recitation_quality }}/10
                                            </span>
                                        @endif
                                        @if($attendance->tajweed_accuracy)
                                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs font-medium">
                                                تجويد: {{ $attendance->tajweed_accuracy }}/10
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="ri-calendar-line text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500">لا توجد جلسات مسجلة للطالب في هذه الحلقة</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-8">
            <!-- Performance Summary -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">ملخص الأداء</h3>
                    <i class="ri-trophy-line text-2xl text-orange-600"></i>
                </div>
                
                <div class="space-y-4">
                    <!-- Recitation Quality -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">جودة التلاوة المتوسطة</span>
                            <span class="font-medium">{{ number_format($stats['avg_recitation_quality'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['avg_recitation_quality'] * 10 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Tajweed Accuracy -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">دقة التجويد المتوسطة</span>
                            <span class="font-medium">{{ number_format($stats['avg_tajweed_accuracy'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['avg_tajweed_accuracy'] * 10 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Attendance Rate -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">معدل الحضور</span>
                            <span class="font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Circle Information -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">معلومات الحلقة</h3>
                
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 flex items-center">
                            <i class="ri-group-line ml-1"></i>
                            اسم الحلقة
                        </span>
                        <span class="font-medium">{{ $circle->name }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 flex items-center">
                            <i class="ri-user-star-line ml-1"></i>
                            المعلم
                        </span>
                        <span class="font-medium">{{ $circle->quranTeacher->user->name ?? 'غير محدد' }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 flex items-center">
                            <i class="ri-team-line ml-1"></i>
                            عدد الطلاب
                        </span>
                        <span class="font-medium">{{ $circle->students->count() }} طالب</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                
                <div class="space-y-3">
                    <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                       class="block w-full text-center px-5 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-colors shadow-lg">
                        <i class="ri-group-line ml-2"></i>
                        عرض الحلقة
                    </a>
                    
                    <a href="{{ route('teacher.group-circles.progress', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                       class="block w-full text-center px-5 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-medium rounded-xl hover:from-green-700 hover:to-emerald-700 transition-colors shadow-lg">
                        <i class="ri-bar-chart-line ml-2"></i>
                        تقرير الحلقة الكامل
                    </a>
                    
                    <button class="w-full px-5 py-3 border-2 border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors">
                        <i class="ri-download-line ml-2"></i>
                        تصدير التقرير
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sample data for charts
    const studentSessions = [
        @foreach($sessions as $session)
            @php
                $attendance = $session->attendances->where('student_id', $student->id)->first();
            @endphp
            {
                date: '{{ $session->scheduled_at ? $session->scheduled_at->format('Y-m-d') : '' }}',
                recitation_quality: {{ $attendance ? ($attendance->recitation_quality ?? 0) : 0 }},
                tajweed_accuracy: {{ $attendance ? ($attendance->tajweed_accuracy ?? 0) : 0 }},
                attended: {{ $attendance ? ($attendance->attendance_status === 'attended' ? 1 : 0) : 0 }}
            }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ];

    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'line',
        data: {
            labels: studentSessions.filter(s => s.date && s.recitation_quality > 0).map(s => s.date).slice(-8),
            datasets: [{
                label: 'جودة التلاوة',
                data: studentSessions.filter(s => s.date && s.recitation_quality > 0).map(s => s.recitation_quality).slice(-8),
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'دقة التجويد',
                data: studentSessions.filter(s => s.date && s.tajweed_accuracy > 0).map(s => s.tajweed_accuracy).slice(-8),
                borderColor: 'rgb(99, 102, 241)',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10
                }
            }
        }
    });

    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'bar',
        data: {
            labels: studentSessions.filter(s => s.date).map(s => s.date).slice(-10),
            datasets: [{
                label: 'الحضور',
                data: studentSessions.filter(s => s.date).map(s => s.attended).slice(-10),
                backgroundColor: function(context) {
                    return context.parsed.y === 1 ? 'rgba(34, 197, 94, 0.8)' : 'rgba(239, 68, 68, 0.8)';
                },
                borderColor: function(context) {
                    return context.parsed.y === 1 ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)';
                },
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 1,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            return value === 1 ? 'حضر' : 'غاب';
                        }
                    }
                }
            }
        }
    });
});
</script>

</x-layouts.teacher>
