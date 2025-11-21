<x-layouts.teacher
    :title="'التقرير الأكاديمي - ' . $student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الأكاديمي الشامل للطالب'">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('teacher.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">{{ auth()->user()->name }}</a></li>
            <li>/</li>
            <li><a href="{{ route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $subscription->id]) }}" class="hover:text-primary">الاشتراك الأكاديمي</a></li>
            <li>/</li>
            <li class="text-gray-900">التقرير الشامل</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">التقرير الأكاديمي الشامل</h1>
                <p class="text-gray-600 mt-1">الطالب: {{ $student->name }}</p>
                @if(isset($subject))
                    <p class="text-sm text-gray-500 mt-1">المادة: {{ $subject->name ?? 'غير محدد' }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $subscription->id]) }}"
                   class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                    <i class="ri-arrow-right-line ml-1"></i>
                    عودة للاشتراك
                </a>
                <button onclick="window.print()"
                        class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition-colors">
                    <i class="ri-printer-line ml-1"></i>
                    طباعة
                </button>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">الجلسات المكتملة</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $progress['sessions_completed'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">من أصل {{ $progress['total_sessions'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-checkbox-circle-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">نسبة الحضور</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $attendance['attendance_rate'] }}%</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $attendance['attended'] }} حضور</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-star-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">المعدل العام</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($performance['average_overall_performance'], 1) }}</p>
                    <p class="text-xs text-gray-500 mt-1">من 10</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-star-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">إنجاز الواجبات</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ $progress['homework_completion_rate'] }}%</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $progress['homework_submitted'] }}/{{ $progress['homework_assigned'] }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="ri-file-edit-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Attendance Card -->
            <x-reports.attendance-card
                :attendance="$attendance"
                title="إحصائيات الحضور" />

            <!-- Performance Card -->
            <x-academic.performance-card
                :performance="$performance"
                title="التقييم الأكاديمي" />

            <!-- Progress Card -->
            <x-academic.progress-card
                :progress="$progress"
                title="التقدم الأكاديمي" />

            <!-- Recent Sessions -->
            @if(isset($recentSessions) && $recentSessions->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">الجلسات الأخيرة</h2>
                <div class="space-y-3">
                    @foreach($recentSessions as $session)
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-gray-900">{{ $session->title }}</span>
                                    @if($session->status === 'completed')
                                        <span class="px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">مكتمل</span>
                                    @elseif($session->status === 'scheduled')
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">مجدول</span>
                                    @endif
                                </div>
                                <div class="text-xs text-gray-600 mt-1">
                                    {{ $session->scheduled_at?->format('Y-m-d H:i') }}
                                </div>
                            </div>
                            @php
                                $sessionReport = $session->sessionReports()->where('student_id', $student->id)->first();
                            @endphp
                            @if($sessionReport && $sessionReport->overall_performance)
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-primary">{{ number_format($sessionReport->overall_performance, 1) }}/10</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Subscription Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">معلومات الاشتراك</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">تاريخ البداية:</span>
                        <span class="font-medium text-gray-900">{{ $subscription->start_date?->format('Y-m-d') ?? 'لم تبدأ' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">تاريخ الانتهاء:</span>
                        <span class="font-medium text-gray-900">{{ $subscription->end_date?->format('Y-m-d') ?? 'غير محدد' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">عدد الجلسات:</span>
                        <span class="font-medium text-gray-900">{{ $subscription->total_sessions ?? 'غير محدد' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">الحالة:</span>
                        <span class="font-medium {{ $subscription->status === 'active' ? 'text-green-600' : 'text-gray-600' }}">
                            {{ $subscription->status === 'active' ? 'نشط' : ($subscription->status === 'completed' ? 'مكتمل' : 'منتهي') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Performance Breakdown -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">تفصيل الأداء</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">المشاركة</span>
                            <span class="text-sm font-bold text-gray-900">{{ number_format($performance['average_participation_degree'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($performance['average_participation_degree'] / 10) * 100 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">الفهم والاستيعاب</span>
                            <span class="text-sm font-bold text-gray-900">{{ number_format($performance['average_understanding_degree'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($performance['average_understanding_degree'] / 10) * 100 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm text-gray-600">أداء الواجبات</span>
                            <span class="text-sm font-bold text-gray-900">{{ number_format($performance['average_homework_degree'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: {{ ($performance['average_homework_degree'] / 10) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                <div class="space-y-2">
                    <a href="{{ route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $subscription->id]) }}"
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <span class="text-sm font-medium text-gray-700">عرض جميع الجلسات</span>
                        <i class="ri-arrow-left-line text-gray-600"></i>
                    </a>
                    @if($progress['sessions_completed'] > 0)
                        <button onclick="window.print()"
                                class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <span class="text-sm font-medium text-gray-700">طباعة التقرير</span>
                            <i class="ri-printer-line text-gray-600"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.teacher>
