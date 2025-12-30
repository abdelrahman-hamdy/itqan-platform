@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy?->subdomain ?? 'itqan-academy';
@endphp

<x-layouts.parent-layout title="تقرير الحضور">
    <div class="space-y-6">

        <!-- Page Header -->
        <div class="mb-4 md:mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 md:gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-center">
                        <i class="ri-calendar-check-line text-blue-600 ms-2 md:ms-3"></i>
                        تقرير الحضور
                    </h1>
                    <p class="text-sm md:text-base text-gray-600 mt-1 md:mt-2">متابعة سجل حضور أبنائك في جميع الجلسات</p>
                </div>
                <a href="{{ route('parent.reports.progress', ['subdomain' => $subdomain]) }}"
                   class="min-h-[44px] inline-flex items-center justify-center gap-2 bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 md:py-2.5 px-4 md:px-5 rounded-lg transition-colors text-sm md:text-base">
                    <i class="ri-bar-chart-line"></i>
                    <span>تقرير التقدم</span>
                </a>
            </div>
        </div>

        <!-- Overall Attendance Statistics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-4 md:mb-8">
            <!-- Total Sessions -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg md:rounded-xl shadow-md p-3 md:p-6 text-white">
                <div class="flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-blue-100 text-xs md:text-sm font-medium truncate">إجمالي الجلسات</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['total_sessions'] }}</p>
                        <p class="text-xs md:text-sm text-blue-100 mt-0.5 md:mt-1 hidden sm:block">جلسة مسجلة</p>
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
                        <p class="text-green-100 text-xs md:text-sm font-medium truncate">حضور</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['present_count'] }}</p>
                        <p class="text-xs md:text-sm text-green-100 mt-0.5 md:mt-1 hidden sm:block">جلسة حضرها الأبناء</p>
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
                        <p class="text-red-100 text-xs md:text-sm font-medium truncate">غياب</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['absent_count'] }}</p>
                        <p class="text-xs md:text-sm text-red-100 mt-0.5 md:mt-1 hidden sm:block">جلسة غائبة</p>
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
                        <p class="text-purple-100 text-xs md:text-sm font-medium truncate">نسبة الحضور</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $attendanceReport['overall']['attendance_rate'] }}%</p>
                        <p class="text-xs md:text-sm text-purple-100 mt-0.5 md:mt-1 hidden sm:block">معدل الحضور</p>
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
                            <h3 class="font-bold text-gray-900 text-sm md:text-base truncate">حضور جلسات القرآن</h3>
                            <p class="text-xs md:text-sm text-gray-500">نسبة الحضور: {{ $attendanceReport['quran']['attendance_rate'] }}%</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 md:p-6">
                    <div class="grid grid-cols-3 gap-2 md:gap-4 mb-3 md:mb-4">
                        <div class="text-center p-2 md:p-4 bg-green-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-green-600">{{ $attendanceReport['quran']['present'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">حضور</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-red-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-red-600">{{ $attendanceReport['quran']['absent'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">غياب</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-yellow-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-yellow-600">{{ $attendanceReport['quran']['late'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">تأخر</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">نسبة الحضور</span>
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
                            <h3 class="font-bold text-gray-900 text-sm md:text-base truncate">حضور الجلسات الأكاديمية</h3>
                            <p class="text-xs md:text-sm text-gray-500">نسبة الحضور: {{ $attendanceReport['academic']['attendance_rate'] }}%</p>
                        </div>
                    </div>
                </div>
                <div class="p-4 md:p-6">
                    <div class="grid grid-cols-3 gap-2 md:gap-4 mb-3 md:mb-4">
                        <div class="text-center p-2 md:p-4 bg-green-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-green-600">{{ $attendanceReport['academic']['present'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">حضور</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-red-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-red-600">{{ $attendanceReport['academic']['absent'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">غياب</p>
                        </div>
                        <div class="text-center p-2 md:p-4 bg-yellow-50 rounded-lg">
                            <p class="text-lg md:text-2xl font-bold text-yellow-600">{{ $attendanceReport['academic']['late'] }}</p>
                            <p class="text-[10px] md:text-xs text-gray-500">تأخر</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center justify-between text-sm mb-2">
                            <span class="text-gray-600">نسبة الحضور</span>
                            <span class="font-bold text-blue-600">{{ $attendanceReport['academic']['attendance_rate'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-blue-500 h-3 rounded-full transition-all duration-300" style="width: {{ $attendanceReport['academic']['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Per-Child Attendance -->
        @if(count($childrenAttendance) > 0)
        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-4 md:px-6 py-3 md:py-4 border-b border-gray-200">
                <h3 class="text-base md:text-lg font-bold text-gray-900 flex items-center">
                    <i class="ri-team-line text-purple-600 ms-1.5 md:ms-2"></i>
                    حضور كل طالب
                </h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($childrenAttendance as $childData)
                <div class="p-4 md:p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-start gap-3 md:gap-4">
                        <x-avatar
                            :user="$childData['child']->user"
                            user-type="student"
                            size="lg"
                        />
                        <div class="flex-1">
                            <h4 class="font-bold text-gray-900 mb-2 md:mb-3 text-sm md:text-base">{{ $childData['child']->user?->name ?? $childData['child']->first_name }}</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-4 text-xs md:text-sm">
                                <div>
                                    <p class="text-gray-500 text-[10px] md:text-xs">إجمالي الجلسات</p>
                                    <p class="font-bold text-gray-900">{{ $childData['attendance']['overall']['total_sessions'] }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-[10px] md:text-xs">حضور</p>
                                    <p class="font-bold text-green-600">{{ $childData['attendance']['overall']['present_count'] }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-[10px] md:text-xs">غياب</p>
                                    <p class="font-bold text-red-600">{{ $childData['attendance']['overall']['absent_count'] }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-[10px] md:text-xs">نسبة الحضور</p>
                                    <p class="font-bold text-purple-600">{{ $childData['attendance']['overall']['attendance_rate'] }}%</p>
                                </div>
                                <div class="col-span-2 sm:col-span-1">
                                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                        <div class="bg-purple-500 h-2 rounded-full" style="width: {{ $childData['attendance']['overall']['attendance_rate'] }}%"></div>
                                    </div>
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
</x-layouts.parent-layout>
