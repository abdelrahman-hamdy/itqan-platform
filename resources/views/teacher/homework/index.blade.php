<x-layouts.teacher title="{{ __('teacher.homework.title') }}">
    <div class="space-y-4 md:space-y-6">
        <!-- Page Header -->
        <div class="mb-1 md:mb-2">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 mb-1 md:mb-2">{{ __('teacher.homework.title') }}</h1>
            <p class="text-sm md:text-base text-gray-600">{{ __('teacher.homework.subtitle') }}</p>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
                <div class="flex items-start">
                    <i class="ri-checkbox-circle-line text-green-600 text-lg md:text-xl ms-2 flex-shrink-0"></i>
                    <p class="font-medium text-green-900 text-sm md:text-base">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
                <div class="flex items-start">
                    <i class="ri-error-warning-line text-red-600 text-lg md:text-xl ms-2 flex-shrink-0"></i>
                    <p class="font-medium text-red-900 text-sm md:text-base">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        <!-- Statistics Cards -->
        @if(isset($statistics))
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
            <!-- Total Homework -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-sm border border-blue-200 p-3 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm text-blue-700 mb-0.5 md:mb-1">{{ __('teacher.homework.stats.total_homework') }}</p>
                        <p class="text-xl md:text-3xl font-bold text-blue-900">{{ $statistics['total_homework'] }}</p>
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
                        <p class="text-xl md:text-3xl font-bold text-yellow-900">{{ $statistics['pending_grading'] }}</p>
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
                        <p class="text-xl md:text-3xl font-bold text-green-900">{{ $statistics['graded'] }}</p>
                    </div>
                    <div class="bg-green-200 rounded-full p-2 md:p-3 hidden sm:block">
                        <i class="ri-check-double-line text-green-700 text-lg md:text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Average Score -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-sm border border-purple-200 p-3 md:p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs md:text-sm text-purple-700 mb-0.5 md:mb-1">{{ __('teacher.homework.stats.average_score') }}</p>
                        <p class="text-xl md:text-3xl font-bold text-purple-900">{{ number_format($statistics['average_score'], 1) }}%</p>
                    </div>
                    <div class="bg-purple-200 rounded-full p-2 md:p-3 hidden sm:block">
                        <i class="ri-bar-chart-line text-purple-700 text-lg md:text-2xl"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Actions -->
        <div class="flex flex-wrap items-center gap-3 md:gap-4 mb-4 md:mb-6">
            <a href="{{ route('teacher.homework.statistics') }}" class="min-h-[44px] inline-flex items-center px-3 md:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm md:text-base">
                <i class="ri-line-chart-line ms-2"></i>
                {{ __('teacher.homework.detailed_statistics') }}
            </a>
        </div>

        <!-- Pending Submissions Section -->
        @if(isset($pendingSubmissions) && $pendingSubmissions->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 md:mb-8">
            <div class="border-b border-gray-200 p-4 md:p-6">
                <h2 class="text-base md:text-xl font-bold text-gray-900 flex items-center">
                    <i class="ri-alert-line text-yellow-600 ms-2"></i>
                    {{ __('teacher.homework.needs_grading_count', ['count' => $pendingSubmissions->count()]) }}
                </h2>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($pendingSubmissions as $submission)
                <div class="p-4 md:p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 sm:gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 md:gap-3 mb-2">
                                <h3 class="text-base md:text-lg font-semibold text-gray-900">
                                    {{ $submission->homework->title ?? __('teacher.homework.academic_homework') }}
                                </h3>
                                <span class="inline-flex items-center px-2 py-0.5 md:py-1 rounded text-xs font-medium
                                    {{ $submission->submission_status === \App\Enums\HomeworkSubmissionStatus::LATE ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $submission->submission_status->label() }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600 mb-2 md:mb-3">
                                <div class="flex items-center">
                                    <i class="ri-user-line ms-1"></i>
                                    <span>{{ __('teacher.homework.student_label') }} {{ $submission->student->name ?? __('teacher.homework.not_specified') }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="ri-calendar-line ms-1"></i>
                                    <span class="hidden sm:inline">{{ __('teacher.homework.submitted_on_label') }} </span>{{ $submission->submitted_at->format('Y-m-d h:i A') }}
                                </div>
                                @if($submission->is_late)
                                <div class="flex items-center text-red-600">
                                    <i class="ri-error-warning-line ms-1"></i>
                                    <span>{{ trans_choice('teacher.homework.late_by', $submission->days_late, ['days' => $submission->days_late]) }}</span>
                                </div>
                                @endif
                            </div>

                            @if($submission->homework->description)
                            <p class="text-xs md:text-sm text-gray-600 mb-2 md:mb-3 line-clamp-2">{{ Str::limit($submission->homework->description, 100) }}</p>
                            @endif

                            <div class="flex items-center gap-2 text-xs md:text-sm text-gray-500">
                                @if($submission->submission_text)
                                <span class="inline-flex items-center">
                                    <i class="ri-file-text-line ms-1"></i>
                                    {{ __('teacher.homework.answer_text') }}
                                </span>
                                @endif
                                @if($submission->submission_files && count($submission->submission_files) > 0)
                                <span class="inline-flex items-center">
                                    <i class="ri-attachment-line ms-1"></i>
                                    {{ trans_choice('teacher.homework.file_count', count($submission->submission_files), ['count' => count($submission->submission_files)]) }}
                                </span>
                                @endif
                            </div>
                        </div>

                        <a href="{{ route('teacher.homework.grade', $submission->id) }}"
                           class="min-h-[44px] w-full sm:w-auto mt-2 sm:mt-0 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white rounded-lg transition-all shadow-md text-sm md:text-base">
                            <i class="ri-edit-box-line ms-2"></i>
                            {{ __('teacher.homework.grade_now') }}
                        </a>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-6 md:mb-8 p-6 md:p-8 text-center">
            <div class="w-16 h-16 md:w-20 md:h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                <i class="ri-checkbox-circle-line text-green-600 text-2xl md:text-4xl"></i>
            </div>
            <h3 class="text-base md:text-xl font-semibold text-gray-900 mb-1 md:mb-2">{{ __('teacher.homework.no_pending_title') }}</h3>
            <p class="text-sm md:text-base text-gray-600">{{ __('teacher.homework.no_pending_desc') }}</p>
        </div>
        @endif

        <!-- All Homework Section -->
        @if(isset($homework) && $homework->count() > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="border-b border-gray-200 p-4 md:p-6">
                <h2 class="text-base md:text-xl font-bold text-gray-900 flex items-center">
                    <i class="ri-file-list-line text-blue-600 ms-2"></i>
                    {{ __('teacher.homework.all_homework') }}
                </h2>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($homework as $hw)
                <div class="p-4 md:p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 md:gap-3 mb-2">
                                <h3 class="text-base md:text-lg font-semibold text-gray-900">{{ $hw->title }}</h3>
                                <span class="inline-flex items-center px-2 py-0.5 md:py-1 rounded text-xs font-medium
                                    {{ $hw->status === 'active' ? 'bg-green-100 text-green-800' :
                                       ($hw->status === 'draft' ? 'bg-gray-100 text-gray-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $hw->status === 'active' ? __('teacher.homework.status_active') : ($hw->status === 'draft' ? __('teacher.homework.status_draft') : __('teacher.homework.status_closed')) }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 md:gap-4 text-xs md:text-sm text-gray-600 mb-3">
                                <div class="flex items-center">
                                    <i class="ri-calendar-line ms-1"></i>
                                    <span class="hidden sm:inline">{{ __('teacher.homework.due_date_label') }} </span>{{ $hw->due_date ? $hw->due_date->format('Y-m-d h:i A') : __('teacher.homework.not_specified') }}
                                </div>
                                <div class="flex items-center">
                                    <i class="ri-star-line ms-1"></i>
                                    <span>{{ __('teacher.homework.score_label') }} {{ $hw->max_score ?? 100 }}</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 md:gap-4 mt-3">
                                <div class="text-center p-2 md:p-3 bg-blue-50 rounded-lg">
                                    <p class="text-xs md:text-sm text-blue-700 mb-0.5 md:mb-1">{{ __('teacher.homework.total_students') }}</p>
                                    <p class="text-lg md:text-xl font-bold text-blue-900">{{ $hw->total_students ?? 0 }}</p>
                                </div>
                                <div class="text-center p-2 md:p-3 bg-green-50 rounded-lg">
                                    <p class="text-xs md:text-sm text-green-700 mb-0.5 md:mb-1">{{ __('teacher.homework.submitted_count') }}</p>
                                    <p class="text-lg md:text-xl font-bold text-green-900">{{ $hw->submitted_count ?? 0 }}</p>
                                </div>
                                <div class="text-center p-2 md:p-3 bg-purple-50 rounded-lg">
                                    <p class="text-xs md:text-sm text-purple-700 mb-0.5 md:mb-1">{{ __('teacher.homework.graded_count') }}</p>
                                    <p class="text-lg md:text-xl font-bold text-purple-900">{{ $hw->graded_count ?? 0 }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-row lg:flex-col gap-2 w-full lg:w-auto">
                            @if($hw->submitted_count > 0 && $hw->submitted_count > $hw->graded_count)
                            <a href="{{ route('teacher.homework.index', ['needs_grading' => true]) }}"
                               class="min-h-[44px] flex-1 lg:flex-none inline-flex items-center justify-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors text-sm">
                                <i class="ri-edit-box-line ms-2"></i>
                                {{ __('teacher.homework.grade_button', ['count' => $hw->submitted_count - $hw->graded_count]) }}
                            </a>
                            @endif

                            @if($hw->average_score)
                            <div class="text-center px-4 py-2 bg-gray-100 rounded-lg flex-1 lg:flex-none">
                                <p class="text-xs text-gray-600">{{ __('teacher.homework.average_label') }}</p>
                                <p class="text-base md:text-lg font-bold text-gray-900">{{ number_format($hw->average_score, 1) }}%</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8 text-center">
            <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 md:mb-4">
                <i class="ri-file-list-line text-gray-400 text-2xl md:text-4xl"></i>
            </div>
            <h3 class="text-base md:text-xl font-semibold text-gray-900 mb-1 md:mb-2">{{ __('teacher.homework.no_homework_title') }}</h3>
            <p class="text-sm md:text-base text-gray-600 mb-3 md:mb-4">{{ __('teacher.homework.no_homework_desc') }}</p>
        </div>
        @endif
    </div>
</x-layouts.teacher>
