@php
use App\Enums\SessionSubscriptionStatus;

    $subdomain = $subdomain ?? request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout :title="__('parent.reports.progress_title')">
    <div class="space-y-6">

        <!-- Page Header -->
        <div class="mb-4 md:mb-8">
            <div>
                <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-center">
                    <i class="ri-bar-chart-line text-teal-600 ms-2 md:ms-3"></i>
                    {{ __('parent.reports.progress.page_header') }}
                </h1>
                <p class="text-sm md:text-base text-gray-600 mt-1 md:mt-2">{{ __('parent.reports.progress.page_description') }}</p>
            </div>
        </div>

        <!-- Overall Attendance Statistics -->
        @if(isset($attendanceReport))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-4 md:mb-8">
            <!-- Total Sessions -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg md:rounded-xl shadow-md p-3 md:p-6 text-white">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-blue-100 text-xs md:text-sm font-medium truncate">{{ __('parent.reports.total_sessions') }}</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['total_sessions'] }}</p>
                        <p class="text-xs md:text-sm text-blue-100 mt-0.5 md:mt-1 hidden sm:block">{{ __('parent.reports.sessions_recorded') }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-calendar-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Present -->
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg md:rounded-xl shadow-md p-3 md:p-6 text-white">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-green-100 text-xs md:text-sm font-medium truncate">{{ __('parent.reports.present') }}</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['present_count'] }}</p>
                        <p class="text-xs md:text-sm text-green-100 mt-0.5 md:mt-1 hidden sm:block">{{ __('parent.reports.sessions_attended') }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-check-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Absent -->
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg md:rounded-xl shadow-md p-3 md:p-6 text-white">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-red-100 text-xs md:text-sm font-medium truncate">{{ __('parent.reports.absent') }}</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['absent_count'] }}</p>
                        <p class="text-xs md:text-sm text-red-100 mt-0.5 md:mt-1 hidden sm:block">{{ __('parent.reports.absent_sessions') }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-close-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Attendance Rate -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg md:rounded-xl shadow-md p-3 md:p-6 text-white">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-purple-100 text-xs md:text-sm font-medium truncate">{{ __('parent.reports.attendance_rate') }}</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['attendance_rate'] }}%</p>
                        <p class="text-xs md:text-sm text-purple-100 mt-0.5 md:mt-1 hidden sm:block">{{ __('parent.reports.attendance_rate_value') }}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-percent-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Program Attendance Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-8">
            <!-- Quran Attendance -->
            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2 md:gap-3">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="ri-book-read-line text-lg md:text-xl text-green-600"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 text-sm md:text-base truncate">{{ __('parent.reports.quran_attendance_title') }}</h3>
                            <p class="text-xs md:text-sm text-gray-500">{{ __('parent.reports.attendance_rate') }}: {{ $attendanceReport['quran']['attendance_rate'] }}%</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 md:p-6">
                    <div class="grid grid-cols-3 gap-2 md:gap-4 mb-3 md:mb-4">
                        <div class="text-center p-2 md:p-4 bg-green-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-green-600">{{ $attendanceReport['quran']['present'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">{{ __('parent.reports.present') }}</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-red-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-red-600">{{ $attendanceReport['quran']['absent'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">{{ __('parent.reports.absent') }}</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-yellow-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-yellow-600">{{ $attendanceReport['quran']['late'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">{{ __('parent.reports.late') }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">{{ __('parent.reports.attendance_rate') }}</span>
                            <span class="font-bold text-green-600">{{ $attendanceReport['quran']['attendance_rate'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-green-500 h-3 rounded-full transition-all duration-300" style="width: {{ $attendanceReport['quran']['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Academic Attendance -->
            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2 md:gap-3">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="ri-graduation-cap-line text-lg md:text-xl text-blue-600"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-gray-900 text-sm md:text-base truncate">{{ __('parent.reports.academic_attendance_title') }}</h3>
                            <p class="text-xs md:text-sm text-gray-500">{{ __('parent.reports.attendance_rate') }}: {{ $attendanceReport['academic']['attendance_rate'] }}%</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 md:p-6">
                    <div class="grid grid-cols-3 gap-2 md:gap-4 mb-3 md:mb-4">
                        <div class="text-center p-2 md:p-4 bg-green-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-green-600">{{ $attendanceReport['academic']['present'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">{{ __('parent.reports.present') }}</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-red-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-red-600">{{ $attendanceReport['academic']['absent'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">{{ __('parent.reports.absent') }}</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-yellow-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-yellow-600">{{ $attendanceReport['academic']['late'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">{{ __('parent.reports.late') }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">{{ __('parent.reports.attendance_rate') }}</span>
                            <span class="font-bold text-blue-600">{{ $attendanceReport['academic']['attendance_rate'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-500 h-3 rounded-full transition-all duration-300" style="width: {{ $attendanceReport['academic']['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Per-Child Subscriptions Section Header -->
        <div class="flex items-center gap-2 md:gap-3 mb-3 md:mb-4">
            <div class="w-8 h-8 md:w-10 md:h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="ri-team-line text-lg md:text-xl text-purple-600"></i>
            </div>
            <div class="min-w-0">
                <h2 class="text-base md:text-xl font-bold text-gray-900">{{ __('parent.reports.child_subscriptions_title') }}</h2>
                <p class="text-xs md:text-sm text-gray-500">{{ __('parent.reports.child_subscriptions_description') }}</p>
            </div>
        </div>

        @if(empty($childrenData))
            <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
                <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                    <i class="ri-file-list-3-line text-2xl md:text-4xl text-gray-400"></i>
                </div>
                <h3 class="text-base md:text-xl font-bold text-gray-900 mb-1 md:mb-2">{{ __('parent.reports.no_subscriptions_title') }}</h3>
                <p class="text-sm md:text-base text-gray-500">{{ __('parent.reports.no_subscriptions_description') }}</p>
            </div>
        @else
            <!-- Per-Child Subscriptions -->
            @foreach($childrenData as $childData)
                @php
                    $child = $childData['child'];
                    $subscriptions = $childData['subscriptions'];
                    $hasAnySubscription = !empty($subscriptions['quran']) || !empty($subscriptions['academic']) || !empty($subscriptions['interactive']);
                @endphp

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Child Header -->
                    <div class="bg-gradient-to-r from-purple-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center gap-4">
                            <x-avatar
                                :user="$child->user"
                                user-type="student"
                                size="lg"
                            />
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">{{ $child->user?->name ?? $child->first_name }}</h2>
                                <p class="text-sm text-gray-500">
                                    {{ count($subscriptions['quran']) + count($subscriptions['academic']) + count($subscriptions['interactive']) }} {{ __('parent.reports.subscription_count') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if(!$hasAnySubscription)
                        <div class="p-8 text-center">
                            <p class="text-gray-500">{{ __('parent.reports.no_child_subscriptions') }}</p>
                        </div>
                    @else
                        <div class="p-6 space-y-8">

                            {{-- Quran Subscriptions Section --}}
                            @if(!empty($subscriptions['quran']))
                                <div>
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                            <i class="ri-book-read-line text-green-600"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ __('parent.reports.quran_program') }}</h3>
                                        <span class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full">
                                            {{ count($subscriptions['quran']) }} {{ __('parent.reports.subscription_count') }}
                                        </span>
                                    </div>

                                    <div class="grid gap-4">
                                        @foreach($subscriptions['quran'] as $item)
                                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-3 mb-2">
                                                            <h4 class="font-bold text-gray-900">{{ $item['name'] }}</h4>
                                                            @php
                                                                $statusValue = $item['status'] instanceof \BackedEnum ? $item['status']->value : $item['status'];
                                                            @endphp
                                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full
                                                                {{ $statusValue === SessionSubscriptionStatus::ACTIVE->value ? 'bg-green-100 text-green-700' : '' }}
                                                                {{ $statusValue === SessionSubscriptionStatus::PAUSED->value ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                                {{ in_array($statusValue, ['expired', SessionSubscriptionStatus::CANCELLED->value]) ? 'bg-gray-100 text-gray-600' : '' }}
                                                                {{ $statusValue === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}">
                                                                {{ $item['status_label'] }}
                                                            </span>
                                                        </div>

                                                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-3">
                                                            <span class="flex items-center gap-1">
                                                                <i class="ri-user-line"></i>
                                                                {{ $item['teacher_name'] }}
                                                            </span>
                                                            @if($item['started_at'])
                                                                <span class="flex items-center gap-1">
                                                                    <i class="ri-calendar-line"></i>
                                                                    {{ __('parent.reports.started_on', ['date' => $item['started_at']->format('Y-m-d')]) }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.sessions_count') }}</p>
                                                                <p class="font-bold text-gray-900">{{ $item['completed_sessions'] }}/{{ $item['total_sessions'] }}</p>
                                                            </div>
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.attendance_rate') }}</p>
                                                                <p class="font-bold {{ $item['attendance_rate'] >= 80 ? 'text-green-600' : ($item['attendance_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                                                    {{ $item['attendance_rate'] }}%
                                                                </p>
                                                            </div>
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.performance') }}</p>
                                                                <p class="font-bold {{ $item['performance_score'] >= 7 ? 'text-green-600' : ($item['performance_score'] >= 5 ? 'text-yellow-600' : 'text-gray-600') }}">
                                                                    {{ $item['performance_score'] > 0 ? $item['performance_score'] . '/10' : '-' }}
                                                                </p>
                                                            </div>
                                                            <div class="flex items-center justify-center">
                                                                @if($item['report_url'])
                                                                    <a href="{{ $item['report_url'] }}"
                                                                       class="text-green-600 hover:text-green-700 text-sm font-bold flex items-center gap-1">
                                                                        <i class="ri-file-chart-line"></i>
                                                                        {{ __('parent.reports.detailed_report') }}
                                                                    </a>
                                                                @else
                                                                    <span class="text-gray-400 text-sm">{{ __('parent.reports.no_report') }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Academic Subscriptions Section --}}
                            @if(!empty($subscriptions['academic']))
                                <div>
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="ri-graduation-cap-line text-blue-600"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ __('parent.reports.academic_program') }}</h3>
                                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded-full">
                                            {{ count($subscriptions['academic']) }} {{ __('parent.reports.subscription_count') }}
                                        </span>
                                    </div>

                                    <div class="grid gap-4">
                                        @foreach($subscriptions['academic'] as $item)
                                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-3 mb-2">
                                                            <h4 class="font-bold text-gray-900">{{ $item['name'] }}</h4>
                                                            @php
                                                                $statusValue = $item['status'] instanceof \BackedEnum ? $item['status']->value : $item['status'];
                                                            @endphp
                                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full
                                                                {{ $statusValue === SessionSubscriptionStatus::ACTIVE->value ? 'bg-green-100 text-green-700' : '' }}
                                                                {{ $statusValue === SessionSubscriptionStatus::PAUSED->value ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                                {{ in_array($statusValue, ['expired', SessionSubscriptionStatus::CANCELLED->value]) ? 'bg-gray-100 text-gray-600' : '' }}
                                                                {{ $statusValue === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}">
                                                                {{ $item['status_label'] }}
                                                            </span>
                                                        </div>

                                                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-3">
                                                            <span class="flex items-center gap-1">
                                                                <i class="ri-user-line"></i>
                                                                {{ $item['teacher_name'] }}
                                                            </span>
                                                            @if($item['started_at'])
                                                                <span class="flex items-center gap-1">
                                                                    <i class="ri-calendar-line"></i>
                                                                    {{ __('parent.reports.started_on', ['date' => $item['started_at']->format('Y-m-d')]) }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.lessons_count') }}</p>
                                                                <p class="font-bold text-gray-900">{{ $item['completed_sessions'] }}/{{ $item['total_sessions'] }}</p>
                                                            </div>
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.attendance_rate') }}</p>
                                                                <p class="font-bold {{ $item['attendance_rate'] >= 80 ? 'text-green-600' : ($item['attendance_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                                                    {{ $item['attendance_rate'] }}%
                                                                </p>
                                                            </div>
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.performance') }}</p>
                                                                <p class="font-bold {{ $item['performance_score'] >= 7 ? 'text-green-600' : ($item['performance_score'] >= 5 ? 'text-yellow-600' : 'text-gray-600') }}">
                                                                    {{ $item['performance_score'] > 0 ? $item['performance_score'] . '/10' : '-' }}
                                                                </p>
                                                            </div>
                                                            <div class="flex items-center justify-center">
                                                                <a href="{{ $item['report_url'] }}"
                                                                   class="text-blue-600 hover:text-blue-700 text-sm font-bold flex items-center gap-1">
                                                                    <i class="ri-file-chart-line"></i>
                                                                    {{ __('parent.reports.detailed_report') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            {{-- Interactive Course Subscriptions Section --}}
                            @if(!empty($subscriptions['interactive']))
                                <div>
                                    <div class="flex items-center gap-2 mb-4">
                                        <div class="w-8 h-8 bg-violet-100 rounded-lg flex items-center justify-center">
                                            <i class="ri-play-circle-line text-violet-600"></i>
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-900">{{ __('parent.reports.interactive_courses') }}</h3>
                                        <span class="bg-violet-100 text-violet-700 text-xs font-bold px-2 py-1 rounded-full">
                                            {{ count($subscriptions['interactive']) }} {{ __('parent.reports.course_count') }}
                                        </span>
                                    </div>

                                    <div class="grid gap-4">
                                        @foreach($subscriptions['interactive'] as $item)
                                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex-1">
                                                        <div class="flex items-center gap-3 mb-2">
                                                            <h4 class="font-bold text-gray-900">{{ $item['name'] }}</h4>
                                                            @php
                                                                $statusValue = $item['status'] instanceof \BackedEnum ? $item['status']->value : $item['status'];
                                                            @endphp
                                                            <span class="px-2 py-0.5 text-xs font-bold rounded-full
                                                                {{ $statusValue === SessionSubscriptionStatus::ACTIVE->value ? 'bg-green-100 text-green-700' : '' }}
                                                                {{ $statusValue === SessionSubscriptionStatus::PAUSED->value ? 'bg-yellow-100 text-yellow-700' : '' }}
                                                                {{ in_array($statusValue, ['expired', SessionSubscriptionStatus::CANCELLED->value]) ? 'bg-gray-100 text-gray-600' : '' }}
                                                                {{ $statusValue === 'completed' ? 'bg-blue-100 text-blue-700' : '' }}">
                                                                {{ $item['status_label'] }}
                                                            </span>
                                                        </div>

                                                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-3">
                                                            <span class="flex items-center gap-1">
                                                                <i class="ri-user-line"></i>
                                                                {{ $item['teacher_name'] }}
                                                            </span>
                                                            @if($item['started_at'])
                                                                <span class="flex items-center gap-1">
                                                                    <i class="ri-calendar-line"></i>
                                                                    {{ __('parent.reports.enrolled_on', ['date' => $item['started_at']->format('Y-m-d')]) }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.sessions_count') }}</p>
                                                                <p class="font-bold text-gray-900">{{ $item['attended_sessions'] }}/{{ $item['total_sessions'] }}</p>
                                                            </div>
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.attendance_rate') }}</p>
                                                                <p class="font-bold {{ $item['attendance_rate'] >= 80 ? 'text-green-600' : ($item['attendance_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                                                                    {{ $item['attendance_rate'] }}%
                                                                </p>
                                                            </div>
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.progress') }}</p>
                                                                <p class="font-bold text-violet-600">{{ $item['progress_percentage'] }}%</p>
                                                            </div>
                                                            <div class="bg-gray-50 rounded-lg p-2 text-center">
                                                                <p class="text-xs text-gray-500">{{ __('parent.reports.performance') }}</p>
                                                                <p class="font-bold {{ $item['performance_score'] >= 7 ? 'text-green-600' : ($item['performance_score'] >= 5 ? 'text-yellow-600' : 'text-gray-600') }}">
                                                                    {{ $item['performance_score'] > 0 ? $item['performance_score'] . '/10' : '-' }}
                                                                </p>
                                                            </div>
                                                            <div class="flex items-center justify-center">
                                                                <a href="{{ $item['report_url'] }}"
                                                                   class="text-violet-600 hover:text-violet-700 text-sm font-bold flex items-center gap-1">
                                                                    <i class="ri-file-chart-line"></i>
                                                                    {{ __('parent.reports.detailed_report') }}
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endif
                </div>
            @endforeach
        @endif

    </div>
</x-layouts.parent-layout>
