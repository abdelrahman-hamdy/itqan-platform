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

<div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 p-4 md:p-6">
    <!-- Header Section -->
    <div class="progress-gradient rounded-xl md:rounded-2xl shadow-lg text-white p-4 md:p-8 mb-4 md:mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-6">
            <div class="flex items-start gap-3 md:gap-6">
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center text-white/80 hover:text-white transition-colors">
                    <i class="ri-arrow-right-line text-xl md:text-2xl"></i>
                </a>

                <!-- Student Profile -->
                <div class="flex items-center gap-3 md:gap-4">
                    <x-avatar
                        :user="$student"
                        size="xl"
                        userType="student"
                        :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'" />
                    <div class="min-w-0">
                        <h1 class="text-xl md:text-3xl font-bold mb-0.5 md:mb-1 truncate">{{ $student->name }}</h1>
                        <div class="flex flex-wrap items-center gap-2 md:gap-4 text-white/90 text-xs md:text-sm">
                            <span class="flex items-center gap-1">
                                <i class="ri-group-line"></i>
                                <span class="truncate max-w-[100px] md:max-w-none">حلقة {{ $circle->name }}</span>
                            </span>
                            <span class="flex items-center gap-1">
                                <i class="ri-user-star-line"></i>
                                <span class="truncate max-w-[80px] md:max-w-none">{{ $circle->quranTeacher->user->name ?? 'المعلم' }}</span>
                            </span>
                        </div>

                        <!-- Quick Progress Indicator -->
                        <div class="mt-2 md:mt-3 flex flex-wrap items-center gap-2 md:gap-4">
                            <div class="flex items-center gap-2">
                                <div class="w-20 md:w-24 h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full transition-all duration-500"
                                         style="width: {{ number_format($stats['attendance_rate'], 1) }}%"></div>
                                </div>
                                <span class="text-xs md:text-sm font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                            </div>
                            <span class="text-xs md:text-sm text-white/80">معدل الحضور</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-2 md:gap-3 mt-2 lg:mt-0">
                <button onclick="window.print()" class="min-h-[44px] inline-flex items-center px-3 md:px-6 py-2 md:py-3 bg-white/20 backdrop-blur-sm text-white text-xs md:text-sm font-medium rounded-lg md:rounded-xl hover:bg-white/30 transition-colors">
                    <i class="ri-printer-line ml-1 md:ml-2"></i>
                    <span class="hidden sm:inline">طباعة التقرير</span>
                    <span class="sm:hidden">طباعة</span>
                </button>
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                   class="min-h-[44px] inline-flex items-center px-3 md:px-6 py-2 md:py-3 bg-white text-indigo-600 text-xs md:text-sm font-medium rounded-lg md:rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="ri-group-line ml-1 md:ml-2"></i>
                    <span class="hidden sm:inline">عرض الحلقة</span>
                    <span class="sm:hidden">الحلقة</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Student Statistics -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-4 md:mb-8">
        <!-- Total Sessions -->
        <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-blue-100 p-3 md:p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-blue-600 mb-0.5 md:mb-1 truncate">إجمالي الجلسات</p>
                    <p class="text-xl md:text-3xl font-bold text-blue-900 mb-1 md:mb-2">{{ $stats['total_sessions'] }}</p>
                    <div class="flex items-center gap-1 text-[10px] md:text-xs text-blue-600">
                        <i class="ri-calendar-line"></i>
                        <span>في هذه الحلقة</span>
                    </div>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-100 rounded-lg md:rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="ri-book-line text-lg md:text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Attended Sessions -->
        <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-green-100 p-3 md:p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-green-600 mb-0.5 md:mb-1 truncate">الجلسات المحضورة</p>
                    <p class="text-xl md:text-3xl font-bold text-green-900 mb-1 md:mb-2">{{ $stats['attended_sessions'] }}</p>
                    <div class="flex items-center gap-1 text-[10px] md:text-xs text-green-600">
                        <i class="ri-check-line"></i>
                        <span>{{ number_format($stats['attendance_rate'], 1) }}% حضور</span>
                    </div>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-green-100 rounded-lg md:rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="ri-checkbox-circle-line text-lg md:text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Pages Memorized -->
        <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-purple-100 p-3 md:p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-purple-600 mb-0.5 md:mb-1 truncate">الصفحات المحفوظة</p>
                    <p class="text-xl md:text-3xl font-bold text-purple-900 mb-1 md:mb-2">{{ $stats['total_pages_memorized'] }}</p>
                    <div class="flex items-center gap-1 text-[10px] md:text-xs text-purple-600">
                        <i class="ri-book-open-line"></i>
                        <span>في هذه الحلقة</span>
                    </div>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-purple-100 rounded-lg md:rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="ri-pages-line text-lg md:text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Average Performance -->
        <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-orange-100 p-3 md:p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-xs md:text-sm font-medium text-orange-600 mb-0.5 md:mb-1 truncate">متوسط الأداء</p>
                    <p class="text-xl md:text-3xl font-bold text-orange-900 mb-1 md:mb-2">{{ number_format(($stats['avg_recitation_quality'] + $stats['avg_tajweed_accuracy']) / 2, 1) }}</p>
                    <div class="flex items-center gap-1 text-[10px] md:text-xs text-orange-600">
                        <i class="ri-star-line"></i>
                        <span>من 10</span>
                    </div>
                </div>
                <div class="w-10 h-10 md:w-12 md:h-12 bg-orange-100 rounded-lg md:rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform flex-shrink-0">
                    <i class="ri-trophy-line text-lg md:text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 md:gap-8 mb-4 md:mb-8">
        <!-- Performance Over Time -->
        <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-8">
            <div class="flex items-center justify-between mb-4 md:mb-6">
                <div>
                    <h3 class="text-base md:text-xl font-bold text-gray-900">تطور الأداء</h3>
                    <p class="text-gray-600 text-xs md:text-sm">التلاوة والتجويد عبر الجلسات</p>
                </div>
                <i class="ri-line-chart-line text-xl md:text-2xl text-indigo-600"></i>
            </div>
            <div class="chart-container" style="height: 200px; md:height: 300px;">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Attendance Pattern -->
        <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-8">
            <div class="flex items-center justify-between mb-4 md:mb-6">
                <div>
                    <h3 class="text-base md:text-xl font-bold text-gray-900">نمط الحضور</h3>
                    <p class="text-gray-600 text-xs md:text-sm">آخر 10 جلسات</p>
                </div>
                <i class="ri-calendar-check-line text-xl md:text-2xl text-green-600"></i>
            </div>
            <div class="chart-container" style="height: 200px; md:height: 300px;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 md:gap-8">
        <!-- Sessions History -->
        <div class="xl:col-span-2">
            <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-8">
                <div class="flex items-center justify-between mb-4 md:mb-6">
                    <h3 class="text-lg md:text-2xl font-bold text-gray-900">سجل الجلسات</h3>
                    <span class="text-xs md:text-sm bg-indigo-100 text-indigo-700 px-2 md:px-3 py-1 rounded-full font-medium">
                        {{ $sessions->count() }} جلسة
                    </span>
                </div>

                <div class="space-y-3 md:space-y-4">
                    @forelse($sessions->take(10) as $session)
                        @php
                            $attendance = $session->attendances->where('student_id', $student->id)->first();
                        @endphp
                        <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg md:rounded-xl p-3 md:p-6 border border-gray-200 hover:shadow-md transition-all duration-300">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                <div class="flex items-start gap-3 md:gap-4">
                                    <!-- Status Indicator -->
                                    <div class="flex flex-col items-center flex-shrink-0">
                                        @if($attendance && $attendance->attendance_status === 'attended')
                                            <div class="w-3 h-3 md:w-4 md:h-4 bg-green-500 rounded-full mb-0.5 md:mb-1 animate-pulse"></div>
                                            <span class="text-[10px] md:text-xs text-green-600 font-bold">حضر</span>
                                        @elseif($attendance && $attendance->attendance_status === 'late')
                                            <div class="w-3 h-3 md:w-4 md:h-4 bg-yellow-500 rounded-full mb-0.5 md:mb-1"></div>
                                            <span class="text-[10px] md:text-xs text-yellow-600 font-bold">متأخر</span>
                                        @elseif($attendance && $attendance->attendance_status === 'absent')
                                            <div class="w-3 h-3 md:w-4 md:h-4 bg-red-500 rounded-full mb-0.5 md:mb-1"></div>
                                            <span class="text-[10px] md:text-xs text-red-600 font-bold">غائب</span>
                                        @else
                                            <div class="w-3 h-3 md:w-4 md:h-4 bg-gray-400 rounded-full mb-0.5 md:mb-1"></div>
                                            <span class="text-[10px] md:text-xs text-gray-500 font-bold">غير محدد</span>
                                        @endif
                                    </div>

                                    <!-- Session Details -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-2 mb-1 md:mb-2">
                                            <h4 class="font-bold text-gray-900 text-sm md:text-lg truncate">{{ $session->title ?? 'جلسة قرآنية' }}</h4>
                                            @if($attendance && ($attendance->papers_memorized_today || $attendance->verses_memorized_today))
                                                <span class="bg-green-100 text-green-800 text-[10px] md:text-xs px-1.5 md:px-2 py-0.5 md:py-1 rounded-full font-medium">
                                                    @if($attendance->papers_memorized_today)
                                                        +{{ $attendance->papers_memorized_today }} صفحة
                                                    @elseif($attendance->verses_memorized_today)
                                                        +{{ $attendance->verses_memorized_today }} آية
                                                    @endif
                                                </span>
                                            @endif
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600">
                                            <span class="flex items-center gap-1">
                                                <i class="ri-calendar-line"></i>
                                                <span>{{ $session->scheduled_at ? formatDateArabic($session->scheduled_at) : 'غير مجدولة' }}</span>
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <i class="ri-time-line"></i>
                                                <span>{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : '--:--' }}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Performance Scores -->
                                @if($attendance && ($attendance->recitation_quality || $attendance->tajweed_accuracy))
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($attendance->recitation_quality)
                                            <span class="bg-purple-100 text-purple-700 px-1.5 md:px-2 py-0.5 md:py-1 rounded text-[10px] md:text-xs font-medium">
                                                تلاوة: {{ $attendance->recitation_quality }}/10
                                            </span>
                                        @endif
                                        @if($attendance->tajweed_accuracy)
                                            <span class="bg-indigo-100 text-indigo-700 px-1.5 md:px-2 py-0.5 md:py-1 rounded text-[10px] md:text-xs font-medium">
                                                تجويد: {{ $attendance->tajweed_accuracy }}/10
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 md:py-12">
                            <i class="ri-calendar-line text-3xl md:text-4xl text-gray-300 mb-2 md:mb-3"></i>
                            <p class="text-gray-500 text-sm md:text-base">لا توجد جلسات مسجلة للطالب في هذه الحلقة</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4 md:space-y-8">
            <!-- Performance Summary -->
            <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-6">
                <div class="flex items-center justify-between mb-3 md:mb-4">
                    <h3 class="text-base md:text-lg font-bold text-gray-900">ملخص الأداء</h3>
                    <i class="ri-trophy-line text-xl md:text-2xl text-orange-600"></i>
                </div>

                <div class="space-y-3 md:space-y-4">
                    <!-- Recitation Quality -->
                    <div>
                        <div class="flex justify-between text-xs md:text-sm mb-1">
                            <span class="text-gray-600">جودة التلاوة المتوسطة</span>
                            <span class="font-medium">{{ number_format($stats['avg_recitation_quality'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 md:h-2">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-1.5 md:h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $stats['avg_recitation_quality'] * 10 }}%"></div>
                        </div>
                    </div>

                    <!-- Tajweed Accuracy -->
                    <div>
                        <div class="flex justify-between text-xs md:text-sm mb-1">
                            <span class="text-gray-600">دقة التجويد المتوسطة</span>
                            <span class="font-medium">{{ number_format($stats['avg_tajweed_accuracy'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 md:h-2">
                            <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-1.5 md:h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $stats['avg_tajweed_accuracy'] * 10 }}%"></div>
                        </div>
                    </div>

                    <!-- Attendance Rate -->
                    <div>
                        <div class="flex justify-between text-xs md:text-sm mb-1">
                            <span class="text-gray-600">معدل الحضور</span>
                            <span class="font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 md:h-2">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-1.5 md:h-2 rounded-full transition-all duration-500"
                                 style="width: {{ $stats['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Circle Information -->
            <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">معلومات الحلقة</h3>

                <div class="space-y-2 md:space-y-3 text-xs md:text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 flex items-center gap-1">
                            <i class="ri-group-line"></i>
                            اسم الحلقة
                        </span>
                        <span class="font-medium truncate max-w-[120px] md:max-w-none">{{ $circle->name }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 flex items-center gap-1">
                            <i class="ri-user-star-line"></i>
                            المعلم
                        </span>
                        <span class="font-medium truncate max-w-[120px] md:max-w-none">{{ $circle->quranTeacher->user->name ?? 'غير محدد' }}</span>
                    </div>

                    <div class="flex items-center justify-between">
                        <span class="text-gray-600 flex items-center gap-1">
                            <i class="ri-team-line"></i>
                            عدد الطلاب
                        </span>
                        <span class="font-medium">{{ $circle->students->count() }} طالب</span>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4">إجراءات سريعة</h3>

                <div class="space-y-2 md:space-y-3">
                    <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                       class="min-h-[44px] flex items-center justify-center w-full px-4 md:px-5 py-2 md:py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-xs md:text-sm font-medium rounded-lg md:rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-colors shadow-lg">
                        <i class="ri-group-line ml-1 md:ml-2"></i>
                        عرض الحلقة
                    </a>

                    <a href="{{ route('teacher.group-circles.progress', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}"
                       class="min-h-[44px] flex items-center justify-center w-full px-4 md:px-5 py-2 md:py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-xs md:text-sm font-medium rounded-lg md:rounded-xl hover:from-green-700 hover:to-emerald-700 transition-colors shadow-lg">
                        <i class="ri-bar-chart-line ml-1 md:ml-2"></i>
                        تقرير الحلقة الكامل
                    </a>

                    <button class="min-h-[44px] w-full px-4 md:px-5 py-2 md:py-3 border-2 border-gray-200 text-gray-700 text-xs md:text-sm font-medium rounded-lg md:rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors">
                        <i class="ri-download-line ml-1 md:ml-2"></i>
                        تصدير التقرير
                    </button>
                </div>
            </div>

            <!-- Certificate Section -->
            @php
                $studentSubscription = $student->quranSubscriptions()
                    ->where('quran_circle_id', $circle->id)
                    ->orWhere(function($q) use ($circle) {
                        $q->whereHas('individualCircle', function($iq) use ($circle) {
                            $iq->where('quran_circle_id', $circle->id);
                        });
                    })
                    ->first();
            @endphp

            @if($studentSubscription)
            <div class="bg-white rounded-xl md:rounded-2xl shadow-lg border border-gray-100 p-4 md:p-6">
                <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2">
                    <i class="ri-award-line text-amber-500"></i>
                    الشهادات
                </h3>

                @if($studentSubscription->certificate_issued && $studentSubscription->certificate)
                    <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-lg md:rounded-xl p-3 md:p-4 border-2 border-amber-200 mb-3 md:mb-4">
                        <div class="flex items-center gap-2 md:gap-3 mb-2 md:mb-3">
                            <div class="w-10 h-10 md:w-12 md:h-12 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="ri-award-fill text-lg md:text-xl text-amber-600"></i>
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-amber-800 text-sm md:text-base">تم إصدار الشهادة</p>
                                <p class="text-[10px] md:text-xs text-amber-600 truncate">{{ $studentSubscription->certificate->certificate_number }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <a href="{{ route('student.certificate.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $studentSubscription->certificate->id]) }}"
                               target="_blank"
                               class="min-h-[44px] w-full inline-flex items-center justify-center px-3 md:px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white text-xs md:text-sm font-medium rounded-lg transition-colors">
                                <i class="ri-eye-line ml-1 md:ml-2"></i>
                                عرض الشهادة
                            </a>
                            <a href="{{ route('student.certificate.download', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'certificate' => $studentSubscription->certificate->id]) }}"
                               class="min-h-[44px] w-full inline-flex items-center justify-center px-3 md:px-4 py-2 bg-green-500 hover:bg-green-600 text-white text-xs md:text-sm font-medium rounded-lg transition-colors">
                                <i class="ri-download-line ml-1 md:ml-2"></i>
                                تحميل PDF
                            </a>
                        </div>
                    </div>
                @else
                    <p class="text-xs md:text-sm text-gray-600 mb-3 md:mb-4">يمكنك إصدار شهادة للطالب عند إتمام البرنامج أو تحقيق إنجاز معين</p>
                    <button type="button"
                            onclick="Livewire.dispatch('openModal', { subscriptionType: 'quran', subscriptionId: {{ $studentSubscription->id }}, circleId: null })"
                            class="min-h-[44px] w-full inline-flex items-center justify-center px-4 md:px-5 py-2 md:py-3 bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-600 hover:to-yellow-600 text-white text-xs md:text-sm font-bold rounded-lg md:rounded-xl transition-all shadow-lg hover:shadow-xl">
                        <i class="ri-award-line ml-1 md:ml-2 text-base md:text-lg"></i>
                        إصدار شهادة
                    </button>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Certificate Modal -->
@livewire('issue-certificate-modal')

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sample data for charts
    const studentSessions = [
        @foreach($sessions as $session)
            @php
                $attendance = $session->attendances->where('student_id', $student->id)->first();
            @endphp
            {
                date: '{{ $session->scheduled_at ? formatDateArabic($session->scheduled_at, 'Y-m-d') : '' }}',
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
