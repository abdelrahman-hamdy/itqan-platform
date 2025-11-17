<x-layouts.teacher title="تقييم الواجب">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Back Button -->
        <div class="mb-6">
            <a href="{{ route('teacher.homework.index') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <i class="ri-arrow-right-line ml-1"></i>
                العودة إلى قائمة الواجبات
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="ri-checkbox-circle-line text-green-600 text-xl ml-2 flex-shrink-0"></i>
                    <div>
                        <p class="font-medium text-green-900">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <div class="flex items-start">
                    <i class="ri-error-warning-line text-red-600 text-xl ml-2 flex-shrink-0"></i>
                    <div>
                        <p class="font-medium text-red-900">{{ session('error') }}</p>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($submission) && isset($homework))
            <div class="space-y-6">
                <!-- Homework and Student Info Header -->
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-sm border border-blue-200 p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $homework->title }}</h1>

                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <!-- Student Info -->
                                <div class="flex items-center bg-white rounded-lg px-3 py-2 shadow-sm">
                                    <i class="ri-user-line text-blue-600 ml-2"></i>
                                    <span class="text-sm font-medium text-gray-700">الطالب:</span>
                                    <span class="text-sm font-semibold text-gray-900 mr-1">{{ $submission->student->name ?? 'غير محدد' }}</span>
                                </div>

                                <!-- Submission Status Badge -->
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                    {{ $submission->submission_status === 'not_submitted' ? 'bg-gray-100 text-gray-800' :
                                       ($submission->submission_status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                                       (in_array($submission->submission_status, ['submitted', 'late']) ? 'bg-blue-100 text-blue-800' :
                                       'bg-green-100 text-green-800')) }}">
                                    {{ $submission->submission_status_text }}
                                </span>

                                @if($submission->is_late)
                                <span class="inline-flex items-center px-2 py-1 rounded text-sm font-medium bg-red-100 text-red-800">
                                    <i class="ri-error-warning-line ml-1"></i>
                                    متأخر {{ $submission->days_late }} {{ $submission->days_late == 1 ? 'يوم' : 'أيام' }}
                                </span>
                                @endif
                            </div>

                            @if($homework->description)
                                <div class="bg-white rounded-lg p-4">
                                    <p class="text-sm font-medium text-gray-700 mb-1">وصف الواجب:</p>
                                    <p class="text-sm text-gray-800">{{ $homework->description }}</p>
                                </div>
                            @endif
                        </div>

                        @if($submission->score !== null)
                            <div class="text-center mr-4 bg-white rounded-lg p-4 shadow-sm">
                                <div class="text-4xl font-bold {{ $submission->score_percentage >= 80 ? 'text-green-600' :
                                                                   ($submission->score_percentage >= 60 ? 'text-yellow-600' :
                                                                   'text-red-600') }}">
                                    {{ number_format($submission->score_percentage, 1) }}%
                                </div>
                                <div class="text-sm text-gray-600 mt-1">
                                    {{ $submission->score }}/{{ $submission->max_score }}
                                </div>
                                <div class="text-xs text-gray-500 mt-1">
                                    {{ $submission->grade_performance }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Dates Info -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    @if($homework->due_date)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-blue-100 rounded-full p-3 ml-3">
                                <i class="ri-calendar-line text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">موعد التسليم</p>
                                <p class="font-semibold text-gray-900">{{ $homework->due_date->format('Y-m-d') }}</p>
                                <p class="text-xs text-gray-500">{{ $homework->due_date->format('h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($submission->submitted_at)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-green-100 rounded-full p-3 ml-3">
                                <i class="ri-send-plane-line text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">تم التسليم</p>
                                <p class="font-semibold text-gray-900">{{ $submission->submitted_at->format('Y-m-d') }}</p>
                                <p class="text-xs text-gray-500">{{ $submission->submitted_at->format('h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($submission->graded_at)
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                        <div class="flex items-center">
                            <div class="bg-purple-100 rounded-full p-3 ml-3">
                                <i class="ri-star-line text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">تم التصحيح</p>
                                <p class="font-semibold text-gray-900">{{ $submission->graded_at->format('Y-m-d') }}</p>
                                <p class="text-xs text-gray-500">{{ $submission->graded_at->format('h:i A') }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Teacher Files (if any) -->
                @if($homework->teacher_files && count($homework->teacher_files) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="ri-folder-line text-blue-600 ml-2"></i>
                        ملفات مرفقة من المعلم
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($homework->teacher_files as $file)
                            <a href="{{ Storage::url($file['path']) }}"
                               target="_blank"
                               class="flex items-center p-3 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors group">
                                <div class="bg-blue-100 rounded p-2 ml-3">
                                    <i class="ri-file-line text-blue-600 text-xl"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-gray-900 truncate group-hover:text-blue-600">
                                        {{ $file['original_name'] ?? 'ملف مرفق' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ number_format(($file['size'] ?? 0) / 1024, 2) }} KB
                                    </p>
                                </div>
                                <i class="ri-external-link-line text-gray-400 group-hover:text-blue-600"></i>
                            </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Grading Interface Component -->
                <x-homework.grading-interface
                    :submission="$submission"
                    :homework="$homework"
                    action="{{ route('teacher.homework.grade.process', $submission->id) }}"
                    method="POST"
                />
            </div>
        @else
            <!-- Error State -->
            <div class="bg-red-50 border border-red-200 rounded-xl p-8 text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-error-warning-line text-red-600 text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-red-900 mb-2">خطأ في تحميل الواجب</h3>
                <p class="text-red-700 mb-4">
                    عذراً، لم نتمكن من تحميل معلومات الواجب المطلوب.
                </p>
                <a href="{{ route('teacher.homework.index') }}" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                    <i class="ri-arrow-right-line ml-2"></i>
                    العودة إلى قائمة الواجبات
                </a>
            </div>
        @endif
    </div>
</x-layouts.teacher>
