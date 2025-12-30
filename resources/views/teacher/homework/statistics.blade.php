<x-layouts.teacher :title="__('teacher.homework.statistics_title')">
    <div class="space-y-4 md:space-y-6">
        <!-- Page Header -->
        <div class="mb-1 md:mb-2">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">{{ __('teacher.homework.statistics_title') }}</h1>
            <p class="text-sm md:text-base text-gray-600">{{ __('teacher.homework.statistics_subtitle') }}</p>
        </div>

        <!-- Back Button -->
        <div class="mb-4">
            <a href="{{ route('teacher.homework.index', ['subdomain' => request()->route('subdomain')]) }}"
               class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900">
                <i class="ri-arrow-right-line ms-1"></i>
                {{ __('teacher.homework.back_to_management') }}
            </a>
        </div>

        <!-- Statistics Cards -->
        @if(isset($statistics))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
            <!-- Total Homework -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-sm border border-blue-200 p-3 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm text-blue-700 mb-0.5 md:mb-1">{{ __('teacher.homework.stats.total_homework') }}</p>
                        <p class="text-xl md:text-3xl font-bold text-blue-900">{{ $statistics['total_homework'] ?? 0 }}</p>
                    </div>
                    <div class="bg-blue-200 rounded-full p-2 md:p-3 hidden sm:block">
                        <i class="ri-file-list-3-line text-blue-700 text-lg md:text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Grading -->
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl shadow-sm border border-yellow-200 p-3 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm text-yellow-700 mb-0.5 md:mb-1">{{ __('teacher.homework.stats.pending_grading') }}</p>
                        <p class="text-xl md:text-3xl font-bold text-yellow-900">{{ $statistics['pending_grading'] ?? 0 }}</p>
                    </div>
                    <div class="bg-yellow-200 rounded-full p-2 md:p-3 hidden sm:block">
                        <i class="ri-time-line text-yellow-700 text-lg md:text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Graded -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-sm border border-green-200 p-3 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm text-green-700 mb-0.5 md:mb-1">{{ __('teacher.homework.stats.graded') }}</p>
                        <p class="text-xl md:text-3xl font-bold text-green-900">{{ $statistics['graded'] ?? 0 }}</p>
                    </div>
                    <div class="bg-green-200 rounded-full p-2 md:p-3 hidden sm:block">
                        <i class="ri-checkbox-circle-line text-green-700 text-lg md:text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Average Grade -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-sm border border-purple-200 p-3 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm text-purple-700 mb-0.5 md:mb-1">{{ __('teacher.homework.stats.average_grade') }}</p>
                        <p class="text-xl md:text-3xl font-bold text-purple-900">{{ number_format($statistics['average_grade'] ?? 0, 1) }}%</p>
                    </div>
                    <div class="bg-purple-200 rounded-full p-2 md:p-3 hidden sm:block">
                        <i class="ri-bar-chart-line text-purple-700 text-lg md:text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <!-- Submission Rate Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('teacher.homework.stats.submission_rates') }}</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">{{ __('teacher.homework.stats.submission_rate') }}</span>
                        <span class="font-bold text-green-600">{{ number_format($statistics['submission_rate'] ?? 0, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $statistics['submission_rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>

            <!-- On-Time Submission Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">{{ __('teacher.homework.stats.on_time_submission') }}</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">{{ __('teacher.homework.stats.on_time_rate') }}</span>
                        <span class="font-bold text-blue-600">{{ number_format($statistics['on_time_rate'] ?? 0, 1) }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $statistics['on_time_rate'] ?? 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 text-center">
            <i class="ri-bar-chart-line text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('teacher.homework.no_statistics') }}</h3>
            <p class="text-gray-600">{{ __('teacher.homework.no_statistics_description') }}</p>
        </div>
        @endif

        <!-- Homework List Summary -->
        @if(isset($homework) && $homework->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4 md:p-6 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">{{ __('teacher.homework.homework_summary') }}</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-right text-gray-700 font-semibold">{{ __('teacher.homework.homework_name') }}</th>
                            <th class="px-4 py-3 text-center text-gray-700 font-semibold">{{ __('teacher.homework.students') }}</th>
                            <th class="px-4 py-3 text-center text-gray-700 font-semibold">{{ __('teacher.homework.submissions') }}</th>
                            <th class="px-4 py-3 text-center text-gray-700 font-semibold">{{ __('teacher.homework.average_grade_label') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($homework as $hw)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-900">{{ $hw->title ?? __('teacher.homework.homework_default') }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $hw->students_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-center text-gray-600">{{ $hw->submissions_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($hw->average_grade ?? 0) >= 70 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ number_format($hw->average_grade ?? 0, 1) }}%
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</x-layouts.teacher>
