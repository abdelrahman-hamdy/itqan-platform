<x-layouts.student
    :title="'تقريري الأكاديمي - ' . config('app.name', 'منصة إتقان')"
    :description="'التقرير الأكاديمي الخاص بي'">

<div>
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li><a href="{{ route('student.dashboard', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">لوحة التحكم</a></li>
            <li>/</li>
            <li><a href="{{ route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $subscription->id]) }}" class="hover:text-primary">اشتراكي الأكاديمي</a></li>
            <li>/</li>
            <li class="text-gray-900">تقريري</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="bg-gradient-to-r from-primary to-blue-600 rounded-xl shadow-lg p-8 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">تقريري الأكاديمي</h1>
                <p class="mt-2 opacity-90">متابعة تقدمي وأدائي الأكاديمي</p>
                @if(isset($subject))
                    <div class="mt-3 inline-flex items-center px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm">
                        <i class="ri-book-line ml-1"></i>
                        {{ $subject->name ?? 'مادة غير محددة' }}
                    </div>
                @endif
            </div>
            <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                <i class="ri-file-chart-line text-5xl"></i>
            </div>
        </div>
    </div>

    <!-- Overall Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">معدلي العام</p>
                    <p class="text-3xl font-bold text-primary mt-2">{{ number_format($performance['average_overall_performance'], 1) }}</p>
                    <p class="text-xs text-gray-500 mt-1">من 10</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-blue-100 to-blue-200 rounded-xl flex items-center justify-center">
                    <i class="ri-star-line text-primary text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">نسبة حضوري</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ $attendance['attendance_rate'] }}%</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $attendance['attended'] }} جلسة</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-green-100 to-green-200 rounded-xl flex items-center justify-center">
                    <i class="ri-checkbox-circle-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">الجلسات المكتملة</p>
                    <p class="text-3xl font-bold text-purple-600 mt-2">{{ $progress['sessions_completed'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">من {{ $progress['total_sessions'] }}</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-purple-100 to-purple-200 rounded-xl flex items-center justify-center">
                    <i class="ri-calendar-check-line text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600">إنجاز الواجبات</p>
                    <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $progress['homework_completion_rate'] }}%</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $progress['homework_submitted'] }}/{{ $progress['homework_assigned'] }}</p>
                </div>
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-100 to-yellow-200 rounded-xl flex items-center justify-center">
                    <i class="ri-file-edit-line text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Performance Card -->
            <x-academic.performance-card
                :performance="$performance"
                title="تقييم أدائي" />

            <!-- Attendance Card -->
            <x-reports.attendance-card
                :attendance="$attendance"
                title="سجل حضوري" />

            <!-- Progress Card -->
            <x-academic.progress-card
                :progress="$progress"
                title="تقدمي الدراسي" />

            <!-- Grade Improvement Trend -->
            @if($progress['grade_improvement'] != 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">
                    <i class="ri-line-chart-line ml-2"></i>
                    اتجاه التحسن
                </h2>
                <div class="flex items-center justify-center p-6 bg-gradient-to-r {{ $progress['grade_improvement'] > 0 ? 'from-green-50 to-green-100' : 'from-red-50 to-red-100' }} rounded-lg">
                    <div class="text-center">
                        <div class="flex items-center justify-center gap-2">
                            <i class="ri-{{ $progress['grade_improvement'] > 0 ? 'arrow-up' : 'arrow-down' }}-line text-3xl {{ $progress['grade_improvement'] > 0 ? 'text-green-600' : 'text-red-600' }}"></i>
                            <span class="text-4xl font-bold {{ $progress['grade_improvement'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ abs($progress['grade_improvement']) }}
                            </span>
                        </div>
                        <p class="text-sm {{ $progress['grade_improvement'] > 0 ? 'text-green-700' : 'text-red-700' }} mt-2">
                            @if($progress['grade_improvement'] > 0)
                                أحسنت! معدلك في تحسن مستمر
                            @else
                                تحتاج لمزيد من التركيز
                            @endif
                        </p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Teacher Info -->
            @if(isset($teacher))
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">
                    <i class="ri-user-line ml-2"></i>
                    معلمي
                </h3>
                <div class="flex items-center gap-3">
                    @if($teacher->user && $teacher->user->profile_picture)
                        <img src="{{ Storage::url($teacher->user->profile_picture) }}"
                             alt="{{ $teacher->user->name }}"
                             class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center">
                            <i class="ri-user-line text-white text-xl"></i>
                        </div>
                    @endif
                    <div class="flex-1">
                        <p class="font-bold text-gray-900">{{ $teacher->user->name ?? 'المعلم' }}</p>
                        @if($teacher->specialization)
                            <p class="text-xs text-gray-600">{{ $teacher->specialization }}</p>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- My Performance Breakdown -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">
                    <i class="ri-bar-chart-line ml-2"></i>
                    تفصيل أدائي
                </h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-700">المشاركة في الحصة</span>
                            <span class="text-sm font-bold text-blue-600">{{ number_format($performance['average_participation_degree'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 rounded-full transition-all duration-500"
                                 style="width: {{ ($performance['average_participation_degree'] / 10) * 100 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-700">الفهم والاستيعاب</span>
                            <span class="text-sm font-bold text-green-600">{{ number_format($performance['average_understanding_degree'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-gradient-to-r from-green-500 to-green-600 h-2.5 rounded-full transition-all duration-500"
                                 style="width: {{ ($performance['average_understanding_degree'] / 10) * 100 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm text-gray-700">أداء الواجبات</span>
                            <span class="text-sm font-bold text-purple-600">{{ number_format($performance['average_homework_degree'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-gradient-to-r from-purple-500 to-purple-600 h-2.5 rounded-full transition-all duration-500"
                                 style="width: {{ ($performance['average_homework_degree'] / 10) * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Motivational Card -->
            <div class="bg-gradient-to-br from-primary to-blue-600 rounded-xl shadow-lg p-6 text-white">
                <div class="text-center">
                    <i class="ri-trophy-line text-5xl mb-3 opacity-90"></i>
                    <h3 class="font-bold text-lg mb-2">استمر في التقدم!</h3>
                    <p class="text-sm opacity-90">
                        @if($performance['average_overall_performance'] >= 8)
                            أداءك ممتاز! واصل التميز
                        @elseif($performance['average_overall_performance'] >= 6)
                            أداءك جيد! يمكنك الوصول للأفضل
                        @else
                            كل جلسة هي فرصة للتحسن
                        @endif
                    </p>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="font-bold text-gray-900 mb-4">
                    <i class="ri-links-line ml-2"></i>
                    روابط سريعة
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('student.academic-subscriptions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $subscription->id]) }}"
                       class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <span class="text-sm font-medium text-gray-700">عرض جميع الجلسات</span>
                        <i class="ri-arrow-left-line text-gray-600"></i>
                    </a>
                    @if(isset($upcomingSession))
                        <a href="{{ route('student.academic-sessions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $upcomingSession->id]) }}"
                           class="flex items-center justify-between p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                            <span class="text-sm font-medium text-blue-700">الجلسة القادمة</span>
                            <i class="ri-arrow-left-line text-blue-600"></i>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.student>
